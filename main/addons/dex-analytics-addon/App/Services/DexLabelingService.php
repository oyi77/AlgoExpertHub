<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DexLabelingService
{
    public function assignLabels(int $watchlistId, array $metrics): array
    {
        $labels = $this->deriveLabels($metrics);
        $computedAt = now();

        foreach ($labels as $label => $confidence) {
            DB::table('dex_trader_labels')->insert([
                'watchlist_id' => $watchlistId,
                'wallet_address' => Arr::get($metrics, 'wallet_address', ''),
                'platform' => Arr::get($metrics, 'platform', ''),
                'label' => $label,
                'confidence' => $confidence,
                'window_days' => Arr::get($metrics, 'window_days'),
                'computed_at' => $computedAt,
                'created_at' => $computedAt,
                'updated_at' => $computedAt,
            ]);
        }

        return $labels;
    }

    protected function deriveLabels(array $metrics): array
    {
        $labels = [];
        $winRate = (float) Arr::get($metrics, 'win_rate', 0);
        $profitFactor = (float) Arr::get($metrics, 'profit_factor', 0);
        $drawdown = (float) Arr::get($metrics, 'max_drawdown', 0);
        $avgTradeSize = (float) Arr::get($metrics, 'avg_trade_size', 0);

        if ($profitFactor >= 2 && $winRate >= 60) {
            $labels['Smart Money'] = 85.0;
        }

        if ($avgTradeSize >= 100000) {
            $labels['Whale'] = 80.0;
        }

        if ($drawdown <= 20 && $profitFactor >= 1.5) {
            $labels['Diamond Hands'] = 75.0;
        }

        if ($drawdown >= 50) {
            $labels['Paper Hands'] = 65.0;
        }

        if ($winRate >= 50 && $avgTradeSize > 0 && Arr::get($metrics, 'trade_frequency', 0) >= 20) {
            $labels['HFT/Scalper'] = 70.0;
        }

        return $labels;
    }
}
