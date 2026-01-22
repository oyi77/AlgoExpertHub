<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\TradingManagement\TradingBot\ConfigManager;

use Tests\Unit\Addons\TradingManagement\TradingBot\TradingBotTestCase;
use Addons\TradingManagement\Modules\TradingBot\Services\ConfigManager\TradingBotConfigManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConfigManagerTest extends TradingBotTestCase
{
    use RefreshDatabase;
    
    public function test_update_config_persists(): void
    {
        $bot = $this->createTestBot();
        
        $manager = app(TradingBotConfigManager::class);
        $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.03]);
        
        $bot->refresh();
        $this->assertEquals(0.03, $bot->tradingPreset->risk_per_trade_pct);
        
        $this->assertDatabaseHas('trading_presets', [
            'id' => $bot->trading_preset_id,
            'risk_per_trade_pct' => 0.03,
        ]);
    }
    
    public function test_config_update_publishes_redis_event(): void
    {
        $bot = $this->createTestBot(['status' => 'running']);
        
        $manager = app(TradingBotConfigManager::class);
        
        Redis::shouldReceive('publish')
            ->once()
            ->with(
                "bot:{$bot->id}:config",
                \Mockery::on(function ($payload) {
                    $data = json_decode($payload, true);
                    return $data['event'] === 'config_updated'
                        && isset($data['config'])
                        && isset($data['timestamp']);
                })
            );
        
        $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.04]);
    }
    
    public function test_runtime_config_cached(): void
    {
        $bot = $this->createTestBot();
        
        $manager = app(TradingBotConfigManager::class);
        
        Cache::shouldReceive('remember')
            ->twice()
            ->with("bot_config:{$bot->id}", 3600, \Mockery::on(function ($closure) {
                return $closure instanceof \Closure;
            }))
            ->andReturn(['risk_per_trade_pct' => 0.02]);

        $config1 = $manager->getRuntimeConfig($bot);

        $config2 = $manager->getRuntimeConfig($bot);
        
        $this->assertEquals($config1, $config2);
    }
    
    protected function buildExpectedConfig($bot): array
    {
        return [
            'bot_id' => $bot->id,
            'risk_per_trade_pct' => 0.02,
            'max_open_trades' => 3,
            'stop_loss_pct' => 0.05,
            'take_profit_pct' => 0.10,
            'trading_hours' => [],
            'allowed_symbols' => ['BTC/USDT', 'ETH/USDT'],
        ];
    }
}
