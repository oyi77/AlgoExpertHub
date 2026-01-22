<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DexTimeBasedMetricsService
{
    public const TIME_PERIODS = ['1d', '7d', '30d', '90d', '180d', '365d', 'all_time'];

    public function computeAllTimeBasedMetrics(): void
    {
        $watchlists = DB::table('dex_trader_watchlist')
            ->where('is_active', true)
            ->get(['id', 'wallet_address', 'platform']);

        foreach ($watchlists as $watchlist) {
            $this->computeMetricsForWatchlist((int) $watchlist->id);
        }
    }

    public function computeMetricsForWatchlist(int $watchlistId): array
    {
        $watchlist = DB::table('dex_trader_watchlist')
            ->where('id', $watchlistId)
            ->first(['wallet_address', 'platform']);

        if (!$watchlist) {
            return [];
        }

        $results = [];

        foreach (self::TIME_PERIODS as $period) {
            $metrics = $this->computeMetricsForPeriod($watchlistId, $period);
            $this->storeMetrics($watchlistId, $watchlist->wallet_address, $watchlist->platform, $period, $metrics);
            $results[$period] = $metrics;
        }

        return $results;
    }

    protected function computeMetricsForPeriod(int $watchlistId, string $period): array
    {
        $startDate = $this->getStartDateForPeriod($period);

        $query = DB::table('dex_pnl_records')
            ->where('watchlist_id', $watchlistId)
            ->where('closed_at', '>=', $startDate)
            ->orderBy('closed_at');

        $pnlRecords = $query->get([
                'realized_pnl',
                'size',
                'funding_cost',
                'closed_at',
                'raw_payload',
            ]);

        $computationService = app(DexAnalyticsComputationService::class);
        $metrics = $computationService->computeMetricsForWatchlist($watchlistId);

        // Add period-specific calculations
        $metrics['time_period'] = $period;
        $metrics['period_start'] = $startDate;
        $metrics['period_end'] = now();

        return $metrics;
    }

    protected function getStartDateForPeriod(string $period): string
    {
        return match ($period) {
            '1d' => now()->subDay()->toDateTimeString(),
            '7d' => now()->subDays(7)->toDateTimeString(),
            '30d' => now()->subDays(30)->toDateTimeString(),
            '90d' => now()->subDays(90)->toDateTimeString(),
            '180d' => now()->subDays(180)->toDateTimeString(),
            '365d' => now()->subDays(365)->toDateTimeString(),
            'all_time' => '1970-01-01 00:00:00',
            default => now()->subDays(30)->toDateTimeString(),
        };
    }

    protected function storeMetrics(int $watchlistId, string $walletAddress, string $platform, string $period, array $metrics): void
    {
        DB::table('dex_time_based_metrics')->upsert(
            [
                'watchlist_id' => $watchlistId,
                'wallet_address' => $walletAddress,
                'platform' => $platform,
                'time_period' => $period,
                'total_pnl' => $metrics['total_pnl'] ?? 0,
                'win_rate' => $metrics['win_rate'] ?? 0,
                'profit_factor' => $metrics['profit_factor'] ?? 0,
                'total_trades' => $metrics['total_trades'] ?? 0,
                'sharpe_ratio' => $metrics['sharpe_ratio'] ?? 0,
                'calmar_ratio' => $metrics['calmar_ratio'] ?? 0,
                'sortino_ratio' => $metrics['sortino_ratio'] ?? 0,
                'max_drawdown' => $metrics['max_drawdown'] ?? 0,
                'avg_trade_size' => $metrics['avg_trade_size'] ?? 0,
                'avg_winning_trade' => $metrics['avg_winning_trade'] ?? 0,
                'avg_losing_trade' => $metrics['avg_losing_trade'] ?? 0,
                'win_loss_ratio' => $metrics['win_loss_ratio'] ?? 0,
                'avg_holding_time' => $metrics['avg_holding_time'] ?? 0,
                'liquidation_rate' => $metrics['liquidation_rate'] ?? 0,
                'funding_cost_ratio' => $metrics['funding_cost_ratio'] ?? 0,
                'total_exposure' => $metrics['total_exposure'] ?? 0,
                'pnl_category' => $metrics['pnl_category'] ?? 'break_even',
                'wallet_tier' => $metrics['wallet_tier'] ?? 'shrimp',
                'consistency_score' => $metrics['consistency_score'] ?? 0,
                'copy_suitability_score' => $metrics['copy_suitability_score'] ?? 0,
                'copy_rating' => $metrics['copy_rating'] ?? 'F',
                'period_start' => $metrics['period_start'] ?? null,
                'period_end' => $metrics['period_end'] ?? now(),
                'updated_at' => now(),
            ],
            ['watchlist_id', 'time_period'],
            [
                'total_pnl',
                'win_rate',
                'profit_factor',
                'total_trades',
                'sharpe_ratio',
                'calmar_ratio',
                'sortino_ratio',
                'max_drawdown',
                'avg_trade_size',
                'avg_winning_trade',
                'avg_losing_trade',
                'win_loss_ratio',
                'avg_holding_time',
                'liquidation_rate',
                'funding_cost_ratio',
                'total_exposure',
                'pnl_category',
                'wallet_tier',
                'consistency_score',
                'copy_suitability_score',
                'copy_rating',
                'period_start',
                'period_end',
                'updated_at',
            ]
        );
    }

    public function getMetricsForWatchlist(int $watchlistId, ?string $period = null): array
    {
        $query = DB::table('dex_time_based_metrics')
            ->where('watchlist_id', $watchlistId);

        if ($period) {
            $query->where('time_period', $period);
            return $query->first() ?? [];
        }

        return $query->get()->keyBy('time_period')->toArray();
    }

    public function getLeaderboardForPeriod(string $period, int $limit = 100): array
    {
        $metrics = DB::table('dex_time_based_metrics')
            ->where('time_period', $period)
            ->orderByDesc('total_pnl')
            ->limit($limit)
            ->get();

        return $metrics->toArray();
    }

    public function getTopCopySuitable(int $limit = 50): array
    {
        $metrics = DB::table('dex_time_based_metrics')
            ->where('time_period', '30d')
            ->orderByDesc('copy_suitability_score')
            ->limit($limit)
            ->get();

        return $metrics->toArray();
    }

    public function comparePeriods(int $watchlistId, string $period1, string $period2): array
    {
        $metrics1 = DB::table('dex_time_based_metrics')
            ->where('watchlist_id', $watchlistId)
            ->where('time_period', $period1)
            ->first();

        $metrics2 = DB::table('dex_time_based_metrics')
            ->where('watchlist_id', $watchlistId)
            ->where('time_period', $period2)
            ->first();

        if (!$metrics1 || !$metrics2) {
            return [];
        }

        $comparison = [];

        foreach (['total_pnl', 'win_rate', 'profit_factor', 'sharpe_ratio', 'max_drawdown'] as $key) {
            $val1 = (float) ($metrics1->$key ?? 0);
            $val2 = (float) ($metrics2->$key ?? 0);

            $comparison[$key] = [
                'period_1' => $val1,
                'period_2' => $val2,
                'change' => $val2 - $val1,
                'change_pct' => $val1 !== 0.0 ? round(($val2 - $val1) / abs($val1) * 100, 2) : 0,
            ];
        }

        return $comparison;
    }
}
