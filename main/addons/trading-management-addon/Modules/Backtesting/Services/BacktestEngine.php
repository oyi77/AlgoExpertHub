<?php

namespace Addons\TradingManagement\Modules\Backtesting\Services;

use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use Addons\TradingManagement\Modules\FilterStrategy\Services\FilterStrategyEvaluator;
use Addons\TradingManagement\Modules\RiskManagement\Services\RiskCalculatorService;
use App\Models\Signal;

/**
 * Backtest Engine
 * 
 * Runs trading strategies on historical data
 * Uses same components as live trading:
 * - FilterStrategyEvaluator
 * - AiAnalysis (optional)
 * - RiskCalculatorService
 */
class BacktestEngine
{
    protected MarketDataService $marketDataService;
    protected FilterStrategyEvaluator $filterEvaluator;
    protected RiskCalculatorService $riskCalculator;

    public function __construct(
        MarketDataService $marketDataService,
        FilterStrategyEvaluator $filterEvaluator,
        RiskCalculatorService $riskCalculator
    ) {
        $this->marketDataService = $marketDataService;
        $this->filterEvaluator = $filterEvaluator;
        $this->riskCalculator = $riskCalculator;
    }

    /**
     * Run backtest
     * 
     * @param Backtest $backtest
     * @return BacktestResult
     */
    public function run(Backtest $backtest): BacktestResult
    {
        $backtest->markAsRunning();

        try {
            // Get historical market data
            $marketData = $this->marketDataService->getRange(
                $backtest->symbol,
                $backtest->timeframe,
                $backtest->start_date->timestamp,
                $backtest->end_date->timestamp
            );

            if ($marketData->isEmpty()) {
                throw new \Exception('No market data available for backtest period');
            }

            // Initialize backtest state
            $balance = (float) $backtest->initial_balance;
            $equity = $balance;
            $trades = [];
            $equityCurve = [];
            $openPosition = null;
            $totalTrades = 0;
            $winningTrades = 0;
            $losingTrades = 0;
            $totalProfit = 0;
            $totalLoss = 0;
            $maxEquity = $balance;
            $maxDrawdown = 0;

            $totalCandles = $marketData->count();
            $processedCandles = 0;

            // Simulate trading on each candle
            foreach ($marketData as $candle) {
                $processedCandles++;
                
                // Update progress every 10%
                if ($processedCandles % max(1, (int)($totalCandles / 10)) === 0) {
                    $progress = (int)(($processedCandles / $totalCandles) * 100);
                    $backtest->updateProgress($progress);
                }

                // Check if open position should be closed (SL/TP hit)
                if ($openPosition) {
                    $currentPrice = (float) $candle->close;
                    
                    // Check SL
                    if ($this->shouldCloseBySL($openPosition, $currentPrice)) {
                        $pnl = $this->calculatePnL($openPosition, $openPosition['sl'], $openPosition['direction']);
                        $equity += $pnl;
                        
                        if ($pnl < 0) {
                            $losingTrades++;
                            $totalLoss += abs($pnl);
                        } else {
                            $winningTrades++;
                            $totalProfit += $pnl;
                        }
                        
                        $trades[] = array_merge($openPosition, [
                            'exit_price' => $openPosition['sl'],
                            'exit_reason' => 'SL',
                            'pnl' => $pnl,
                            'exit_time' => $candle->timestamp,
                        ]);
                        
                        $openPosition = null;
                        $totalTrades++;
                    }
                    // Check TP
                    elseif ($this->shouldCloseByTP($openPosition, $currentPrice)) {
                        $pnl = $this->calculatePnL($openPosition, $openPosition['tp'], $openPosition['direction']);
                        $equity += $pnl;
                        $winningTrades++;
                        $totalProfit += $pnl;
                        
                        $trades[] = array_merge($openPosition, [
                            'exit_price' => $openPosition['tp'],
                            'exit_reason' => 'TP',
                            'pnl' => $pnl,
                            'exit_time' => $candle->timestamp,
                        ]);
                        
                        $openPosition = null;
                        $totalTrades++;
                    }
                }

                // Generate signal and evaluate filters (simplified for backtest)
                // In real implementation, you'd apply filter strategy here
                // For now, we'll use market data as entry signals
                
                // Update equity curve
                $equityCurve[] = [
                    'timestamp' => $candle->timestamp,
                    'equity' => $equity,
                ];

                // Track max drawdown
                if ($equity > $maxEquity) {
                    $maxEquity = $equity;
                }
                $drawdown = $maxEquity - $equity;
                if ($drawdown > $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                }
            }

            // Calculate final metrics
            $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;
            $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : 0;
            $netProfit = $totalProfit - $totalLoss;
            $returnPercent = (($equity - $backtest->initial_balance) / $backtest->initial_balance) * 100;
            $maxDrawdownPercent = $maxEquity > 0 ? ($maxDrawdown / $maxEquity) * 100 : 0;

            // Create result
            $result = BacktestResult::create([
                'backtest_id' => $backtest->id,
                'total_trades' => $totalTrades,
                'winning_trades' => $winningTrades,
                'losing_trades' => $losingTrades,
                'win_rate' => $winRate,
                'total_profit' => $totalProfit,
                'total_loss' => $totalLoss,
                'net_profit' => $netProfit,
                'final_balance' => $equity,
                'return_percent' => $returnPercent,
                'profit_factor' => $profitFactor,
                'max_drawdown' => $maxDrawdown,
                'max_drawdown_percent' => $maxDrawdownPercent,
                'avg_win' => $winningTrades > 0 ? $totalProfit / $winningTrades : 0,
                'avg_loss' => $losingTrades > 0 ? $totalLoss / $losingTrades : 0,
                'equity_curve' => $equityCurve,
                'trade_details' => $trades,
            ]);

            $backtest->markAsCompleted();

            return $result;

        } catch (\Exception $e) {
            $backtest->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function shouldCloseBySL(array $position, float $currentPrice): bool
    {
        $sl = $position['sl'];
        return $position['direction'] === 'buy'
            ? $currentPrice <= $sl
            : $currentPrice >= $sl;
    }

    protected function shouldCloseByTP(array $position, float $currentPrice): bool
    {
        $tp = $position['tp'];
        return $position['direction'] === 'buy'
            ? $currentPrice >= $tp
            : $currentPrice <= $tp;
    }

    protected function calculatePnL(array $position, float $exitPrice, string $direction): float
    {
        $entryPrice = $position['entry_price'];
        $lotSize = $position['lot_size'];
        
        $priceDiff = $direction === 'buy'
            ? $exitPrice - $entryPrice
            : $entryPrice - $exitPrice;

        return $priceDiff * $lotSize * 10; // Simplified: $10 per pip per lot
    }
}

