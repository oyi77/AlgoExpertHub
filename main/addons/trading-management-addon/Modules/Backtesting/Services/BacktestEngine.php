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

                // Only look for new entries if no position is open
                if (!$openPosition && $backtest->filter_strategy_id) {
                    // Create temporary signal for filter evaluation
                    $tempSignal = $this->createTempSignal($candle, $backtest);
                    
                    // Evaluate filter strategy
                    $filterStrategy = $backtest->filterStrategy;
                    if ($filterStrategy) {
                        $filterResult = $this->filterEvaluator->evaluate($filterStrategy, $tempSignal);
                        
                        // If filter passes, enter trade
                        if ($filterResult['pass']) {
                            // Calculate position size using risk calculator
                            $preset = $backtest->preset;
                            if ($preset) {
                                $accountInfo = [
                                    'balance' => $equity,
                                    'equity' => $equity,
                                    'margin' => 0,
                                ];
                                
                                $riskResult = $this->riskCalculator->calculateForSignal($tempSignal, $preset, $accountInfo);
                                $lotSize = $riskResult['lot_size'] ?? 0.01;
                                
                                // Calculate SL/TP using preset
                                $sl = $this->riskCalculator->calculateStopLoss($tempSignal, $preset, $lotSize);
                                $tps = $this->riskCalculator->calculateTakeProfits($tempSignal, $preset, $lotSize);
                                $tp = !empty($tps) ? $tps[0] : $tempSignal->tp;
                                
                                // Open position
                                $openPosition = [
                                    'entry_price' => (float) $candle->close,
                                    'sl' => $sl,
                                    'tp' => $tp,
                                    'tps' => $tps, // Multiple TPs
                                    'direction' => $tempSignal->direction,
                                    'lot_size' => $lotSize,
                                    'entry_time' => $candle->timestamp,
                                    'symbol' => $backtest->symbol,
                                ];
                            }
                        }
                    }
                }
                
                // Handle multiple TP levels (partial closes)
                if ($openPosition && isset($openPosition['tps']) && is_array($openPosition['tps'])) {
                    $currentPrice = (float) $candle->close;
                    $remainingLotSize = $openPosition['lot_size'];
                    $closedLots = 0;
                    
                    foreach ($openPosition['tps'] as $index => $tpLevel) {
                        if (!isset($openPosition['tp_closed_' . $index])) {
                            $shouldClose = $openPosition['direction'] === 'buy'
                                ? $currentPrice >= $tpLevel
                                : $currentPrice <= $tpLevel;
                            
                            if ($shouldClose) {
                                // Partial close at this TP level
                                $tpLotSize = $remainingLotSize * (1 / count($openPosition['tps'])); // Equal distribution
                                $pnl = $this->calculatePnL($openPosition, $tpLevel, $openPosition['direction'], $tpLotSize);
                                $equity += $pnl;
                                
                                if ($pnl > 0) {
                                    $winningTrades++;
                                    $totalProfit += $pnl;
                                } else {
                                    $losingTrades++;
                                    $totalLoss += abs($pnl);
                                }
                                
                                $closedLots += $tpLotSize;
                                $remainingLotSize -= $tpLotSize;
                                $openPosition['tp_closed_' . $index] = true;
                                
                                $trades[] = [
                                    'entry_price' => $openPosition['entry_price'],
                                    'exit_price' => $tpLevel,
                                    'exit_reason' => 'TP' . ($index + 1),
                                    'pnl' => $pnl,
                                    'entry_time' => $openPosition['entry_time'],
                                    'exit_time' => $candle->timestamp,
                                    'lot_size' => $tpLotSize,
                                    'direction' => $openPosition['direction'],
                                ];
                                
                                $totalTrades++;
                            }
                        }
                    }
                    
                    // Update remaining lot size
                    $openPosition['lot_size'] = $remainingLotSize;
                    
                    // If all lots closed, clear position
                    if ($remainingLotSize <= 0.001) {
                        $openPosition = null;
                    }
                }
                
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
            
            // Calculate Sharpe ratio
            $sharpeRatio = $this->calculateSharpeRatio($equityCurve, $trades);
            
            // Calculate additional metrics
            $largestWin = !empty($trades) ? max(array_column($trades, 'pnl')) : 0;
            $largestLoss = !empty($trades) ? min(array_column($trades, 'pnl')) : 0;
            $consecutiveWins = $this->calculateConsecutiveWins($trades);
            $consecutiveLosses = $this->calculateConsecutiveLosses($trades);

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
                'sharpe_ratio' => $sharpeRatio,
                'max_drawdown' => $maxDrawdown,
                'max_drawdown_percent' => $maxDrawdownPercent,
                'avg_win' => $winningTrades > 0 ? $totalProfit / $winningTrades : 0,
                'avg_loss' => $losingTrades > 0 ? $totalLoss / $losingTrades : 0,
                'largest_win' => $largestWin,
                'largest_loss' => $largestLoss,
                'consecutive_wins' => $consecutiveWins,
                'consecutive_losses' => $consecutiveLosses,
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

    protected function calculatePnL(array $position, float $exitPrice, string $direction, ?float $lotSize = null): float
    {
        $entryPrice = $position['entry_price'];
        $lotSize = $lotSize ?? $position['lot_size'];
        
        $priceDiff = $direction === 'buy'
            ? $exitPrice - $entryPrice
            : $entryPrice - $exitPrice;

        // Simplified: $10 per pip per lot (for forex)
        // For crypto, this would be different calculation
        return $priceDiff * $lotSize * 10;
    }

    /**
     * Create temporary signal for filter evaluation
     */
    protected function createTempSignal($candle, Backtest $backtest): Signal
    {
        // Find or create currency pair
        $pair = \App\Models\CurrencyPair::firstOrCreate(
            ['name' => $backtest->symbol],
            ['status' => 1]
        );
        
        // Find or create timeframe
        $timeframe = \App\Models\TimeFrame::firstOrCreate(
            ['name' => $backtest->timeframe],
            ['status' => 1]
        );
        
        // Find or create market (default to Forex)
        $market = \App\Models\Market::where('name', 'Forex')->first();
        if (!$market) {
            $market = \App\Models\Market::firstOrCreate(
                ['name' => 'Forex'],
                ['status' => 1]
            );
        }
        
        // Create temporary signal (not saved to DB)
        $signal = new Signal();
        $signal->currency_pair_id = $pair->id;
        $signal->time_frame_id = $timeframe->id;
        $signal->market_id = $market->id;
        $signal->open_price = (float) $candle->close;
        $signal->direction = 'buy'; // Default, can be determined by filter
        $signal->sl = 0; // Will be calculated by risk calculator
        $signal->tp = 0; // Will be calculated by risk calculator
        
        return $signal;
    }

    /**
     * Calculate Sharpe ratio
     */
    protected function calculateSharpeRatio(array $equityCurve, array $trades): float
    {
        if (empty($trades) || count($trades) < 2) {
            return 0;
        }
        
        // Calculate returns from trades
        $returns = [];
        foreach ($trades as $trade) {
            if (isset($trade['pnl']) && isset($trade['entry_price'])) {
                $return = $trade['pnl'] / ($trade['entry_price'] * ($trade['lot_size'] ?? 0.01) * 10);
                $returns[] = $return;
            }
        }
        
        if (empty($returns)) {
            return 0;
        }
        
        $meanReturn = array_sum($returns) / count($returns);
        
        // Calculate standard deviation
        $variance = 0;
        foreach ($returns as $return) {
            $variance += pow($return - $meanReturn, 2);
        }
        $stdDev = sqrt($variance / count($returns));
        
        if ($stdDev == 0) {
            return 0;
        }
        
        // Sharpe ratio = (mean return - risk free rate) / std dev
        // Risk free rate assumed to be 0 for simplicity
        return $meanReturn / $stdDev;
    }

    /**
     * Calculate consecutive wins
     */
    protected function calculateConsecutiveWins(array $trades): int
    {
        $maxConsecutive = 0;
        $current = 0;
        
        foreach ($trades as $trade) {
            if (($trade['pnl'] ?? 0) > 0) {
                $current++;
                $maxConsecutive = max($maxConsecutive, $current);
            } else {
                $current = 0;
            }
        }
        
        return $maxConsecutive;
    }

    /**
     * Calculate consecutive losses
     */
    protected function calculateConsecutiveLosses(array $trades): int
    {
        $maxConsecutive = 0;
        $current = 0;
        
        foreach ($trades as $trade) {
            if (($trade['pnl'] ?? 0) < 0) {
                $current++;
                $maxConsecutive = max($maxConsecutive, $current);
            } else {
                $current = 0;
            }
        }
        
        return $maxConsecutive;
    }
}

