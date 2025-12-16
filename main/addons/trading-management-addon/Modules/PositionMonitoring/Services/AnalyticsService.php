<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Services;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionAnalytic;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * AnalyticsService
 * 
 * Calculates and stores analytics for execution connections
 */
class AnalyticsService
{
    /**
     * Calculate and update analytics for a connection.
     * 
     * @param ExecutionConnection $connection
     * @param Carbon|null $date
     * @return ExecutionAnalytic
     */
    public function calculateAnalytics(ExecutionConnection $connection, Carbon $date = null): ExecutionAnalytic
    {
        $date = $date ?? Carbon::yesterday(); // Calculate for yesterday by default

        // Get closed positions for this date
        $closedPositions = ExecutionPosition::closed()
            ->where('connection_id', $connection->id)
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

        // Calculate max drawdown
        $maxDrawdown = $this->calculateMaxDrawdown($connection, $date);

        // Get balance and equity from connection adapter (if available)
        $balance = 0;
        $equity = 0;
        try {
            // Try to get balance from adapter if connection has adapter
            // This is optional and may not be available for all connection types
            if (method_exists($connection, 'getAdapter')) {
                $adapter = $connection->getAdapter();
                if ($adapter && method_exists($adapter, 'getBalance')) {
                    $balanceData = $adapter->getBalance();
                    $balance = $balanceData['balance'] ?? 0;
                    $equity = $balanceData['equity'] ?? 0;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to get balance for analytics", [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
        }

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

        Log::info('Analytics calculated for connection', [
            'connection_id' => $connection->id,
            'date' => $date->toDateString(),
            'total_trades' => $totalTrades,
            'win_rate' => round($winRate, 2),
            'profit_factor' => round($profitFactor, 4),
        ]);

        return $analytic;
    }

    /**
     * Calculate max drawdown for a connection.
     * 
     * @param ExecutionConnection $connection
     * @param Carbon $date
     * @return float
     */
    protected function calculateMaxDrawdown(ExecutionConnection $connection, Carbon $date): float
    {
        // Get all closed positions up to this date
        $positions = ExecutionPosition::closed()
            ->where('connection_id', $connection->id)
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
            $cumulativePnL += $position->pnl ?? 0;
            
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
     * 
     * @param ExecutionConnection $connection
     * @param Carbon $date
     * @return float
     */
    protected function calculateSharpeRatio(ExecutionConnection $connection, Carbon $date): float
    {
        // Get returns for the last 30 days
        $startDate = $date->copy()->subDays(30);
        $positions = ExecutionPosition::closed()
            ->where('connection_id', $connection->id)
            ->whereBetween('closed_at', [$startDate, $date])
            ->get();

        if ($positions->count() < 2) {
            return 0;
        }

        // Use pnl_percentage if available, otherwise calculate from pnl
        $returns = $positions->map(function ($position) {
            if (isset($position->pnl_percentage) && $position->pnl_percentage !== null) {
                return (float) $position->pnl_percentage;
            }
            // Fallback: calculate percentage from pnl (simplified)
            return 0; // Can't calculate without entry price context
        })->filter(function ($return) {
            return $return !== null && $return !== 0;
        })->toArray();

        if (count($returns) < 2) {
            return 0;
        }

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
     * 
     * @param \Illuminate\Database\Eloquent\Collection $positions
     * @return float
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
     * Update analytics for all active connections.
     * 
     * @param Carbon|null $date
     * @return void
     */
    public function updateAllAnalytics(Carbon $date = null): void
    {
        $connections = ExecutionConnection::where('is_active', true)
            ->where('status', 'active')
            ->get();

        foreach ($connections as $connection) {
            try {
                $this->calculateAnalytics($connection, $date);
            } catch (\Exception $e) {
                Log::error("Failed to update analytics for connection", [
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}

