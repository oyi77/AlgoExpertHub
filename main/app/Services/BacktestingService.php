<?php

namespace App\Services;

use App\Models\Backtest;
use App\Models\BacktestTrade;
use App\Models\Signal;
use App\Services\Backtesting\BacktestSlippageModel;
use Addons\TradingManagement\Modules\MarketData\Models\MarketData;
use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BacktestingService
{
    /**
     * Run a backtest
     * 
     * @param Backtest $backtest
     * @return array
     */
    public function runBacktest(Backtest $backtest): array
    {
        try {
            $backtest->update([
                'status' => 'running',
                'started_at' => now(),
            ]);

            // Load historical data
            $startTimestamp = Carbon::parse($backtest->start_date)->startOfDay()->timestamp;
            $endTimestamp = Carbon::parse($backtest->end_date)->endOfDay()->timestamp;

            $historicalData = MarketData::bySymbol($backtest->symbol)
                ->byTimeframe($backtest->timeframe)
                ->betweenDates($startTimestamp, $endTimestamp)
                ->orderBy('timestamp', 'asc')
                ->get();

            // If no historical data, try to fetch it from an active data connection
            if ($historicalData->isEmpty()) {
                Log::warning('No historical data found, attempting to fetch', [
                    'symbol' => $backtest->symbol,
                    'timeframe' => $backtest->timeframe,
                    'start' => $backtest->start_date,
                    'end' => $backtest->end_date,
                ]);
                
                // Try to find an active data connection (check both DataConnection and ExchangeConnection)
                $dataConnection = DataConnection::where('is_active', true)->first();
                
                // If no DataConnection, try ExchangeConnection with data fetching enabled
                if (!$dataConnection) {
                    $exchangeConnection = ExchangeConnection::where('is_active', true)
                        ->where('data_fetching_enabled', true)
                        ->where('status', 'active')
                        ->first();
                    
                    if ($exchangeConnection) {
                        // Use ExchangeConnection as data source
                        // Note: BackfillHistoricalDataJob expects DataConnection, so we need to create a temporary one
                        // or modify the job to accept ExchangeConnection. For now, we'll use the first available DataConnection
                        // or create a fallback message
                        Log::info('Found ExchangeConnection for data fetching, but BackfillHistoricalDataJob requires DataConnection', [
                            'exchange_connection_id' => $exchangeConnection->id,
                        ]);
                    }
                }
                
                if ($dataConnection) {
                    // Dispatch backfill job to fetch historical data
                    \Addons\TradingManagement\Modules\MarketData\Jobs\BackfillHistoricalDataJob::dispatch(
                        $dataConnection->id,
                        $backtest->symbol,
                        $backtest->timeframe,
                        $startTimestamp,
                        $endTimestamp
                    )->onQueue('high');
                    
                    throw new \Exception('No historical data found. Historical data fetch has been queued. Please wait a few minutes and try running the backtest again.');
                } else {
                    throw new \Exception('No historical data found and no active data connection available to fetch data. Please create a data connection or exchange connection with data fetching enabled first.');
                }
            }

            // Get published signals in the date range
            $signals = Signal::where('is_published', 1)
                ->whereHas('pair', function($query) use ($backtest) {
                    $query->where('name', $backtest->symbol);
                })
                ->whereHas('time', function($query) use ($backtest) {
                    $query->where('name', $backtest->timeframe);
                })
                ->whereNotNull('published_date')
                ->whereBetween('published_date', [
                    $backtest->start_date->startOfDay(),
                    $backtest->end_date->endOfDay()
                ])
                ->get();

            // Initialize backtest state
            $balance = $backtest->initial_balance;
            $equity = [$balance]; // Track equity over time
            $equityTimestamps = [Carbon::createFromTimestamp($historicalData->first()->timestamp)->toDateString()]; // Track timestamps for equity curve
            $trades = [];
            $openPositions = [];
            $peakBalance = $balance;
            $maxDrawdown = 0;

            // Process each candle
            foreach ($historicalData as $candle) {
                $candleTime = Carbon::createFromTimestamp($candle->timestamp);

                // Check for new signals at this time
                foreach ($signals as $signal) {
                    $signalTime = Carbon::parse($signal->published_date);
                    
                    // If signal was published at or before this candle
                    if ($signalTime->lte($candleTime) && !isset($openPositions[$signal->id])) {
                        $this->openPosition($backtest, $signal, $candle, $openPositions, $trades, $balance);
                    }
                }

                // Check existing positions for exit conditions
                $this->checkPositions($candle, $openPositions, $trades, $balance, $backtest);

                // Update equity curve
                $currentEquity = $balance;
                foreach ($openPositions as $position) {
                    $unrealizedPnL = $this->calculateUnrealizedPnL($position, $candle);
                    $currentEquity += $unrealizedPnL;
                }
                $equity[] = $currentEquity;
                $equityTimestamps[] = $candleTime->toDateString();

                // Track peak and drawdown
                if ($currentEquity > $peakBalance) {
                    $peakBalance = $currentEquity;
                }
                // Prevent division by zero in drawdown calculation
                $drawdown = $peakBalance > 0 
                    ? (($peakBalance - $currentEquity) / $peakBalance) * 100 
                    : 0;
                if ($drawdown > $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                }
            }

            // Close any remaining open positions at end
            foreach ($openPositions as $position) {
                $lastCandle = $historicalData->last();
                $this->closePosition($position, $lastCandle, $trades, $balance, $backtest);
            }

            // Calculate metrics
            $metrics = $this->calculateMetrics($backtest, $trades, $balance, $maxDrawdown);

            // Save trades to database
            foreach ($trades as $tradeData) {
                BacktestTrade::create($tradeData);
            }

            // Update backtest with results
            $backtest->update([
                'status' => 'completed',
                'final_balance' => $balance,
                'total_return' => $metrics['total_return'],
                'win_rate' => $metrics['win_rate'],
                'max_drawdown' => $maxDrawdown,
                'profit_factor' => $metrics['profit_factor'],
                'total_trades' => $metrics['total_trades'],
                'winning_trades' => $metrics['winning_trades'],
                'losing_trades' => $metrics['losing_trades'],
                'average_win' => $metrics['average_win'],
                'average_loss' => $metrics['average_loss'],
                'completed_at' => now(),
            ]);
            
            // Store equity curve data for chart (can be stored in a separate table or JSON field if needed)
            // For now, it's calculated from trades in the view

            return [
                'success' => true,
                'message' => 'Backtest completed successfully',
                'backtest' => $backtest->fresh(),
            ];

        } catch (\Exception $e) {
            Log::error('Backtest failed', [
                'backtest_id' => $backtest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $backtest->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Open a position from a signal
     */
    protected function openPosition(Backtest $backtest, Signal $signal, MarketData $candle, array &$openPositions, array &$trades, float &$balance): void
    {
        // Validate signal has valid open price
        if (!$signal->open_price || $signal->open_price <= 0) {
            Log::warning('Invalid signal open_price, skipping position', [
                'signal_id' => $signal->id,
                'open_price' => $signal->open_price,
            ]);
            return;
        }

        // Calculate position size (simple: 10% of balance)
        $positionSize = ($balance * 0.10) / $signal->open_price;
        
        if ($positionSize <= 0) {
            return; // Insufficient balance
        }

        $direction = in_array($signal->direction, ['buy', 'long']) ? 'buy' : 'sell';
        $expectedEntryPrice = $signal->open_price;
        
        // Apply slippage to entry price if enabled
        $slippageModel = app(BacktestSlippageModel::class);
        $slippageModelType = $backtest->slippage_model ?? 'fixed';
        $slippagePips = $backtest->slippage_pips ?? 2.0;
        $symbol = $backtest->symbol;
        
        $entryPrice = $expectedEntryPrice;
        if ($slippageModelType !== 'none') {
            $actualSlippage = $slippageModelType === 'fixed' 
                ? $slippagePips 
                : $slippageModel->calculateSlippage($symbol, $positionSize);
            $entryPrice = $slippageModel->applySlippage($expectedEntryPrice, $direction, $actualSlippage, $symbol);
        }
        
        // Apply spread cost on entry if enabled
        $spreadCostEnabled = $backtest->spread_cost_enabled ?? true;
        if ($spreadCostEnabled) {
            $spreadCost = $slippageModel->calculateSpreadCost($symbol, $positionSize);
            $balance -= $spreadCost; // Deduct spread cost from balance
        }

        $position = [
            'signal_id' => $signal->id,
            'backtest_id' => $backtest->id,
            'entry_time' => Carbon::createFromTimestamp($candle->timestamp),
            'entry_price' => $entryPrice,
            'direction' => $direction,
            'quantity' => $positionSize,
            'stop_loss' => $signal->sl,
            'take_profit' => $signal->tp,
            'status' => 'open',
        ];

        $openPositions[$signal->id] = $position;
    }

    /**
     * Check positions for exit conditions
     */
    protected function checkPositions(MarketData $candle, array &$openPositions, array &$trades, float &$balance, Backtest $backtest): void
    {
        $slippageModel = app(BacktestSlippageModel::class);
        $symbol = $backtest->symbol;
        
        // Get slippage configuration from backtest
        $slippageModelType = $backtest->slippage_model ?? 'fixed';
        $slippagePips = $backtest->slippage_pips ?? 2.0;
        $spreadCostEnabled = $backtest->spread_cost_enabled ?? true;
        
        foreach ($openPositions as $signalId => $position) {
            $lotSize = $position['quantity'] ?? 0.01;
            $shouldClose = false;
            $closeReason = '';
            $executionPrice = null;

            // Check stop loss
            if ($position['stop_loss']) {
                if ($position['direction'] === 'buy' && $candle->low <= $position['stop_loss']) {
                    $shouldClose = true;
                    $closeReason = 'stop_loss';
                    // Apply slippage to SL execution price
                    if ($slippageModelType !== 'none') {
                        $actualSlippage = $slippageModelType === 'fixed' 
                            ? $slippagePips 
                            : $slippageModel->calculateSlippage($symbol, $lotSize);
                        $executionPrice = $slippageModel->applySlippage($position['stop_loss'], 'sell', $actualSlippage, $symbol);
                    } else {
                        $executionPrice = $position['stop_loss'];
                    }
                } elseif ($position['direction'] === 'sell' && $candle->high >= $position['stop_loss']) {
                    $shouldClose = true;
                    $closeReason = 'stop_loss';
                    // Apply slippage to SL execution price
                    if ($slippageModelType !== 'none') {
                        $actualSlippage = $slippageModelType === 'fixed' 
                            ? $slippagePips 
                            : $slippageModel->calculateSlippage($symbol, $lotSize);
                        $executionPrice = $slippageModel->applySlippage($position['stop_loss'], 'buy', $actualSlippage, $symbol);
                    } else {
                        $executionPrice = $position['stop_loss'];
                    }
                }
            }

            // Check take profit
            if (!$shouldClose && $position['take_profit']) {
                if ($position['direction'] === 'buy' && $candle->high >= $position['take_profit']) {
                    $shouldClose = true;
                    $closeReason = 'take_profit';
                    // Apply slippage to TP execution price
                    if ($slippageModelType !== 'none') {
                        $actualSlippage = $slippageModelType === 'fixed' 
                            ? $slippagePips 
                            : $slippageModel->calculateSlippage($symbol, $lotSize);
                        $executionPrice = $slippageModel->applySlippage($position['take_profit'], 'sell', $actualSlippage, $symbol);
                    } else {
                        $executionPrice = $position['take_profit'];
                    }
                } elseif ($position['direction'] === 'sell' && $candle->low <= $position['take_profit']) {
                    $shouldClose = true;
                    $closeReason = 'take_profit';
                    // Apply slippage to TP execution price
                    if ($slippageModelType !== 'none') {
                        $actualSlippage = $slippageModelType === 'fixed' 
                            ? $slippagePips 
                            : $slippageModel->calculateSlippage($symbol, $lotSize);
                        $executionPrice = $slippageModel->applySlippage($position['take_profit'], 'buy', $actualSlippage, $symbol);
                    } else {
                        $executionPrice = $position['take_profit'];
                    }
                }
            }

            if ($shouldClose) {
                $this->closePosition($position, $candle, $trades, $balance, $backtest, $closeReason, $executionPrice, $slippageModel, $spreadCostEnabled);
                unset($openPositions[$signalId]);
            }
        }
    }

    /**
     * Close a position
     */
    protected function closePosition(array $position, MarketData $candle, array &$trades, float &$balance, Backtest $backtest, string $closeReason = 'manual', ?float $executionPrice = null, ?BacktestSlippageModel $slippageModel = null, bool $spreadCostEnabled = true): void
    {
        // Use execution price if provided (with slippage), otherwise use trigger price or candle close
        $exitPrice = $executionPrice ?? ($position['direction'] === 'buy' 
            ? ($closeReason === 'stop_loss' ? $position['stop_loss'] : ($closeReason === 'take_profit' ? $position['take_profit'] : $candle->close))
            : ($closeReason === 'stop_loss' ? $position['stop_loss'] : ($closeReason === 'take_profit' ? $position['take_profit'] : $candle->close)));

        // Calculate P&L
        if ($position['direction'] === 'buy') {
            $profitLoss = ($exitPrice - $position['entry_price']) * $position['quantity'];
        } else {
            $profitLoss = ($position['entry_price'] - $exitPrice) * $position['quantity'];
        }
        
        // Apply spread cost if enabled
        if ($spreadCostEnabled && $slippageModel) {
            $symbol = $backtest->symbol;
            $lotSize = $position['quantity'] ?? 0.01;
            $spreadCost = $slippageModel->calculateSpreadCost($symbol, $lotSize);
            $profitLoss -= $spreadCost; // Spread cost reduces profit
        }

        // Prevent division by zero in profit/loss percentage calculation
        $profitLossPercent = $position['entry_price'] > 0 
            ? (($exitPrice - $position['entry_price']) / $position['entry_price']) * 100 
            : 0;
        if ($position['direction'] === 'sell') {
            $profitLossPercent = -$profitLossPercent;
        }

        // Update balance
        $balance += $profitLoss;

        $trades[] = [
            'backtest_id' => $backtest->id,
            'entry_time' => $position['entry_time'],
            'exit_time' => Carbon::createFromTimestamp($candle->timestamp),
            'entry_price' => $position['entry_price'],
            'exit_price' => $exitPrice,
            'direction' => $position['direction'],
            'quantity' => $position['quantity'],
            'profit_loss' => $profitLoss,
            'profit_loss_percent' => $profitLossPercent,
            'status' => 'closed',
            'notes' => "Closed: {$closeReason}",
        ];
    }

    /**
     * Calculate unrealized P&L for open position
     */
    protected function calculateUnrealizedPnL(array $position, MarketData $candle): float
    {
        $currentPrice = $candle->close;
        
        if ($position['direction'] === 'buy') {
            return ($currentPrice - $position['entry_price']) * $position['quantity'];
        } else {
            return ($position['entry_price'] - $currentPrice) * $position['quantity'];
        }
    }

    /**
     * Calculate performance metrics
     */
    protected function calculateMetrics(Backtest $backtest, array $trades, float $finalBalance, float $maxDrawdown): array
    {
        // Prevent division by zero in total return calculation
        $totalReturn = $backtest->initial_balance > 0 
            ? (($finalBalance - $backtest->initial_balance) / $backtest->initial_balance) * 100 
            : 0;
        
        $winningTrades = array_filter($trades, fn($t) => $t['profit_loss'] > 0);
        $losingTrades = array_filter($trades, fn($t) => $t['profit_loss'] < 0);
        
        $totalTrades = count($trades);
        $winningCount = count($winningTrades);
        $losingCount = count($losingTrades);
        
        $winRate = $totalTrades > 0 ? ($winningCount / $totalTrades) * 100 : 0;
        
        $grossProfit = array_sum(array_column($winningTrades, 'profit_loss'));
        $grossLoss = abs(array_sum(array_column($losingTrades, 'profit_loss')));
        
        $profitFactor = $grossLoss > 0 ? $grossProfit / $grossLoss : ($grossProfit > 0 ? 999 : 0);
        
        $averageWin = $winningCount > 0 ? $grossProfit / $winningCount : 0;
        $averageLoss = $losingCount > 0 ? $grossLoss / $losingCount : 0;

        return [
            'total_return' => $totalReturn,
            'win_rate' => $winRate,
            'max_drawdown' => $maxDrawdown,
            'profit_factor' => $profitFactor,
            'total_trades' => $totalTrades,
            'winning_trades' => $winningCount,
            'losing_trades' => $losingCount,
            'average_win' => $averageWin,
            'average_loss' => $averageLoss,
        ];
    }
}

