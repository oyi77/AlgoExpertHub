<?php

namespace Addons\TradingManagement\Modules\Marketplace\Services;

use Addons\TradingManagement\Modules\Marketplace\Models\{TraderProfile, TraderLeaderboard};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaderboardService
{
    public function calculateAndUpdate(string $timeframe = 'all_time'): int
    {
        $traders = TraderProfile::public()->verified()->get();
        $rank = 1;
        $updated = 0;

        $dateFilter = $this->getDateFilter($timeframe);

        foreach ($traders as $trader) {
            $stats = $this->calculateTraderStats($trader, $dateFilter);

            TraderLeaderboard::updateOrCreate(
                [
                    'trader_id' => $trader->id,
                    'timeframe' => $timeframe,
                ],
                array_merge($stats, [
                    'rank' => $rank++,
                    'calculated_at' => now(),
                ])
            );

            $updated++;
        }

        return $updated;
    }

    public function getLeaderboard(string $timeframe = 'all_time', int $limit = 100)
    {
        return TraderLeaderboard::byTimeframe($timeframe)
            ->with('traderProfile.user')
            ->topRanked($limit)
            ->get();
    }

    public function getTraderRank(int $traderId, string $timeframe = 'all_time'): ?int
    {
        $entry = TraderLeaderboard::where('trader_id', $traderId)
            ->where('timeframe', $timeframe)
            ->first();

        return $entry?->rank;
    }

    protected function calculateTraderStats(TraderProfile $trader, ?Carbon $startDate): array
    {
        // Get trader's copy trading subscriptions
        $query = DB::table('copy_trading_executions')
            ->where('trader_id', $trader->user_id);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $executions = $query->get();

        $totalTrades = $executions->count();
        $winningTrades = $executions->where('status', 'success')->count();
        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

        // Calculate profit %
        $totalProfit = $executions->sum('profit_amount');
        $profitPercent = $trader->total_profit_percent; // Use profile value

        // Get new followers in period
        $followersQuery = DB::table('copy_trading_subscriptions')
            ->where('trader_id', $trader->user_id);
        
        if ($startDate) {
            $followersQuery->where('subscribed_at', '>=', $startDate);
        }
        
        $followersGained = $followersQuery->count();

        // Calculate Sharpe ratio (simplified)
        $returns = $executions->pluck('profit_percent')->toArray();
        $sharpeRatio = $this->calculateSharpeRatio($returns);

        // Calculate avg rating
        $avgRating = DB::table('trader_ratings')
            ->where('trader_id', $trader->id)
            ->avg('rating') ?? 0;

        return [
            'profit_percent' => $profitPercent,
            'win_rate' => round($winRate, 2),
            'sharpe_ratio' => $sharpeRatio,
            'roi' => $profitPercent, // Simplified
            'total_trades' => $totalTrades,
            'followers_gained' => $followersGained,
            'avg_rating' => round($avgRating, 2),
        ];
    }

    protected function calculateSharpeRatio(array $returns): ?float
    {
        if (empty($returns)) {
            return null;
        }

        $mean = array_sum($returns) / count($returns);
        $variance = 0;

        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }

        $stdDev = sqrt($variance / count($returns));

        if ($stdDev == 0) {
            return null;
        }

        $riskFreeRate = 0.02; // 2% annual
        return round(($mean - $riskFreeRate) / $stdDev, 4);
    }

    protected function getDateFilter(string $timeframe): ?Carbon
    {
        return match($timeframe) {
            'daily' => Carbon::now()->subDay(),
            'weekly' => Carbon::now()->subWeek(),
            'monthly' => Carbon::now()->subMonth(),
            'all_time' => null,
            default => null,
        };
    }

    public function updateAllTimeframes(): array
    {
        $results = [];

        foreach (['daily', 'weekly', 'monthly', 'all_time'] as $timeframe) {
            $results[$timeframe] = $this->calculateAndUpdate($timeframe);
        }

        return $results;
    }
}


