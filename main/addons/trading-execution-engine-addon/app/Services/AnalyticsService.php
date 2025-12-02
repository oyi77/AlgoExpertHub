<?php

namespace Addons\TradingExecutionEngine\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionAnalytic;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    /**
     * Calculate and update analytics for a connection.
     */
    public function calculateAnalytics(ExecutionConnection $connection, Carbon $date = null): ExecutionAnalytic
    {
        $date = $date ?? Carbon::today();

        // Get closed positions for this date
        $closedPositions = ExecutionPosition::closed()
            ->byConnection($connection->id)
            ->whereDate('closed_at', $date)
            ->get();

        $totalTrades = $closedPositions->count();
        $winningTrades = $closedPositions->where('pnl', '>', 0)->count();
        $losingTrades = $closedPositions->where('pnl', '<', 0)->count();
        $totalPnL = $closedPositions->sum('pnl');

        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

        // Calculate profit factor
        $totalProfit = $closedPositions->where('pnl', '>', 0)->sum('pnl');
        $totalLoss = abs($closedPositions->where('pnl', '<', 0)->sum('pnl'));
        $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : ($totalProfit > 0 ? 999 : 0);

        // Get balance and equity from connection adapter
        $balance = 0;
        $equity = 0;
        try {
            $connectionService = app(ConnectionService::class);
            $adapter = $connectionService->getAdapter($connection);
            if ($adapter) {
                $balanceData = $adapter->getBalance();
                $balance = $balanceData['balance'] ?? 0;
                $equity = $balanceData['equity'] ?? 0;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to get balance for analytics", [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Calculate max drawdown (simplified - can be enhanced)
        $maxDrawdown = $this->calculateMaxDrawdown($connection, $date);

        // Additional metrics
        $additionalMetrics = [
            'sharpe_ratio' => $this->calculateSharpeRatio($connection, $date),
            'expectancy' => $this->calculateExpectancy($closedPositions),
            'average_win' => $winningTrades > 0 ? $closedPositions->where('pnl', '>', 0)->avg('pnl') : 0,
            'average_loss' => $losingTrades > 0 ? abs($closedPositions->where('pnl', '<', 0)->avg('pnl')) : 0,
        ];

        // Update or create analytics record
        $analytic = ExecutionAnalytic::updateOrCreate(
            [
                'connection_id' => $connection->id,
                'date' => $date,
            ],
            [
                'user_id' => $connection->user_id,
                'admin_id' => $connection->admin_id,
                'total_trades' => $totalTrades,
                'winning_trades' => $winningTrades,
                'losing_trades' => $losingTrades,
                'total_pnl' => $totalPnL,
                'win_rate' => $winRate,
                'profit_factor' => $profitFactor,
                'max_drawdown' => $maxDrawdown,
                'balance' => $balance,
                'equity' => $equity,
                'additional_metrics' => $additionalMetrics,
            ]
        );

        return $analytic;
    }

    /**
     * Calculate max drawdown for a connection.
     */
    protected function calculateMaxDrawdown(ExecutionConnection $connection, Carbon $date): float
    {
        // Get all closed positions up to this date
        $positions = ExecutionPosition::closed()
            ->byConnection($connection->id)
            ->whereDate('closed_at', '<=', $date)
            ->orderBy('closed_at', 'asc')
            ->get();

        if ($positions->isEmpty()) {
            return 0;
        }

        $cumulativePnL = 0;
        $peak = 0;
        $maxDrawdown = 0;

        foreach ($positions as $position) {
            $cumulativePnL += $position->pnl;
            
            if ($cumulativePnL > $peak) {
                $peak = $cumulativePnL;
            }

            $drawdown = $peak - $cumulativePnL;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }

        // Return as percentage if peak > 0
        return $peak > 0 ? ($maxDrawdown / $peak) * 100 : 0;
    }

    /**
     * Calculate Sharpe ratio (simplified).
     */
    protected function calculateSharpeRatio(ExecutionConnection $connection, Carbon $date): float
    {
        // Get returns for the last 30 days
        $startDate = $date->copy()->subDays(30);
        $positions = ExecutionPosition::closed()
            ->byConnection($connection->id)
            ->whereBetween('closed_at', [$startDate, $date])
            ->get();

        if ($positions->count() < 2) {
            return 0;
        }

        $returns = $positions->pluck('pnl_percentage')->toArray();
        $mean = array_sum($returns) / count($returns);
        
        $variance = 0;
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        $stdDev = sqrt($variance / count($returns));

        return $stdDev > 0 ? $mean / $stdDev : 0;
    }

    /**
     * Calculate expectancy.
     */
    protected function calculateExpectancy($positions): float
    {
        if ($positions->isEmpty()) {
            return 0;
        }

        $winRate = $positions->where('pnl', '>', 0)->count() / $positions->count();
        $avgWin = $positions->where('pnl', '>', 0)->avg('pnl') ?? 0;
        $avgLoss = abs($positions->where('pnl', '<', 0)->avg('pnl') ?? 0);

        return ($winRate * $avgWin) - ((1 - $winRate) * $avgLoss);
    }

    /**
     * Get analytics summary for a connection.
     */
    public function getAnalyticsSummary(ExecutionConnection $connection, int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days);
        
        $analytics = ExecutionAnalytic::byConnection($connection->id)
            ->where('date', '>=', $startDate)
            ->get();

        if ($analytics->isEmpty()) {
            return $this->getEmptySummary();
        }

        $totalTrades = $analytics->sum('total_trades');
        $winningTrades = $analytics->sum('winning_trades');
        $losingTrades = $analytics->sum('losing_trades');
        $totalPnL = $analytics->sum('total_pnl');
        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

        $totalProfit = ExecutionPosition::closed()
            ->byConnection($connection->id)
            ->where('closed_at', '>=', $startDate)
            ->where('pnl', '>', 0)
            ->sum('pnl');

        $totalLoss = abs(ExecutionPosition::closed()
            ->byConnection($connection->id)
            ->where('closed_at', '>=', $startDate)
            ->where('pnl', '<', 0)
            ->sum('pnl'));

        $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : ($totalProfit > 0 ? 999 : 0);

        $maxDrawdown = $analytics->max('max_drawdown') ?? 0;

        // Get latest balance
        $latestAnalytic = $analytics->sortByDesc('date')->first();
        $balance = $latestAnalytic->balance ?? 0;
        $equity = $latestAnalytic->equity ?? 0;

        return [
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTrades,
            'losing_trades' => $losingTrades,
            'total_pnl' => $totalPnL,
            'win_rate' => round($winRate, 2),
            'profit_factor' => round($profitFactor, 4),
            'max_drawdown' => round($maxDrawdown, 2),
            'balance' => $balance,
            'equity' => $equity,
            'period_days' => $days,
        ];
    }

    /**
     * Get empty summary.
     */
    protected function getEmptySummary(): array
    {
        return [
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'total_pnl' => 0,
            'win_rate' => 0,
            'profit_factor' => 0,
            'max_drawdown' => 0,
            'balance' => 0,
            'equity' => 0,
            'period_days' => 0,
        ];
    }

    /**
     * Update analytics for all connections (scheduled job).
     */
    public function updateAllAnalytics(): void
    {
        $connections = ExecutionConnection::active()->get();

        foreach ($connections as $connection) {
            try {
                $this->calculateAnalytics($connection);
            } catch (\Exception $e) {
                Log::error("Failed to update analytics for connection", [
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

