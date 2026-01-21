<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class DexAnalyticsComputationService
{
    public function computeAllMetrics(): void
    {
        $watchlists = DB::table('dex_trader_watchlist')
            ->where('is_active', true)
            ->get(['id']);

        foreach ($watchlists as $watchlist) {
            $this->computeAndCacheMetrics((int) $watchlist->id);
        }
    }

    public function computeMetricsForWatchlist(int $watchlistId): array
    {
        $pnlRecords = DB::table('dex_pnl_records')
            ->where('watchlist_id', $watchlistId)
            ->orderBy('closed_at')
            ->get([
                'realized_pnl',
                'size',
                'funding_cost',
                'closed_at',
                'raw_payload',
            ]);

        $totalTrades = $pnlRecords->count();
        $totalPnl = (float) $pnlRecords->sum('realized_pnl');
        $wins = $pnlRecords->filter(fn ($record) => (float) $record->realized_pnl > 0)->count();
        $winRate = $totalTrades > 0 ? round(($wins / $totalTrades) * 100, 2) : 0.0;
        $avgTradeSize = $totalTrades > 0 ? (float) $pnlRecords->avg('size') : 0.0;
        $fundingCost = (float) $pnlRecords->sum('funding_cost');
        $fundingCostRatio = $totalPnl !== 0.0 ? round($fundingCost / abs($totalPnl), 4) : 0.0;
        $profitFactor = $this->calculateProfitFactor($pnlRecords->pluck('realized_pnl')->all());
        $maxDrawdown = $this->calculateMaxDrawdown($pnlRecords->pluck('realized_pnl')->all());
        $avgHoldingTime = $this->calculateAvgHoldingTime($pnlRecords->pluck('raw_payload')->all());
        $liquidationRate = $this->calculateLiquidationRate($watchlistId, $totalTrades);
        $totalExposure = (float) DB::table('dex_position_snapshots')
            ->where('watchlist_id', $watchlistId)
            ->sum('size');

        return [
            'total_pnl' => $totalPnl,
            'win_rate' => $winRate,
            'avg_holding_time' => $avgHoldingTime,
            'profit_factor' => $profitFactor,
            'max_drawdown' => $maxDrawdown,
            'avg_trade_size' => $avgTradeSize,
            'funding_cost_ratio' => $fundingCostRatio,
            'liquidation_rate' => $liquidationRate,
            'total_exposure' => $totalExposure,
        ];
    }

    public function computeAndCacheMetrics(int $watchlistId): array
    {
        $metrics = $this->computeMetricsForWatchlist($watchlistId);
        $computedAt = now();
        $watchlist = DB::table('dex_trader_watchlist')
            ->where('id', $watchlistId)
            ->first(['wallet_address', 'platform']);

        foreach ($metrics as $key => $value) {
            DB::table('dex_analytics_cache')->insert([
                'watchlist_id' => $watchlistId,
                'wallet_address' => $watchlist?->wallet_address ?? '',
                'platform' => $watchlist?->platform ?? '',
                'metric_key' => $key,
                'metric_value' => json_encode($value),
                'computed_at' => $computedAt,
                'created_at' => $computedAt,
                'updated_at' => $computedAt,
            ]);
        }

        return $metrics;
    }

    protected function calculateProfitFactor(array $pnls): float
    {
        $positive = 0.0;
        $negative = 0.0;

        foreach ($pnls as $pnl) {
            $value = (float) $pnl;
            if ($value >= 0) {
                $positive += $value;
            } else {
                $negative += $value;
            }
        }

        if ($negative === 0.0) {
            return $positive > 0 ? round($positive, 4) : 0.0;
        }

        return round($positive / abs($negative), 4);
    }

    protected function calculateMaxDrawdown(array $pnls): float
    {
        $peak = 0.0;
        $trough = 0.0;
        $maxDrawdown = 0.0;
        $cumulative = 0.0;

        foreach ($pnls as $pnl) {
            $cumulative += (float) $pnl;
            if ($cumulative > $peak) {
                $peak = $cumulative;
                $trough = $cumulative;
            }

            if ($cumulative < $trough) {
                $trough = $cumulative;
                $drawdown = $peak - $trough;
                if ($drawdown > $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                }
            }
        }

        return round($maxDrawdown, 4);
    }

    protected function calculateAvgHoldingTime(array $rawPayloads): float
    {
        $durations = [];

        foreach ($rawPayloads as $payload) {
            $decoded = is_string($payload) ? json_decode($payload, true) : $payload;
            if (!is_array($decoded)) {
                continue;
            }

            $openedAt = Arr::get($decoded, 'opened_at') ?? Arr::get($decoded, 'entry_time');
            $closedAt = Arr::get($decoded, 'closed_at') ?? Arr::get($decoded, 'exit_time');

            if (!$openedAt || !$closedAt) {
                continue;
            }

            try {
                $start = is_numeric($openedAt) ? Carbon::createFromTimestamp((int) $openedAt) : Carbon::parse($openedAt);
                $end = is_numeric($closedAt) ? Carbon::createFromTimestamp((int) $closedAt) : Carbon::parse($closedAt);
                $durations[] = $end->diffInSeconds($start);
            } catch (\Throwable $exception) {
                continue;
            }
        }

        if (count($durations) === 0) {
            return 0.0;
        }

        return round(array_sum($durations) / count($durations), 2);
    }

    protected function calculateLiquidationRate(int $watchlistId, int $totalTrades): float
    {
        if ($totalTrades === 0) {
            return 0.0;
        }

        $liquidations = DB::table('dex_liquidation_events')
            ->where('watchlist_id', $watchlistId)
            ->count();

        return round(($liquidations / $totalTrades) * 100, 2);
    }
}
