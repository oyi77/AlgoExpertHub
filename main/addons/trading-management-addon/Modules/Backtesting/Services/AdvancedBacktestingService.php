<?php

namespace Addons\TradingManagement\Modules\Backtesting\Services;

use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult;
use Addons\TradingManagement\Modules\Backtesting\Services\BacktestEngine;
use Illuminate\Support\Facades\Log;

/**
 * Advanced Backtesting Service
 * 
 * Provides advanced backtesting features:
 * - Walk-forward optimization
 * - Monte Carlo simulation
 * - Drawdown analysis
 * - Performance metrics (Sharpe, Sortino, Calmar ratios)
 * - Maximum Adverse Excursion (MAE) / Maximum Favorable Excursion (MFE)
 */
class AdvancedBacktestingService
{
    protected BacktestEngine $backtestEngine;

    public function __construct(BacktestEngine $backtestEngine)
    {
        $this->backtestEngine = $backtestEngine;
    }

    /**
     * Run walk-forward optimization
     * 
     * Divides data into in-sample (optimization) and out-of-sample (validation) periods
     * Rolls forward through time to test strategy robustness
     * 
     * @param Backtest $backtest Base backtest configuration
     * @param array $params [
     *   'in_sample_percent' => float (default 0.7),
     *   'out_of_sample_percent' => float (default 0.3),
     *   'step_size_percent' => float (default 0.1),
     *   'optimization_params' => array (parameters to optimize)
     * ]
     * @return array Walk-forward results
     */
    public function runWalkForwardOptimization(Backtest $backtest, array $params = []): array
    {
        $inSamplePercent = (float) ($params['in_sample_percent'] ?? 0.7);
        $outOfSamplePercent = (float) ($params['out_of_sample_percent'] ?? 0.3);
        $stepSizePercent = (float) ($params['step_size_percent'] ?? 0.1);
        $optimizationParams = $params['optimization_params'] ?? [];

        // Calculate date ranges
        $totalDays = $backtest->start_date->diffInDays($backtest->end_date);
        $inSampleDays = (int) ($totalDays * $inSamplePercent);
        $outOfSampleDays = (int) ($totalDays * $outOfSamplePercent);
        $stepDays = (int) ($totalDays * $stepSizePercent);

        $results = [];
        $currentStart = $backtest->start_date->copy();

        while ($currentStart->copy()->addDays($inSampleDays + $outOfSampleDays)->lte($backtest->end_date)) {
            $inSampleEnd = $currentStart->copy()->addDays($inSampleDays);
            $outOfSampleStart = $inSampleEnd->copy();
            $outOfSampleEnd = $outOfSampleStart->copy()->addDays($outOfSampleDays);

            // Optimize on in-sample period
            $optimizedParams = $this->optimizeParameters(
                $backtest,
                $currentStart,
                $inSampleEnd,
                $optimizationParams
            );

            // Test on out-of-sample period
            $testBacktest = $backtest->replicate();
            $testBacktest->start_date = $outOfSampleStart;
            $testBacktest->end_date = $outOfSampleEnd;
            
            // Apply optimized parameters
            foreach ($optimizedParams as $key => $value) {
                $testBacktest->setAttribute($key, $value);
            }

            $result = $this->backtestEngine->run($testBacktest);
            
            $results[] = [
                'period' => [
                    'in_sample' => [
                        'start' => $currentStart->toDateString(),
                        'end' => $inSampleEnd->toDateString(),
                    ],
                    'out_of_sample' => [
                        'start' => $outOfSampleStart->toDateString(),
                        'end' => $outOfSampleEnd->toDateString(),
                    ],
                ],
                'optimized_params' => $optimizedParams,
                'result' => $this->extractMetrics($result),
            ];

            // Move forward
            $currentStart->addDays($stepDays);
        }

        // Aggregate results
        return [
            'walk_forward_results' => $results,
            'summary' => $this->aggregateWalkForwardResults($results),
        ];
    }

