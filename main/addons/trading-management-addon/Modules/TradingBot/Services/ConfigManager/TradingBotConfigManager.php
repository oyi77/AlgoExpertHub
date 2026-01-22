<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\TradingBot\Services\ConfigManager;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TradingBotConfigManager
{
    public function updateConfig(TradingBot $bot, array $config): void
    {
        DB::transaction(function () use ($bot, $config) {
            $preset = $bot->tradingPreset;
            if (!$preset) {
                throw new \Exception("TradingPreset not found for bot {$bot->id}");
            }

            $preset->update($config);

            Cache::forget("bot_config:{$bot->id}");

            Redis::publish("bot:{$bot->id}:config", json_encode([
                'event' => 'config_updated',
                'config' => $this->getRuntimeConfig($bot),
                'timestamp' => now()->toIso8601String(),
            ]));
        });
    }

    public function getRuntimeConfig(TradingBot $bot): array
    {
        $cacheKey = "bot_config:{$bot->id}";

        return Cache::remember($cacheKey, 3600, function () use ($bot) {
            return $this->buildRuntimeConfig($bot);
        });
    }

    protected function buildRuntimeConfig(TradingBot $bot): array
    {
        $preset = $bot->tradingPreset;
        
        $config = [
            'bot_id' => $bot->id,
            'risk_per_trade_pct' => 0.01,
            'max_open_trades' => 3,
            'stop_loss_pct' => 0.05,
            'take_profit_pct' => 0.10,
            'trading_hours' => [],
            'allowed_symbols' => [],
        ];

        if ($preset) {
            $config['risk_per_trade_pct'] = (float) ($preset->risk_per_trade_pct ?? $config['risk_per_trade_pct']);
            $config['max_open_trades'] = (int) ($preset->max_positions ?? $config['max_open_trades']);
            
            if ($preset->symbol) {
                $config['allowed_symbols'] = [$preset->symbol];
            }
        }

        return $config;
    }
}
