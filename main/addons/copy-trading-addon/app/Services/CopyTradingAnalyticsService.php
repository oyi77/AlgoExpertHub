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
}