    /**
     * Run Monte Carlo simulation
     * 
     * Randomly shuffles trade sequence to test strategy robustness
     * 
     * @param BacktestResult $baseResult Base backtest result
     * @param int $iterations Number of Monte Carlo runs (default 1000)
     * @return array Monte Carlo results
     */
    public function runMonteCarloSimulation(BacktestResult $baseResult, int $iterations = 1000): array
    {
        // Extract trades from base result
        $trades = $this->extractTrades($baseResult);
        
        if (empty($trades)) {
            return [
                'error' => 'No trades found in base result',
            ];
        }

        $simulations = [];
        $initialBalance = (float) ($baseResult->initial_balance ?? 10000);

        for ($i = 0; $i < $iterations; $i++) {
            // Randomly shuffle trade sequence
            $shuffledTrades = $trades;
            shuffle($shuffledTrades);
            
            // Simulate with shuffled trades
            $simulation = $this->simulateTrades($shuffledTrades, $initialBalance);
            $simulations[] = $simulation;
        }

        // Calculate statistics
        $finalBalances = array_column($simulations, 'final_balance');
        $returns = array_column($simulations, 'total_return');
        $maxDrawdowns = array_column($simulations, 'max_drawdown');

        return [
            'iterations' => $iterations,
            'statistics' => [
                'mean_final_balance' => $this->mean($finalBalances),
                'median_final_balance' => $this->median($finalBalances),
                'std_dev_final_balance' => $this->stdDev($finalBalances),
                'min_final_balance' => min($finalBalances),
                'max_final_balance' => max($finalBalances),
                'mean_return' => $this->mean($returns),
                'median_return' => $this->median($returns),
                'mean_max_drawdown' => $this->mean($maxDrawdowns),
                'worst_case_drawdown' => max($maxDrawdowns),
            ],
            'percentiles' => [
                'p5' => $this->percentile($finalBalances, 5),
                'p25' => $this->percentile($finalBalances, 25),
                'p50' => $this->percentile($finalBalances, 50),
                'p75' => $this->percentile($finalBalances, 75),
                'p95' => $this->percentile($finalBalances, 95),
            ],
            'probability_of_profit' => $this->calculateProfitProbability($simulations),
            'simulations' => $simulations,
        ];
    }

    /**
     * Calculate advanced performance metrics
     * 
     * @param BacktestResult $result
     * @return array
     */
    public function calculateAdvancedMetrics(BacktestResult $result): array
    {
        $trades = $this->extractTrades($result);
        
        if (empty($trades)) {
            return [];
        }

        $returns = array_column($trades, 'return');
        $pnl = array_column($trades, 'pnl');

        return [
            'sharpe_ratio' => $this->calculateSharpeRatio($returns),
            'sortino_ratio' => $this->calculateSortinoRatio($returns),
            'calmar_ratio' => $this->calculateCalmarRatio($result),
            'max_adverse_excursion' => $this->calculateMAE($trades),
            'max_favorable_excursion' => $this->calculateMFE($trades),
            'profit_factor' => $this->calculateProfitFactor($pnl),
            'expectancy' => $this->calculateExpectancy($trades),
        ];
    }

    /**
     * Optimize parameters on in-sample period
     */
    protected function optimizeParameters(
        Backtest $backtest,
        $startDate,
        $endDate,
        array $paramsToOptimize
    ): array {
        // Simple grid search optimization
        // In production, use more sophisticated methods (genetic algorithm, etc.)
        
        $bestParams = [];
        $bestScore = -999999;

        // Generate parameter combinations
        $combinations = $this->generateParameterCombinations($paramsToOptimize);

        foreach ($combinations as $combination) {
            $testBacktest = $backtest->replicate();
            $testBacktest->start_date = $startDate;
            $testBacktest->end_date = $endDate;
            
            foreach ($combination as $key => $value) {
                $testBacktest->setAttribute($key, $value);
            }

            $result = $this->backtestEngine->run($testBacktest);
            $score = $this->calculateOptimizationScore($result);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestParams = $combination;
            }
        }

