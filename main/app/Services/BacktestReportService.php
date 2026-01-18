<?php

namespace App\Services;

use App\Models\Backtest;
use App\Models\BacktestReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BacktestReportService
{
    /**
     * Generate aggregated backtesting performance reports
     * 
     * Provides summary statistics and detailed breakdowns from individual backtests
     */

    /**
     * Generate summary report for a backtest
     *
     * @param Backtest $backtest
     * @return array Report data
     */
    public function generateSummaryReport(Backtest $backtest): array
    {
        return [
            'report_type' => 'summary',
            'backtest_id' => $backtest->id,
            'backtest_name' => $backtest->name,
            'symbol' => $backtest->symbol,
            'timeframe' => $backtest->timeframe,
            'start_date' => $backtest->start_date,
            'end_date' => $backtest->end_date,
            'initial_balance' => $backtest->initial_balance,
            'final_balance' => $backtest->final_balance,
            'total_return' => $backtest->total_return,
            'total_return_percent' => $backtest->total_return_percent,
            'total_trades' => $backtest->total_trades,
            'winning_trades' => $backtest->winning_trades,
            'losing_trades' => $backtest->losing_trades,
            'win_rate' => $backtest->win_rate,
            'max_drawdown' => $backtest->max_drawdown,
            'profit_factor' => $backtest->profit_factor,
            'average_win' => $backtest->average_win,
            'average_loss' => $backtest->average_loss,
            'best_win_streak' => $backtest->best_win_streak,
            'worst_loss_streak' => $backtest->worst_loss_streak,
            'status' => $backtest->status,
            'completed_at' => $backtest->completed_at,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Generate detailed trade breakdown report
     *
     * @param Backtest $backtest
     * @return array Report data with trade details
     */
    public function generateTradeBreakdownReport(Backtest $backtest): array
    {
        $trades = $backtest->trades()->get();

        return [
            'report_type' => 'trade_breakdown',
            'backtest_id' => $backtest->id,
            'backtest_name' => $backtest->name,
            'symbol' => $backtest->symbol,
            'timeframe' => $backtest->timeframe,
            'total_trades' => count($trades),
            'trades' => $trades->map(function ($trade) {
                return [
                    'id' => $trade->id,
                    'signal_time' => $trade->signal_time->format('Y-m-d H:i:s'),
                    'entry_time' => $trade->entry_time->format('Y-m-d H:i:s'),
                    'exit_time' => $trade->exit_time->format('Y-m-d H:i:s'),
                    'direction' => $trade->direction,
                    'entry_price' => (float) $trade->entry_price,
                    'exit_price' => (float) $trade->exit_price,
                    'quantity' => $trade->quantity,
                    'stop_loss' => $trade->stop_loss,
                    'take_profit' => $trade->take_profit,
                    'profit_loss' => (float) $trade->profit_loss,
                    'spread_cost' => (float) $trade->spread_cost,
                    'profit_loss_percent' => $trade->profit_loss_percent,
                    'status' => $trade->status,
                ];
            }),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Generate performance comparison report
     *
     * Compares multiple backtests for the same symbol/timeframe
     *
     * @param int $userId
     * @param array $backtestIds
     * @return array Comparison report
     */
    public function generateComparisonReport(int $userId, array $backtestIds): array
    {
        $backtests = Backtest::whereIn('id', $backtestIds)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($backtests->isEmpty()) {
            return [
                'report_type' => 'comparison',
                'backtests' => [],
                'comparison' => [],
                'generated_at' => now()->toDateTimeString(),
            ];
        }

        $avgReturn = $backtests->avg('total_return');
        $avgWinRate = $backtests->avg('win_rate');
        $avgDrawdown = $backtests->avg('max_drawdown');
        $avgProfitFactor = $backtests->avg('profit_factor');

        return [
            'report_type' => 'comparison',
            'backtests' => $backtests->map(function ($backtest) {
                return [
                    'id' => $backtest->id,
                    'name' => $backtest->name,
                    'symbol' => $backtest->symbol,
                    'timeframe' => $backtest->timeframe,
                    'total_return' => $backtest->total_return,
                    'total_return_percent' => $backtest->total_return_percent,
                    'total_trades' => $backtest->total_trades,
                    'winning_trades' => $backtest->winning_trades,
                    'losing_trades' => $backtest->losing_trades,
                    'win_rate' => $backtest->win_rate,
                    'max_drawdown' => $backtest->max_drawdown,
                    'profit_factor' => $backtest->profit_factor,
                    'completed_at' => $backtest->completed_at,
                ];
            }),
            'comparison' => [
                'avg_total_return' => $avgReturn,
                'avg_win_rate' => $avgWinRate,
                'avg_max_drawdown' => $avgDrawdown,
                'avg_profit_factor' => $avgProfitFactor,
                'best_backtest_id' => $backtests->sortByDesc('win_rate')->first()->id,
                'worst_backtest_id' => $backtests->sortBy('win_rate')->first()->id,
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get reports for a user
     *
     * @param int $userId
     * @param string $reportType
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserReports(int $userId, string $reportType = null, int $limit = 20)
    {
        $query = BacktestReport::where('user_id', $userId);

        if ($reportType) {
            $query->where('report_type', $reportType);
        }

        return $query->orderBy('generated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Save generated report to database
     *
     * @param array $reportData
     * @return BacktestReport
     */
    public function saveReport(array $reportData): BacktestReport
    {
        return BacktestReport::create([
            'user_id' => auth()->id(),
            'backtest_id' => $reportData['backtest_id'] ?? null,
            'report_type' => $reportData['report_type'] ?? 'summary',
            'name' => $reportData['name'] ?? 'Backtest Report',
            'description' => $reportData['description'] ?? '',
            'data' => $reportData,
            'generated_at' => now(),
        ]);
    }

    /**
     * Delete a report
     *
     * @param int $reportId
     * @return bool
     */
    public function deleteReport(int $reportId): bool
    {
        $report = BacktestReport::with('backtest')->find($reportId);
        
        if (!$report) {
            return false;
        }

        return $report->delete();
    }
}
