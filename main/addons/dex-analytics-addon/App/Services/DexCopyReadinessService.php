<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DexCopyReadinessService
{
    public function calculateScore(array $metrics): int
    {
        $score = 100;
        $tradeFrequency = (float) Arr::get($metrics, 'trade_frequency', 0);
        $avgPositionSize = (float) Arr::get($metrics, 'avg_trade_size', 0);
        $maxDrawdown = (float) Arr::get($metrics, 'max_drawdown', 0);
        $profitFactor = (float) Arr::get($metrics, 'profit_factor', 0);

        if ($tradeFrequency > 20) {
            $score -= 30;
        }

        if ($avgPositionSize < 1000) {
            $score -= 20;
        }

        if ($maxDrawdown > 50) {
            $score -= 25;
        }

        if ($profitFactor >= 2.0) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    public function storeScore(int $watchlistId, array $metrics): int
    {
        $score = $this->calculateScore($metrics);
        $computedAt = now();

        DB::table('dex_copy_suitability')->insert([
            'watchlist_id' => $watchlistId,
            'wallet_address' => Arr::get($metrics, 'wallet_address', ''),
            'platform' => Arr::get($metrics, 'platform', ''),
            'score' => $score,
            'trade_frequency' => Arr::get($metrics, 'trade_frequency'),
            'avg_position_size' => Arr::get($metrics, 'avg_trade_size'),
            'max_drawdown' => Arr::get($metrics, 'max_drawdown'),
            'profit_factor' => Arr::get($metrics, 'profit_factor'),
            'computed_at' => $computedAt,
            'metadata' => json_encode(['source' => static::class]),
            'created_at' => $computedAt,
            'updated_at' => $computedAt,
        ]);

        return $score;
    }
}