        return $bestParams;
    }

    /**
     * Generate parameter combinations for optimization
     */
    protected function generateParameterCombinations(array $params): array
    {
        // Simple implementation: generate all combinations
        // In production, use more efficient methods
        
        $combinations = [[]];
        
        foreach ($params as $key => $values) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $newCombination = $combination;
                    $newCombination[$key] = $value;
                    $newCombinations[] = $newCombination;
                }
            }
            $combinations = $newCombinations;
        }
        
        return $combinations;
    }

    /**
     * Calculate optimization score (higher is better)
     */
    protected function calculateOptimizationScore(BacktestResult $result): float
    {
        // Combined score: profit + Sharpe ratio - drawdown
        $totalReturn = (float) ($result->total_return ?? 0);
        $sharpeRatio = (float) ($result->sharpe_ratio ?? 0);
        $maxDrawdown = abs((float) ($result->max_drawdown ?? 0));
        
        return ($totalReturn * 0.5) + ($sharpeRatio * 100 * 0.3) - ($maxDrawdown * 0.2);
    }

    /**
     * Extract trades from backtest result
     */
    protected function extractTrades(BacktestResult $result): array
    {
        // This would extract trades from the result
        // Implementation depends on how trades are stored
        return json_decode($result->trades ?? '[]', true) ?: [];
    }

    /**
     * Simulate trades with given sequence
     */
    protected function simulateTrades(array $trades, float $initialBalance): array
    {
        $balance = $initialBalance;
        $equity = $balance;
        $peakEquity = $balance;
        $maxDrawdown = 0;

        foreach ($trades as $trade) {
            $pnl = (float) ($trade['pnl'] ?? 0);
            $balance += $pnl;
            $equity = $balance;

            if ($equity > $peakEquity) {
                $peakEquity = $equity;
            }

            $drawdown = $peakEquity > 0 ? (($peakEquity - $equity) / $peakEquity) * 100 : 0;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }

        $totalReturn = $initialBalance > 0 ? (($balance - $initialBalance) / $initialBalance) * 100 : 0;

        return [
            'final_balance' => $balance,
            'total_return' => $totalReturn,
            'max_drawdown' => $maxDrawdown,
            'total_trades' => count($trades),
        ];
    }

    /**
     * Calculate Sharpe Ratio
     */
    protected function calculateSharpeRatio(array $returns): float
    {
        if (empty($returns)) {
            return 0;
        }

        $meanReturn = $this->mean($returns);
        $stdDev = $this->stdDev($returns);

        // Annualized Sharpe (assuming 252 trading days)
        $riskFreeRate = 0.02; // 2% risk-free rate
        $annualizedReturn = $meanReturn * 252;
        $annualizedStdDev = $stdDev * sqrt(252);

        return $annualizedStdDev > 0 ? ($annualizedReturn - $riskFreeRate) / $annualizedStdDev : 0;
    }

    /**
     * Calculate Sortino Ratio (only penalizes downside volatility)
     */
    protected function calculateSortinoRatio(array $returns): float
    {
        if (empty($returns)) {
            return 0;
        }

        $meanReturn = $this->mean($returns);
        $downsideReturns = array_filter($returns, fn($r) => $r < 0);
        
        if (empty($downsideReturns)) {
            return $meanReturn > 0 ? 999 : 0; // No downside = perfect
        }

        $downsideStdDev = $this->stdDev($downsideReturns);
        $annualizedReturn = $meanReturn * 252;
        $annualizedDownsideStdDev = $downsideStdDev * sqrt(252);

        return $annualizedDownsideStdDev > 0 ? ($annualizedReturn - 0.02) / $annualizedDownsideStdDev : 0;
    }

    /**
     * Calculate Calmar Ratio (return / max drawdown)
     */
    protected function calculateCalmarRatio(BacktestResult $result): float
    {
        $totalReturn = abs((float) ($result->total_return ?? 0));
        $maxDrawdown = abs((float) ($result->max_drawdown ?? 0));

        return $maxDrawdown > 0 ? $totalReturn / $maxDrawdown : 0;
    }

    /**
     * Calculate Maximum Adverse Excursion (MAE)
     */
    protected function calculateMAE(array $trades): array
    {
        $maeValues = [];
        
        foreach ($trades as $trade) {
            // MAE = maximum unrealized loss before trade closed
            $mae = (float) ($trade['mae'] ?? abs($trade['max_unrealized_loss'] ?? 0));
            $maeValues[] = $mae;
        }

        return [
            'mean' => $this->mean($maeValues),
            'max' => max($maeValues),
            'values' => $maeValues,
        ];
    }

    /**
     * Calculate Maximum Favorable Excursion (MFE)
     */
    protected function calculateMFE(array $trades): array
    {
        $mfeValues = [];
        
        foreach ($trades as $trade) {
            // MFE = maximum unrealized profit before trade closed
            $mfe = (float) ($trade['mfe'] ?? abs($trade['max_unrealized_profit'] ?? 0));
            $mfeValues[] = $mfe;
        }

        return [
            'mean' => $this->mean($mfeValues),
            'max' => max($mfeValues),
            'values' => $mfeValues,
        ];
    }

    /**
     * Calculate profit factor
     */
    protected function calculateProfitFactor(array $pnl): float
    {
        $profits = array_filter($pnl, fn($p) => $p > 0);
        $losses = array_filter($pnl, fn($p) => $p < 0);

        $totalProfit = array_sum($profits);
        $totalLoss = abs(array_sum($losses));

        return $totalLoss > 0 ? $totalProfit / $totalLoss : ($totalProfit > 0 ? 999 : 0);
    }

    /**
     * Calculate expectancy
     */
    protected function calculateExpectancy(array $trades): float
    {
        if (empty($trades)) {
            return 0;
        }

        $winRate = 0;
        $avgWin = 0;
        $avgLoss = 0;

        $wins = array_filter($trades, fn($t) => ($t['pnl'] ?? 0) > 0);
        $losses = array_filter($trades, fn($t) => ($t['pnl'] ?? 0) < 0);

        $winRate = count($trades) > 0 ? count($wins) / count($trades) : 0;
        $avgWin = !empty($wins) ? array_sum(array_column($wins, 'pnl')) / count($wins) : 0;
        $avgLoss = !empty($losses) ? abs(array_sum(array_column($losses, 'pnl')) / count($losses)) : 0;

        // Expectancy = (WinRate * AvgWin) - ((1 - WinRate) * AvgLoss)
        return ($winRate * $avgWin) - ((1 - $winRate) * $avgLoss);
    }

    /**
     * Extract metrics from backtest result
     */
    protected function extractMetrics(BacktestResult $result): array
    {
        return [
            'total_return' => (float) ($result->total_return ?? 0),
            'sharpe_ratio' => (float) ($result->sharpe_ratio ?? 0),
            'max_drawdown' => (float) ($result->max_drawdown ?? 0),
            'win_rate' => (float) ($result->win_rate ?? 0),
            'profit_factor' => (float) ($result->profit_factor ?? 0),
        ];
    }

    /**
     * Aggregate walk-forward results
     */
    protected function aggregateWalkForwardResults(array $results): array
    {
        $metrics = ['total_return', 'sharpe_ratio', 'max_drawdown', 'win_rate', 'profit_factor'];
        $aggregated = [];

        foreach ($metrics as $metric) {
            $values = array_column(array_column($results, 'result'), $metric);
            $aggregated[$metric] = [
                'mean' => $this->mean($values),
                'std_dev' => $this->stdDev($values),
                'min' => min($values),
                'max' => max($values),
            ];
        }

        return $aggregated;
    }

    /**
     * Calculate profit probability from Monte Carlo simulations
     */
    protected function calculateProfitProbability(array $simulations): float
    {
        $profitable = array_filter($simulations, fn($s) => ($s['final_balance'] ?? 0) > ($s['initial_balance'] ?? 0));
        return count($simulations) > 0 ? (count($profitable) / count($simulations)) * 100 : 0;
    }

    // Statistical helper functions
    protected function mean(array $values): float
    {
        return empty($values) ? 0 : array_sum($values) / count($values);
    }

    protected function median(array $values): float
    {
        if (empty($values)) {
            return 0;
        }
        sort($values);
        $mid = floor(count($values) / 2);
        return count($values) % 2 === 0
            ? ($values[$mid - 1] + $values[$mid]) / 2
            : $values[$mid];
    }

    protected function stdDev(array $values): float
    {
        if (empty($values)) {
            return 0;
        }
        $mean = $this->mean($values);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / count($values);
        return sqrt($variance);
    }

    protected function percentile(array $values, float $percentile): float
    {
        if (empty($values)) {
            return 0;
        }
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower === $upper) {
            return $values[$lower];
        }
        
        $weight = $index - $lower;
        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }
}

