<?php

namespace Addons\CopyTrading\App\Services;

use Addons\CopyTrading\App\Models\CopyTradingExecution;
use Addons\CopyTrading\App\Models\CopyTradingSetting;
use Addons\CopyTrading\App\Models\CopyTradingSubscription;
use App\Support\AddonRegistry;
use Illuminate\Support\Facades\DB;

class CopyTradingAnalyticsService
{
    /**
     * Get trader statistics.
     */
    public function getTraderStats(?int $traderId = null, ?int $adminId = null): array
    {
        $setting = null;
        if ($adminId) {
            $setting = CopyTradingSetting::byAdmin($adminId)->first();
        } elseif ($traderId) {
            $setting = CopyTradingSetting::byUser($traderId)->first();
        }
        
        if (!$setting) {
            return [
                'is_enabled' => false,
                'follower_count' => 0,
                'total_copied_trades' => 0,
                'win_rate' => 0,
                'total_pnl' => 0,
                'total_trades' => 0,
            ];
        }

        $followerCount = 0;
        if ($traderId) {
            $followerCount = CopyTradingSubscription::byTrader($traderId)
                ->active()
                ->count();
        }

        // Get trader's positions (only if trading execution engine is active)
        $totalTrades = 0;
        $winRate = 0;
        $totalPnL = 0;
        
        if (AddonRegistry::active('trading-execution-engine-addon') && class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class)) {
            $traderPositions = \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::whereHas('connection', function ($query) use ($traderId, $adminId) {
            if ($adminId) {
                $query->where('admin_id', $adminId)->where('is_admin_owned', true);
            } else {
                $query->where('user_id', $traderId)->where('is_admin_owned', false);
            }
        })
            ->get();

        $totalTrades = $traderPositions->count();
        $closedPositions = $traderPositions->filter(function ($position) {
            return $position->status === 'closed';
        });
        $winningTrades = $closedPositions->filter(function ($position) {
            return $position->pnl > 0;
        })->count();
        $totalTradesForWinRate = $closedPositions->count();
        
        $winRate = $totalTradesForWinRate > 0 
            ? ($winningTrades / $totalTradesForWinRate) * 100 
            : 0;

        $totalPnL = $closedPositions->sum(function ($position) {
            return $position->pnl ?? 0;
        });
        }

        $totalCopied = 0;
        if ($traderId) {
            $totalCopied = CopyTradingExecution::byTrader($traderId)
                ->executed()
                ->count();
        }

        return [
            'is_enabled' => $setting->isEnabled(),
            'follower_count' => $followerCount,
            'total_copied_trades' => $totalCopied,
            'win_rate' => round($winRate, 2),
            'total_pnl' => round($totalPnL, 2),
            'total_trades' => $totalTrades,
        ];
    }

    /**
     * Get follower statistics for a specific trader.
     */
    public function getFollowerStats(int $followerId, int $traderId): array
    {
        $executions = CopyTradingExecution::where('follower_id', $followerId)
            ->where('trader_id', $traderId)
            ->executed()
            ->with(['followerPosition'])
            ->get();

        $totalCopied = $executions->count();
        
        $closedPositions = $executions->filter(function ($execution) {
            return $execution->followerPosition && $execution->followerPosition->isClosed();
        });

        $winningTrades = $closedPositions->filter(function ($execution) {
            return $execution->followerPosition->pnl > 0;
        })->count();

        $totalForWinRate = $closedPositions->count();
        $winRate = $totalForWinRate > 0 
            ? ($winningTrades / $totalForWinRate) * 100 
            : 0;

        $totalPnL = $closedPositions->sum(function ($execution) {
            return $execution->followerPosition->pnl ?? 0;
        });

        return [
            'total_copied' => $totalCopied,
            'win_rate' => round($winRate, 2),
            'total_pnl' => round($totalPnL, 2),
        ];
    }

    /**
     * Get overall copy trading statistics.
     */
    public function getOverallStats(): array
    {
        $activeTraders = CopyTradingSetting::enabled()->count();
        $activeSubscriptions = CopyTradingSubscription::active()->count();
        $totalExecutions = CopyTradingExecution::executed()->count();

        return [
            'active_traders' => $activeTraders,
            'active_subscriptions' => $activeSubscriptions,
            'total_executions' => $totalExecutions,
        ];
    }

    /**
     * Get system-wide statistics for admin dashboard.
     */
    public function getSystemStats(): array
    {
        $totalTraders = CopyTradingSetting::enabled()->count();
        $totalSubscriptions = CopyTradingSubscription::count();
        $activeSubscriptions = CopyTradingSubscription::active()->count();
        $totalExecutions = CopyTradingExecution::count();
        $successfulExecutions = CopyTradingExecution::executed()->count();
        $failedExecutions = CopyTradingExecution::where('status', 'failed')->count();

        // Calculate unique followers
        $activeFollowers = CopyTradingSubscription::active()->distinct('follower_id')->count('follower_id');

        return [
            'total_traders' => $totalTraders,
            'total_subscriptions' => $totalSubscriptions,
            'active_subscriptions' => $activeSubscriptions,
            'total_executions' => $totalExecutions,
            'successful_executions' => $successfulExecutions,
            'failed_executions' => $failedExecutions,
            'active_followers' => $activeFollowers,
        ];
    }

    /**
     * Get execution chart data for analytics.
     */
    public function getExecutionChartData(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $executionsByDay = CopyTradingExecution::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "executed" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $totalData = [];
        $successData = [];
        $failedData = [];

        foreach ($executionsByDay as $row) {
            $labels[] = date('M d', strtotime($row->date));
            $totalData[] = $row->total;
            $successData[] = $row->successful;
            $failedData[] = $row->failed;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Executions',
                    'data' => $totalData,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                ],
                [
                    'label' => 'Successful',
                    'data' => $successData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                ],
                [
                    'label' => 'Failed',
                    'data' => $failedData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                ],
            ],
        ];
    }

    /**
     * Get top traders by follower count.
     */
    public function getTopTraders(int $limit = 10)
    {
        $traders = CopyTradingSetting::enabled()
            ->with(['user', 'admin'])
            ->get();

        $tradersWithStats = $traders->map(function ($trader) {
            $followerCount = 0;
            $traderId = null;
            $traderName = 'Unknown';

            if ($trader->is_admin_owned) {
                $adminId = $trader->admin_id;
                $admin = $trader->admin;
                $traderName = $admin ? ($admin->name ?? $admin->username ?? $admin->email ?? 'Admin #' . $adminId) : 'Admin #' . $adminId;
                // For admin traders, count subscriptions is complex, skip for now
            } else {
                $traderId = $trader->user_id;
                $user = $trader->user;
                $traderName = $user ? ($user->username ?? $user->email ?? 'User #' . $traderId) : 'User #' . $traderId;
                $followerCount = CopyTradingSubscription::byTrader($traderId)->active()->count();
            }

            $stats = $this->getTraderStats($traderId, $trader->is_admin_owned ? $trader->admin_id : null);

            return (object) [
                'id' => $trader->id,
                'trader_id' => $traderId ?? $trader->admin_id,
                'trader_name' => $traderName,
                'trader_type' => $trader->is_admin_owned ? 'admin' : 'user',
                'follower_count' => $followerCount,
                'win_rate' => $stats['win_rate'],
                'total_pnl' => $stats['total_pnl'],
                'total_trades' => $stats['total_trades'],
            ];
        });

        return $tradersWithStats->sortByDesc('follower_count')->take($limit)->values();
    }
}

