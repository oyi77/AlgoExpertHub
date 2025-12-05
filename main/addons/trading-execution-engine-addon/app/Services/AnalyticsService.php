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

    /**
     * Compare performance across multiple channels/connections.
     * 
     * @param array $connectionIds Array of connection IDs to compare
     * @param int $days Number of days to analyze
     * @return array Comparison data
     */
    public function compareChannels(array $connectionIds, int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days);
        $comparison = [];

        foreach ($connectionIds as $connectionId) {
            $connection = ExecutionConnection::find($connectionId);
            if (!$connection) {
                continue;
            }

            $summary = $this->getAnalyticsSummary($connection, $days);
            
            // Get additional metrics
            $positions = ExecutionPosition::closed()
                ->byConnection($connectionId)
                ->where('closed_at', '>=', $startDate)
                ->get();

            $sharpeRatio = $this->calculateSharpeRatio($connection, Carbon::today());
            $maxDrawdown = $summary['max_drawdown'] ?? 0;
            
            // Calculate average trade duration
            $avgDuration = 0;
            if ($positions->isNotEmpty()) {
                $durations = $positions->filter(function ($pos) {
                    return $pos->closed_at && $pos->created_at;
                })->map(function ($pos) {
                    return $pos->created_at->diffInHours($pos->closed_at);
                });
                $avgDuration = $durations->isNotEmpty() ? $durations->avg() : 0;
            }

            $comparison[] = [
                'connection_id' => $connectionId,
                'connection_name' => $connection->name,
                'exchange_name' => $connection->exchange_name,
                'total_trades' => $summary['total_trades'],
                'win_rate' => $summary['win_rate'],
                'profit_factor' => $summary['profit_factor'],
                'total_pnl' => $summary['total_pnl'],
                'sharpe_ratio' => round($sharpeRatio, 4),
                'max_drawdown' => $maxDrawdown,
                'average_trade_duration_hours' => round($avgDuration, 2),
                'balance' => $summary['balance'],
                'equity' => $summary['equity'],
            ];
        }

        // Sort by total PnL (descending)
        usort($comparison, function ($a, $b) {
            return $b['total_pnl'] <=> $a['total_pnl'];
        });

        return [
            'period_days' => $days,
            'start_date' => $startDate->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'channels' => $comparison,
            'best_performer' => !empty($comparison) ? $comparison[0] : null,
        ];
    }

    /**
     * Export analytics report to CSV.
     * 
     * @param ExecutionConnection $connection
     * @param int $days Number of days
     * @return string CSV content
     */
    public function exportToCsv(ExecutionConnection $connection, int $days = 30): string
    {
        $startDate = Carbon::today()->subDays($days);
        
        $positions = ExecutionPosition::closed()
            ->byConnection($connection->id)
            ->where('closed_at', '>=', $startDate)
            ->orderBy('closed_at', 'asc')
            ->with('signal')
            ->get();

        $summary = $this->getAnalyticsSummary($connection, $days);
        $sharpeRatio = $this->calculateSharpeRatio($connection, Carbon::today());

        // Build CSV
        $csv = [];
        $csv[] = "Analytics Report for: {$connection->name}";
        $csv[] = "Period: {$startDate->toDateString()} to " . Carbon::today()->toDateString();
        $csv[] = "";
        $csv[] = "Summary";
        $csv[] = "Total Trades," . $summary['total_trades'];
        $csv[] = "Winning Trades," . $summary['winning_trades'];
        $csv[] = "Losing Trades," . $summary['losing_trades'];
        $csv[] = "Win Rate," . $summary['win_rate'] . "%";
        $csv[] = "Total P&L," . $summary['total_pnl'];
        $csv[] = "Profit Factor," . $summary['profit_factor'];
        $csv[] = "Max Drawdown," . $summary['max_drawdown'] . "%";
        $csv[] = "Sharpe Ratio," . round($sharpeRatio, 4);
        $csv[] = "Balance," . $summary['balance'];
        $csv[] = "Equity," . $summary['equity'];
        $csv[] = "";
        $csv[] = "Trade Details";
        $csv[] = "Date,Signal ID,Symbol,Direction,Entry Price,Exit Price,Quantity,P&L,P&L %,Duration (hours),Close Reason";

        foreach ($positions as $position) {
            $duration = $position->closed_at && $position->created_at 
                ? $position->created_at->diffInHours($position->closed_at) 
                : 0;
            
            $csv[] = sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",
                $position->closed_at ? $position->closed_at->format('Y-m-d H:i:s') : '',
                $position->signal_id ?? '',
                $position->symbol ?? '',
                strtoupper($position->direction ?? ''),
                $position->entry_price ?? 0,
                $position->current_price ?? 0,
                $position->quantity ?? 0,
                $position->pnl ?? 0,
                $position->pnl_percentage ?? 0,
                $duration,
                $position->closed_reason ?? ''
            );
        }

        return implode("\n", $csv);
    }

    /**
     * Export analytics report to JSON.
     * 
     * @param ExecutionConnection $connection
     * @param int $days Number of days
     * @return array JSON data
     */
    public function exportToJson(ExecutionConnection $connection, int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days);
        
        $positions = ExecutionPosition::closed()
            ->byConnection($connection->id)
            ->where('closed_at', '>=', $startDate)
            ->orderBy('closed_at', 'asc')
            ->with('signal')
            ->get();

        $summary = $this->getAnalyticsSummary($connection, $days);
        $sharpeRatio = $this->calculateSharpeRatio($connection, Carbon::today());

        $trades = $positions->map(function ($position) {
            $duration = $position->closed_at && $position->created_at 
                ? $position->created_at->diffInHours($position->closed_at) 
                : 0;
            
            return [
                'date' => $position->closed_at ? $position->closed_at->toIso8601String() : null,
                'signal_id' => $position->signal_id,
                'symbol' => $position->symbol,
                'direction' => $position->direction,
                'entry_price' => (float) $position->entry_price,
                'exit_price' => (float) ($position->current_price ?? $position->entry_price),
                'quantity' => (float) $position->quantity,
                'pnl' => (float) $position->pnl,
                'pnl_percentage' => (float) $position->pnl_percentage,
                'duration_hours' => $duration,
                'close_reason' => $position->closed_reason,
            ];
        })->toArray();

        return [
            'connection' => [
                'id' => $connection->id,
                'name' => $connection->name,
                'exchange_name' => $connection->exchange_name,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
                'days' => $days,
            ],
            'summary' => array_merge($summary, [
                'sharpe_ratio' => round($sharpeRatio, 4),
            ]),
            'trades' => $trades,
        ];
    }
}

