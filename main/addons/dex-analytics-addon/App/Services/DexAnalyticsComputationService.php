<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class DexAnalyticsComputationService
{
    public const WALLET_TIERS = [
        'kraken' => 5000000,      // $5M+
        'whale' => 1000000,       // $1M-$5M
        'large_whale' => 500000,  // $500K-$1M
        'shark' => 100000,        // $100K-$500K
        'dolphin' => 50000,       // $50K-$100K
        'large_fish' => 10000,    // $10K-$50K
        'fish' => 250,            // $250-$10K
        'shrimp' => 0,            // $0-$250
    ];

    public const PNL_CATEGORIES = [
        'extremely_profitable' => 1000000,  // +$1M PNL
        'highly_profitable' => 100000,      // +$100K PNL
        'profitable' => 10000,              // +$10K PNL
        'marginally_profitable' => 1000,    // +$1K PNL
        'break_even' => 0,                  // ~$0
        'marginally_rekt' => -1000,         // -$1K
        'rekt' => -10000,                   // -$10K
        'heavily_rekt' => -100000,          // -$100K
        'completely_rekt' => -1000000,      // -$1M+
    ];

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
        $pnlArray = $pnlRecords->pluck('realized_pnl')->all();

        $wins = $pnlRecords->filter(fn ($record) => (float) $record->realized_pnl > 0)->count();
        $losses = $pnlRecords->filter(fn ($record) => (float) $record->realized_pnl < 0)->count();
        $winRate = $totalTrades > 0 ? round(($wins / $totalTrades) * 100, 2) : 0.0;

        $avgTradeSize = $totalTrades > 0 ? (float) $pnlRecords->avg('size') : 0.0;
        $fundingCost = (float) $pnlRecords->sum('funding_cost');
        $fundingCostRatio = $totalPnl !== 0.0 ? round($fundingCost / abs($totalPnl), 4) : 0.0;

        $profitFactor = $this->calculateProfitFactor($pnlArray);
        $maxDrawdown = $this->calculateMaxDrawdown($pnlArray);
        $avgHoldingTime = $this->calculateAvgHoldingTime($pnlRecords->pluck('raw_payload')->all());
        $liquidationRate = $this->calculateLiquidationRate($watchlistId, $totalTrades);
        $totalExposure = (float) DB::table('dex_position_snapshots')
            ->where('watchlist_id', $watchlistId)
            ->sum('size');

        // New metrics
        $sharpeRatio = $this->calculateSharpeRatio($pnlArray);
        $calmarRatio = $this->calculateCalmarRatio($pnlArray, $maxDrawdown);
        $sortinoRatio = $this->calculateSortinoRatio($pnlArray);
        $winLossRatio = $this->calculateWinLossRatio($pnlArray);
        $avgWinningTrade = $this->calculateAvgWinningTrade($pnlArray);
        $avgLosingTrade = $this->calculateAvgLosingTrade($pnlArray);
        $pnlCategory = $this->categorizePnl($totalPnl);
        $walletTier = $this->classifyWalletTier($totalExposure);
        $consistencyScore = $this->calculateConsistencyScore($pnlArray, $winRate);
        $tradingFrequency = $this->calculateTradingFrequency($pnlRecords);

        return [
            // Core metrics
            'total_pnl' => $totalPnl,
            'win_rate' => $winRate,
            'avg_holding_time' => $avgHoldingTime,
            'profit_factor' => $profitFactor,
            'max_drawdown' => $maxDrawdown,
            'avg_trade_size' => $avgTradeSize,
            'funding_cost_ratio' => $fundingCostRatio,
            'liquidation_rate' => $liquidationRate,
            'total_exposure' => $totalExposure,
            'total_trades' => $totalTrades,

            // Advanced risk metrics
            'sharpe_ratio' => $sharpeRatio,
            'calmar_ratio' => $calmarRatio,
            'sortino_ratio' => $sortinoRatio,

            // Trade analysis
            'win_loss_ratio' => $winLossRatio,
            'avg_winning_trade' => $avgWinningTrade,
            'avg_losing_trade' => $avgLosingTrade,

            // Classifications
            'pnl_category' => $pnlCategory,
            'wallet_tier' => $walletTier,

            // Scores
            'consistency_score' => $consistencyScore,
            'trading_frequency' => $tradingFrequency,
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

    /**
     * Calculate Sharpe Ratio (risk-adjusted return)
     * Formula: (Average Return - Risk-Free Rate) / Standard Deviation
     * Using annualization factor for daily returns (sqrt(365))
     */
    public function calculateSharpeRatio(array $pnls, float $riskFreeRate = 0.0): float
    {
        if (count($pnls) < 2) {
            return 0.0;
        }

        $returns = array_map(fn ($p) => (float) $p, $pnls);
        $mean = array_sum($returns) / count($returns);

        $squaredDiffs = array_map(fn ($r) => pow($r - $mean, 2), $returns);
        $stdDev = sqrt(array_sum($squaredDiffs) / count($returns));

        if ($stdDev === 0.0) {
            return $mean > 0 ? 5.0 : 0.0; // Cap at 5.0 for perfect strategies
        }

        $sharpe = ($mean - $riskFreeRate) / $stdDev;

        // Annualize (assuming daily returns)
        $annualizedSharpe = $sharpe * sqrt(count($pnls));

        return round(min(max($annualizedSharpe, -10), 10), 2); // Clamp between -10 and 10
    }

    /**
     * Calculate Calmar Ratio (return / max drawdown)
     */
    public function calculateCalmarRatio(array $pnls, float $maxDrawdown): float
    {
        if ($maxDrawdown === 0.0 || $maxDrawdown <= 0) {
            return 0.0;
        }

        $totalReturn = array_sum(array_map(fn ($p) => (float) $p, $pnls));
        $annualReturn = $totalReturn / max(1, count($pnls)) * 365;

        return round($annualReturn / abs($maxDrawdown), 2);
    }

    /**
     * Calculate Sortino Ratio (downside risk-adjusted return)
     * Only considers negative returns for risk calculation
     */
    public function calculateSortinoRatio(array $pnls, float $targetReturn = 0.0): float
    {
        if (count($pnls) < 2) {
            return 0.0;
        }

        $returns = array_map(fn ($p) => (float) $p, $pnls);
        $mean = array_sum($returns) / count($returns);

        // Only consider downside deviations
        $downsideDiffs = [];
        foreach ($returns as $r) {
            $diff = $r - $targetReturn;
            if ($diff < 0) {
                $downsideDiffs[] = $diff * $diff;
            }
        }

        if (count($downsideDiffs) === 0) {
            return $mean > 0 ? 5.0 : 0.0;
        }

        $downsideStdDev = sqrt(array_sum($downsideDiffs) / count($downsideDiffs));

        if ($downsideStdDev === 0.0) {
            return $mean > 0 ? 5.0 : 0.0;
        }

        $sortino = ($mean - $targetReturn) / $downsideStdDev;

        return round(min(max($sortino, -10), 10), 2);
    }

    /**
     * Calculate Win/Loss Ratio
     */
    public function calculateWinLossRatio(array $pnls): float
    {
        $wins = 0;
        $losses = 0;
        $totalWin = 0;
        $totalLoss = 0;

        foreach ($pnls as $pnl) {
            $value = (float) $pnl;
            if ($value > 0) {
                $wins++;
                $totalWin += $value;
            } elseif ($value < 0) {
                $losses++;
                $totalLoss += abs($value);
            }
        }

        if ($losses === 0) {
            return $wins > 0 ? round($totalWin, 2) : 0.0;
        }

        $avgWin = $wins > 0 ? $totalWin / $wins : 0;
        $avgLoss = $totalLoss / $losses;

        if ($avgLoss === 0.0) {
            return $avgWin > 0 ? round($avgWin, 2) : 0.0;
        }

        return round($avgWin / $avgLoss, 2);
    }

    /**
     * Calculate Average Winning Trade
     */
    public function calculateAvgWinningTrade(array $pnls): float
    {
        $wins = 0;
        $total = 0.0;

        foreach ($pnls as $pnl) {
            $value = (float) $pnl;
            if ($value > 0) {
                $wins++;
                $total += $value;
            }
        }

        return $wins > 0 ? round($total / $wins, 2) : 0.0;
    }

    /**
     * Calculate Average Losing Trade
     */
    public function calculateAvgLosingTrade(array $pnls): float
    {
        $losses = 0;
        $total = 0.0;

        foreach ($pnls as $pnl) {
            $value = (float) $pnl;
            if ($value < 0) {
                $losses++;
                $total += abs($value);
            }
        }

        return $losses > 0 ? round($total / $losses, 2) : 0.0;
    }

    /**
     * Categorize PNL into performance tiers
     */
    public function categorizePnl(float $totalPnl): string
    {
        if ($totalPnl >= 1000000) {
            return 'extremely_profitable';
        }
        if ($totalPnl >= 100000) {
            return 'highly_profitable';
        }
        if ($totalPnl >= 10000) {
            return 'profitable';
        }
        if ($totalPnl >= 1000) {
            return 'marginally_profitable';
        }
        if ($totalPnl >= -1000) {
            return 'break_even';
        }
        if ($totalPnl >= -10000) {
            return 'marginally_rekt';
        }
        if ($totalPnl >= -100000) {
            return 'rekt';
        }
        if ($totalPnl >= -1000000) {
            return 'heavily_rekt';
        }

        return 'completely_rekt';
    }

    /**
     * Get human-readable PNL category label
     */
    public function getPnlCategoryLabel(string $category): string
    {
        return match ($category) {
            'extremely_profitable' => 'Extremely Profitable (+$1M+)',
            'highly_profitable' => 'Highly Profitable (+$100K+)',
            'profitable' => 'Profitable (+$10K+)',
            'marginally_profitable' => 'Marginally Profitable (+$1K+)',
            'break_even' => 'Break Even',
            'marginally_rekt' => 'Marginally Rekt (-$1K)',
            'rekt' => 'Rekt (-$10K)',
            'heavily_rekt' => 'Heavily Rekt (-$100K)',
            'completely_rekt' => 'Completely Rekt (-$1M+)',
            default => 'Unknown',
        };
    }

    /**
     * Classify wallet into size tiers
     */
    public function classifyWalletTier(float $totalExposure): string
    {
        if ($totalExposure >= 5000000) {
            return 'kraken';
        }
        if ($totalExposure >= 1000000) {
            return 'whale';
        }
        if ($totalExposure >= 500000) {
            return 'large_whale';
        }
        if ($totalExposure >= 100000) {
            return 'shark';
        }
        if ($totalExposure >= 50000) {
            return 'dolphin';
        }
        if ($totalExposure >= 10000) {
            return 'large_fish';
        }
        if ($totalExposure >= 250) {
            return 'fish';
        }

        return 'shrimp';
    }

    /**
     * Get human-readable wallet tier label
     */
    public function getWalletTierLabel(string $tier): string
    {
        return match ($tier) {
            'kraken' => 'Kraken ($5M+)',
            'whale' => 'Whale ($1M-$5M)',
            'large_whale' => 'Large Whale ($500K-$1M)',
            'shark' => 'Shark ($100K-$500K)',
            'dolphin' => 'Dolphin ($50K-$100K)',
            'large_fish' => 'Large Fish ($10K-$50K)',
            'fish' => 'Fish ($250-$10K)',
            'shrimp' => 'Shrimp ($0-$250)',
            default => 'Unknown',
        };
    }

    /**
     * Calculate consistency score (0-100)
     * Based on win rate, profit factor, and drawdown
     */
    public function calculateConsistencyScore(array $pnls, float $winRate): float
    {
        $profitFactor = $this->calculateProfitFactor($pnls);
        $maxDrawdown = $this->calculateMaxDrawdown($pnls);

        // Normalize components
        $winScore = min($winRate / 100, 1) * 30; // Max 30 points
        $pfScore = min($profitFactor / 3, 1) * 30; // Max 30 points (3.0+ is excellent)
        $drawdownScore = max(0, (1 - abs($maxDrawdown) / 10000)) * 40; // Max 40 points

        return round($winScore + $pfScore + $drawdownScore, 1);
    }

    /**
     * Calculate trading frequency (trades per day)
     */
    public function calculateTradingFrequency($pnlRecords): float
    {
        if ($pnlRecords->isEmpty()) {
            return 0.0;
        }

        $firstTrade = $pnlRecords->first()->closed_at;
        $lastTrade = $pnlRecords->last()->closed_at;

        $start = is_string($firstTrade) ? strtotime($firstTrade) : (int) $firstTrade;
        $end = is_string($lastTrade) ? strtotime($lastTrade) : (int) $lastTrade;

        $days = max(1, ($end - $start) / 86400);

        return round($pnlRecords->count() / $days, 2);
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
