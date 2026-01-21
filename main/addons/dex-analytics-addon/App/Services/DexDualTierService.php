<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Arr;

class DexDualTierService
{
    public function determineTier(array $metrics): string
    {
        $fundingCostRatio = (float) Arr::get($metrics, 'funding_cost_ratio', 0);
        $avgTradeSize = (float) Arr::get($metrics, 'avg_trade_size', 0);

        if ($avgTradeSize >= 200000) {
            return 'llp';
        }

        if ($fundingCostRatio > 0.02) {
            return 'premium';
        }

        return 'standard';
    }

    public function getTierMetrics(array $metrics): array
    {
        $tier = $this->determineTier($metrics);

        return [
            'tier' => $tier,
            'liquidity_score' => $tier === 'llp' ? 85 : 50,
            'maker_taker_ratio' => $tier === 'premium' ? 0.6 : 0.3,
        ];
    }
}
