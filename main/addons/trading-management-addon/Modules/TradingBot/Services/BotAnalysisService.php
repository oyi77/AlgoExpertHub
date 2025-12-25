<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BotAnalysisService
 * 
 * Calculate and aggregate bot performance metrics
 */
class BotAnalysisService
{
    /**
     * Calculate performance metrics for a bot
     * 
     * @param TradingBot $bot
     * @param array $filters ['date_from' => string, 'date_to' => string, 'period' => string]
     * @return array
     */
    public function calculateMetrics(TradingBot $bot, array $filters = []): array
    {
        try {
            // Get positions for the bot
            $positionsQuery = TradingBotPosition::forBot($bot->id);

            // Apply date filters
            if (isset($filters['date_from'])) {
                $positionsQuery->where('opened_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $positionsQuery->where('opened_at', '<=', $filters['date_to']);
            }

            $positions = $positionsQuery->get();
            $closedPositions = $positions->where('status', 'closed');
            $openPositions = $positions->where('status', 'open');

            // Basic metrics
            $totalTrades = $closedPositions->count();
            $winningTrades = $closedPositions->where('profit_loss', '>', 0)->count();
            $losingTrades = $closedPositions->where('profit_loss', '<', 0)->count();
            $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

            // Profit/Loss metrics
            $totalProfit = $closedPositions->where('profit_loss', '>', 0)->sum('profit_loss');
            $totalLoss = abs($closedPositions->where('profit_loss', '<', 0)->sum('profit_loss'));
            $netProfit = $closedPositions->sum('profit_loss');
            $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : ($totalProfit > 0 ? 999 : 0);

            // Average profit per trade
            $avgProfitPerTrade = $totalTrades > 0 ? $netProfit / $totalTrades : 0;

            // Best and worst trades
            $bestTrade = $closedPositions->max('profit_loss');
            $worstTrade = $closedPositions->min('profit_loss');

            // Drawdown calculation
            $maxDrawdown = $this->calculateMaxDrawdown($closedPositions);

            // Sharpe ratio (simplified calculation)
            $sharpeRatio = $this->calculateSharpeRatio($closedPositions);

            return [
                'metrics' => [
                    'total_trades' => $totalTrades,
                    'winning_trades' => $winningTrades,
                    'losing_trades' => $losingTrades,
                    'win_rate' => round($winRate, 2),
                    'total_profit' => round($totalProfit, 2),
                    'total_loss' => round($totalLoss, 2),
                    'net_profit' => round($netProfit, 2),
                    'profit_factor' => round($profitFactor, 2),
                    'average_profit_per_trade' => round($avgProfitPerTrade, 2),
                    'best_trade' => round($bestTrade ?? 0, 2),
                    'worst_trade' => round($worstTrade ?? 0, 2),
                    'max_drawdown' => round($maxDrawdown, 2),
                    'sharpe_ratio' => round($sharpeRatio, 4),
                ],
                'positions' => [
                    'open' => $openPositions->count(),
                    'closed' => $closedPositions->count(),
                    'total' => $positions->count(),
                ],
                'current_pnl' => round($openPositions->sum('profit_loss'), 2),
            ];
        } catch (\Exception $e) {
            Log::error('BotAnalysisService::calculateMetrics error', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'metrics' => [],
                'positions' => ['open' => 0, 'closed' => 0, 'total' => 0],
                'current_pnl' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get performance chart data
     * 
     * @param TradingBot $bot
     * @param string $period 'daily', 'weekly', 'monthly'
     * @return array
     */
    public function getPerformanceChart(TradingBot $bot, string $period = 'daily'): array
    {
        try {
            $positions = TradingBotPosition::forBot($bot->id)
                ->where('status', 'closed')
                ->orderBy('closed_at', 'asc')
                ->get();

            $chartData = [];
            $cumulativePnL = 0;

            foreach ($positions as $position) {
                $cumulativePnL += $position->profit_loss ?? 0;

                $date = $position->closed_at ? $position->closed_at->format('Y-m-d') : null;
                if (!$date) {
                    continue;
                }

                // Group by period
                $groupKey = $this->getPeriodKey($date, $period);

                if (!isset($chartData[$groupKey])) {
                    $chartData[$groupKey] = [
                        'date' => $groupKey,
                        'profit' => 0,
                        'loss' => 0,
                        'net' => 0,
                        'trades' => 0,
                        'cumulative_pnl' => 0,
                    ];
                }

                $chartData[$groupKey]['trades']++;
                if ($position->profit_loss > 0) {
                    $chartData[$groupKey]['profit'] += $position->profit_loss;
                } else {
                    $chartData[$groupKey]['loss'] += abs($position->profit_loss);
                }
                $chartData[$groupKey]['net'] += $position->profit_loss;
                $chartData[$groupKey]['cumulative_pnl'] = $cumulativePnL;
            }

            // Convert to array and sort by date
            $result = array_values($chartData);
            usort($result, function ($a, $b) {
                return strcmp($a['date'], $b['date']);
            });

            return $result;
        } catch (\Exception $e) {
            Log::error('BotAnalysisService::getPerformanceChart error', [
                'bot_id' => $bot->id,
                'period' => $period,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Compare multiple bots
     * 
     * @param array $botIds
     * @param array $filters
     * @return array
     */
    public function compareBots(array $botIds, array $filters = []): array
    {
        $comparison = [];

        foreach ($botIds as $botId) {
            $bot = TradingBot::find($botId);
            if (!$bot) {
                continue;
            }

            $metrics = $this->calculateMetrics($bot, $filters);
            $comparison[] = [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'metrics' => $metrics['metrics'],
                'positions' => $metrics['positions'],
            ];
        }

        return $comparison;
    }

    /**
     * Export analysis data
     * 
     * @param TradingBot $bot
     * @param string $format 'csv' or 'json'
     * @return string
     */
    public function exportAnalysis(TradingBot $bot, string $format = 'csv'): string
    {
        $metrics = $this->calculateMetrics($bot);
        $chartData = $this->getPerformanceChart($bot);

        if ($format === 'json') {
            return json_encode([
                'bot' => [
                    'id' => $bot->id,
                    'name' => $bot->name,
                ],
                'metrics' => $metrics,
                'chart_data' => $chartData,
                'exported_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT);
        }

        // CSV format
        $csv = "Bot Analysis Export\n";
        $csv .= "Bot: {$bot->name} (ID: {$bot->id})\n";
        $csv .= "Exported: " . now()->toDateTimeString() . "\n\n";
        $csv .= "Metrics\n";
        foreach ($metrics['metrics'] as $key => $value) {
            $csv .= str_replace('_', ' ', ucwords($key, '_')) . ": {$value}\n";
        }
        $csv .= "\nPerformance Chart\n";
        $csv .= "Date,Profit,Loss,Net,Trades,Cumulative PnL\n";
        foreach ($chartData as $row) {
            $csv .= "{$row['date']},{$row['profit']},{$row['loss']},{$row['net']},{$row['trades']},{$row['cumulative_pnl']}\n";
        }

        return $csv;
    }

    /**
     * Calculate maximum drawdown
     * 
     * @param \Illuminate\Support\Collection $positions
     * @return float
     */
    protected function calculateMaxDrawdown($positions): float
    {
        if ($positions->isEmpty()) {
            return 0;
        }

        $cumulativePnL = 0;
        $peak = 0;
        $maxDrawdown = 0;

        foreach ($positions->sortBy('closed_at') as $position) {
            $cumulativePnL += $position->profit_loss ?? 0;

            if ($cumulativePnL > $peak) {
                $peak = $cumulativePnL;
            }

            $drawdown = $peak - $cumulativePnL;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }

        return $maxDrawdown;
    }

    /**
     * Calculate Sharpe ratio (simplified)
     * 
     * @param \Illuminate\Support\Collection $positions
     * @return float
     */
    protected function calculateSharpeRatio($positions): float
    {
        if ($positions->count() < 2) {
            return 0;
        }

        $returns = $positions->pluck('profit_loss')->toArray();
        $mean = array_sum($returns) / count($returns);

        $variance = 0;
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        $stdDev = sqrt($variance / count($returns));

        if ($stdDev == 0) {
            return 0;
        }

        // Risk-free rate assumed to be 0 for simplicity
        return $mean / $stdDev;
    }

    /**
     * Get period key for grouping
     * 
     * @param string $date
     * @param string $period
     * @return string
     */
    protected function getPeriodKey(string $date, string $period): string
    {
        $timestamp = strtotime($date);

        switch ($period) {
            case 'weekly':
                return date('Y-W', $timestamp);
            case 'monthly':
                return date('Y-m', $timestamp);
            case 'daily':
            default:
                return $date;
        }
    }
}

