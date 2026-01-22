<?php

namespace Addons\TradingManagement\Modules\Execution\Jobs;

use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\Execution\Services\MarketStatusChecker;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Addons\TradingManagement\Modules\RiskManagement\Services\SlippageProtectionService;
use Addons\TradingManagement\Modules\RiskManagement\Services\PositionLimitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\InternalTrade;
use App\Models\User;
use App\Services\InternalBrokerService;

/**
 * ExecutionJob
 * 
 * Executes trades on exchange connections using existing adapters
 */
class ExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $executionData;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(array $executionData)
    {
        $this->executionData = $executionData;
    }

    public function handle()
    {
        $botId = $this->executionData['bot_id'] ?? null;
        $symbol = $this->executionData['symbol'] ?? null;
        $direction = $this->executionData['direction'] ?? null;
        
        // Setup bot-specific logging if bot_id is provided
        if ($botId) {
            $this->setupBotLogger($botId);
        }
        
        Log::info('ExecutionJob: Starting trade execution', [
            'bot_id' => $botId,
            'connection_id' => $this->executionData['connection_id'] ?? null,
            'symbol' => $symbol,
            'direction' => $direction,
            'quantity' => $this->executionData['quantity'] ?? null,
        ]);
        
        try {
            $connection = ExecutionConnection::find($this->executionData['connection_id']);
            if (!$connection) {
                Log::error('ExecutionJob: Connection not found', [
                    'connection_id' => $this->executionData['connection_id'],
                ]);
                return;
            }
            
            if (!$connection->canExecuteTrades()) {
                Log::warning('ExecutionJob: Connection not available for execution', [
                    'connection_id' => $connection->id,
                    'is_active' => $connection->is_active ?? false,
                    'status' => $connection->status ?? null,
                ]);
                return;
            }

            // Validate market status before execution (skip in paper trading mode)
            $isTestMode = $this->executionData['is_paper_trading'] ?? false;

            // Paper trading mode: Use virtual positions created via InternalBrokerService for paper trading
            // (already works for manual trading via TradingTerminalController)
            // Paper trading mode: Create virtual position
            if ($isTestMode) {
                Log::info('Paper trading mode: Creating virtual position', [
                    'symbol' => $this->executionData['symbol'] ?? 'unknown',
                ]);

                $result = $this->createVirtualPosition(
                    $this->executionData['symbol'],
                    $this->executionData['direction'],
                    $this->executionData['quantity'],
                    $this->executionData['entry_price'] ?? null,
                    $this->executionData['stop_loss'] ?? null,
                    $this->executionData['take_profit'] ?? null,
                    $this->executionData['connection_id'] ?? null
                );

                if ($result['success']) {
                    Log::info('Paper trade executed', ['trade_id' => $result['trade_id'] ?? null]);
                }
                return;
            }

            // Check position limits
            $positionLimitService = app(PositionLimitService::class);
            $positionLimitCheck = $positionLimitService->shouldPreventTrade($connection, $symbol ?? '');

            if ($positionLimitCheck['should_prevent']) {
                Log::warning('ExecutionJob: Position limit check failed', [
                    'bot_id' => $this->executionData['bot_id'] ?? null,
                    'symbol' => $symbol,
                    'reason' => $positionLimitCheck['reason'],
                ]);
                return;
            }

            // Continue with normal execution
            Log::info('ExecutionJob: All validations passed, proceeding with execution', [
                'bot_id' => $botId,
                'symbol' => $symbol,
                'direction' => $direction,
            ]);

        // Get adapter for connection - create directly based on connection type
            $adapter = $this->createAdapter($connection);

            Log::info('ExecutionJob: Adapter created, checking market data freshness', [
                'connection_id' => $connection->id,
                'provider' => $connection->provider,
                'symbol' => $this->executionData['symbol'],
                'direction' => $this->executionData['direction'],
            ]);

            // Check market data freshness before execution
            $symbol = $this->executionData['symbol'] ?? '';
            $timeframe = $this->executionData['timeframe'] ?? '';
            $dataConnection = $connection->dataConnection;
            $accountId = $dataConnection?->credentials['account_id'] ?? null;

            $marketStatus = app(MarketStatusChecker::class)->checkMarketDataFreshness(
                symbol: $symbol,
                timeframe: $timeframe,
                accountId: $accountId,
                botId: $this->executionData['bot_id'] ?? null,
                dataConnection: $dataConnection
            );

            if (!$marketStatus['is_fresh']) {
                Log::warning('ExecutionJob: Market data stale, rejecting trade', [
                    'bot_id' => $this->executionData['bot_id'] ?? null,
                    'connection_id' => $connection->id,
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'status' => $marketStatus['status'] ?? 'unknown',
                    'age_minutes' => $marketStatus['age_minutes'] ?? 0,
                    'max_age_minutes' => $marketStatus['max_age_minutes'] ?? 0,
                    'last_timestamp' => $marketStatus['last_timestamp'] ?? null,
                ]);

                // Create failed execution log for tracking
                \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::create([
                    'connection_id' => $connection->id,
                    'signal_id' => $this->executionData['signal_id'] ?? null,
                    'status' => 'failed',
                    'execution_type' => ($this->executionData['entry_price'] ?? null) ? 'limit' : 'market',
                    'symbol' => $this->executionData['symbol'] ?? '',
                    'direction' => $this->executionData['direction'] ?? '',
                    'quantity' => $this->executionData['quantity'] ?? 0,
                    'entry_price' => $this->executionData['entry_price'] ?? null,
                    'sl_price' => $this->executionData['stop_loss'] ?? null,
                    'tp_price' => $this->executionData['take_profit'] ?? null,
                    'error_message' => sprintf(
                        'Market data stale (age: %s minutes, max: %s minutes) - status: %s',
                        $marketStatus['age_minutes'] ?? 0,
                        $marketStatus['max_age_minutes'] ?? 0,
                        $marketStatus['status'] ?? 'unknown'
                    ),
                ]);

                return; // Reject trade
            }

            Log::info('ExecutionJob: Market data fresh, proceeding with execution', [
                'bot_id' => $this->executionData['bot_id'] ?? null,
                'connection_id' => $connection->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'data_age_minutes' => $marketStatus['age_minutes'] ?? 0,
            ]);

            // Create execution log FIRST to track all attempts (success or failure)
            $executionLog = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::create([
                'connection_id' => $connection->id,
                'signal_id' => $this->executionData['signal_id'] ?? null,
                'status' => 'pending',
                'execution_type' => ($this->executionData['entry_price'] ?? null) ? 'limit' : 'market',
                'symbol' => $this->executionData['symbol'] ?? '',
                'direction' => $this->executionData['direction'] ?? '',
                'quantity' => $this->executionData['quantity'] ?? 0,
                'entry_price' => $this->executionData['entry_price'] ?? null,
                'sl_price' => $this->executionData['stop_loss'] ?? null,
                'tp_price' => $this->executionData['take_profit'] ?? null,
            ]);

            // Execute trade
            $result = $this->executeTrade($adapter, $connection);
            $result['execution_log_id'] = $executionLog->id;

            if ($result['success']) {
                // Update execution log to executed status
                $executionLog->update([
                    'status' => 'executed',
                    'executed_at' => now(),
                ]);

                // On successful execution, reset failure counter
                $connection->update(['consecutive_failures' => 0]);

                // Create position record
                $this->createPosition($connection, $result);
                
                Log::info('ExecutionJob: Trade executed successfully', [
                    'connection_id' => $connection->id,
                    'bot_id' => $this->executionData['bot_id'] ?? null,
                    'order_id' => $result['order_id'] ?? null,
                    'position_id' => $result['position_id'] ?? null,
                    'symbol' => $this->executionData['symbol'],
                    'direction' => $this->executionData['direction'],
                    'quantity' => $this->executionData['quantity'],
                    'execution_log_id' => $executionLog->id,
                ]);
            } else {
                // Update execution log to failed status
                $executionLog->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error',
                ]);

                // On execution failure, track consecutive failures
                $connection->increment('consecutive_failures');
                $connection->update(['last_failure_at' => now()]);

                Log::error('ExecutionJob: Trade execution failed', [
                    'connection_id' => $connection->id,
                    'bot_id' => $this->executionData['bot_id'] ?? null,
                    'symbol' => $this->executionData['symbol'],
                    'direction' => $this->executionData['direction'],
                    'error' => $result['error'] ?? 'Unknown error',
                    'execution_log_id' => $executionLog->id,
                    'result' => $result,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ExecutionJob: Execution job failed with exception', [
                'execution_data' => $this->executionData,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Execute trade using adapter
     */
    protected function executeTrade($adapter, ExecutionConnection $connection): array
    {
        try {
            $symbol = $this->executionData['symbol'];
            $direction = $this->executionData['direction'];
            $quantity = $this->executionData['quantity'];
            $stopLoss = $this->executionData['stop_loss'] ?? null;
            $takeProfit = $this->executionData['take_profit'] ?? null;
            $entryPrice = $this->executionData['entry_price'] ?? null;

            Log::info('ExecutionJob: Preparing trade order', [
                'connection_id' => $connection->id,
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
                'entry_price' => $entryPrice,
                'stop_loss' => $stopLoss,
                'take_profit' => $takeProfit,
            ]);

            // Determine order type (market or limit)
            $orderType = $entryPrice ? 'limit' : 'market';

            Log::info('ExecutionJob: Placing order', [
                'order_type' => $orderType,
                'symbol' => $symbol,
                'direction' => $direction,
            ]);

            if ($orderType === 'limit') {
                $result = $adapter->createLimitOrder(
                    $symbol,
                    $direction,
                    $quantity,
                    $entryPrice,
                    ['stopLoss' => $stopLoss, 'takeProfit' => $takeProfit, 'comment' => 'Bot: ' . ($this->executionData['bot_id'] ?? 'N/A')]
                );
            } else {
                $result = $adapter->createMarketOrder(
                    $symbol,
                    $direction,
                    $quantity,
                    ['stopLoss' => $stopLoss, 'takeProfit' => $takeProfit, 'comment' => 'Bot: ' . ($this->executionData['bot_id'] ?? 'N/A')]
                );
            }

            Log::info('ExecutionJob: Order placed, received result', [
                'success' => $result['success'] ?? false,
                'order_id' => $result['orderId'] ?? $result['order_id'] ?? null,
                'position_id' => $result['positionId'] ?? $result['position_id'] ?? null,
            ]);

            return [
                'success' => $result['success'] ?? false,
                'order_id' => $result['orderId'] ?? $result['order_id'] ?? null,
                'position_id' => $result['positionId'] ?? $result['position_id'] ?? null,
                'data' => $result,
            ];

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $isMarketClosedError = stripos($errorMessage, 'MARKET_CLOSED') !== false || 
                                    stripos($errorMessage, 'market is closed') !== false;
            
            if ($isMarketClosedError) {
                Log::error('ExecutionJob: Market closed error during execution', [
                    'bot_id' => $this->executionData['bot_id'] ?? null,
                    'symbol' => $this->executionData['symbol'] ?? null,
                    'direction' => $this->executionData['direction'] ?? null,
                    'error' => $errorMessage,
                    'recommendation' => 'Market is closed. Trade will retry when market reopens.',
                    'note' => 'This error indicates the broker rejected the trade because the market is currently closed.',
                ]);
            } else {
                Log::error('ExecutionJob: Exception during trade execution', [
                    'bot_id' => $this->executionData['bot_id'] ?? null,
                    'symbol' => $this->executionData['symbol'] ?? null,
                    'direction' => $this->executionData['direction'] ?? null,
                    'error' => $errorMessage,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'is_market_closed' => $isMarketClosedError,
            ];
        }
    }

    /**
     * Create position record
     */
    protected function createPosition(ExecutionConnection $connection, array $result): void
    {
        try {
            // Get execution log if available (from signal execution)
            $executionLogId = $result['execution_log_id'] ?? null;
            
            // Create a minimal execution log if needed
            if (!$executionLogId) {
                $executionLog = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::create([
                    'connection_id' => $connection->id,
                    'signal_id' => null, // Bot execution, not signal-based
                    'status' => $result['success'] ? 'executed' : 'failed',
                    'execution_type' => ($this->executionData['entry_price'] ?? null) ? 'limit' : 'market',
                    'symbol' => $this->executionData['symbol'] ?? '',
                    'direction' => $this->executionData['direction'] ?? '',
                    'quantity' => $this->executionData['quantity'] ?? 0,
                    'entry_price' => $this->executionData['entry_price'] ?? null,
                    'sl_price' => $this->executionData['stop_loss'] ?? null,
                    'tp_price' => $this->executionData['take_profit'] ?? null,
                    'executed_at' => now(),
                ]);
                $executionLogId = $executionLog->id;
            }

            // Get expected entry price (from signal or execution data)
            $expectedEntryPrice = $this->executionData['entry_price'];
            
            // Get actual execution price from exchange response
            $executionPrice = $result['data']['price'] ?? 
                             $result['data']['openPrice'] ?? 
                             $result['data']['average'] ?? 
                             $result['price'] ?? 
                             $result['average'] ?? 
                             null;
            
            // If execution price not in result, try to fetch from exchange
            if ($executionPrice === null) {
                try {
                    $adapter = $this->createAdapter($connection);
                    $accountInfo = $adapter->getAccountInfo();
                    
                    // Try to get current price from positions
                    if (isset($accountInfo['positions']) && !empty($accountInfo['positions'])) {
                        foreach ($accountInfo['positions'] as $position) {
                            if ($position['symbol'] === $this->executionData['symbol']) {
                                $executionPrice = $position['currentPrice'] ?? $position['openPrice'] ?? null;
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('ExecutionJob: Failed to fetch execution price from exchange', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Use execution price as entry price, or fallback to expected price
            $entryPrice = $executionPrice ?? $expectedEntryPrice;
            
            // If still null, use 0 and log warning
            if ($entryPrice === null || $entryPrice === 0) {
                $entryPrice = $expectedEntryPrice ?? 0;
                if ($entryPrice === 0) {
                    Log::warning('ExecutionJob: Could not determine entry price for market order, using 0', [
                        'symbol' => $this->executionData['symbol'],
                        'connection_id' => $connection->id,
                    ]);
                }
            }
            
            // Calculate slippage if we have both expected and execution prices
            $slippagePips = 0.0;
            if ($expectedEntryPrice !== null && $expectedEntryPrice > 0 && 
                $executionPrice !== null && $executionPrice > 0 && 
                $expectedEntryPrice !== $executionPrice) {
                $slippageService = app(SlippageProtectionService::class);
                $slippagePips = $slippageService->calculateSlippage(
                    $expectedEntryPrice,
                    $executionPrice,
                    $this->executionData['direction'],
                    $this->executionData['symbol']
                );
                
                // Validate slippage
                $maxSlippage = $slippageService->getMaxAllowedSlippage([]);
                $slippageValidation = $slippageService->validateSlippage($slippagePips, $maxSlippage);
                
                if (!$slippageValidation['acceptable']) {
                    Log::warning('ExecutionJob: Slippage exceeded on entry', [
                        'symbol' => $this->executionData['symbol'],
                        'expected_price' => $expectedEntryPrice,
                        'execution_price' => $executionPrice,
                        'slippage_pips' => $slippagePips,
                        'max_allowed' => $maxSlippage,
                    ]);
                }
            }

            // Prepare position data
            $positionData = [
                'connection_id' => $connection->id,
                'execution_log_id' => $executionLogId,
                'symbol' => $this->executionData['symbol'],
                'direction' => $this->executionData['direction'],
                'entry_price' => $entryPrice,
                'execution_price' => $executionPrice ?? $entryPrice, // Actual execution price from exchange
                'current_price' => $entryPrice,
                'sl_price' => $this->executionData['stop_loss'],
                'tp_price' => $this->executionData['take_profit'],
                'quantity' => $this->executionData['quantity'],
                'status' => 'open',
                'order_id' => $result['order_id'] ?? null,
                'slippage_pips' => $slippagePips,
            ];
            
            // Only set signal_id if column is nullable OR if we have a signal_id value
            $signalId = $this->executionData['signal_id'] ?? null;
            if ($signalId !== null) {
                // We have a signal_id, use it
                $positionData['signal_id'] = $signalId;
            } else {
                // Check if signal_id column is nullable before setting it to null
                $prefix = \Illuminate\Support\Facades\Schema::getConnection()->getTablePrefix();
                $tableName = $prefix . 'execution_positions';
                try {
                    $columnInfo = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM `{$tableName}` WHERE Field = 'signal_id'");
                    if (!empty($columnInfo) && isset($columnInfo[0]->Null) && $columnInfo[0]->Null === 'YES') {
                        $positionData['signal_id'] = null; // Bot execution, not signal-based
                    }
                    // If NOT NULL and we don't have signal_id, skip it (will fail gracefully or use default)
                } catch (\Exception $e) {
                    Log::warning('ExecutionPosition: Could not check signal_id column nullability', [
                        'error' => $e->getMessage()
                    ]);
                    // Try to set null anyway (migration might have run but check failed)
                    $positionData['signal_id'] = null;
                }
            }
            
            $executionPosition = ExecutionPosition::create($positionData);

            // If this is a bot execution, also create TradingBotPosition and update bot stats
            if (isset($this->executionData['bot_id'])) {
                $this->createTradingBotPosition($executionPosition);
                $this->updateBotStatistics($this->executionData['bot_id'], $result['success']);
            }

            // If this is a copy trading execution, update CopyTradingExecution
            if (isset($this->executionData['copy_trading_execution_id'])) {
                $this->updateCopyTradingExecution($executionPosition);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create position record', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create TradingBotPosition linked to ExecutionPosition
     */
    protected function createTradingBotPosition(ExecutionPosition $executionPosition): void
    {
        try {
            if (!\Schema::hasTable('trading_bot_positions')) {
                return; // Table doesn't exist yet
            }

            \Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition::create([
                'bot_id' => $this->executionData['bot_id'],
                'signal_id' => $this->executionData['signal_id'] ?? null,
                'execution_position_id' => $executionPosition->id,
                'symbol' => $this->executionData['symbol'],
                'direction' => $this->executionData['direction'],
                'entry_price' => $this->executionData['entry_price'],
                'current_price' => $this->executionData['entry_price'],
                'stop_loss' => $this->executionData['stop_loss'],
                'take_profit' => $this->executionData['take_profit'],
                'quantity' => $this->executionData['quantity'],
                'status' => 'open',
                'opened_at' => now(),
            ]);

            Log::info('TradingBotPosition created', [
                'bot_id' => $this->executionData['bot_id'],
                'execution_position_id' => $executionPosition->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create TradingBotPosition', [
                'bot_id' => $this->executionData['bot_id'] ?? null,
                'execution_position_id' => $executionPosition->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update CopyTradingExecution with follower execution log
     */
    protected function updateCopyTradingExecution(ExecutionPosition $executionPosition): void
    {
        try {
            if (!\Schema::hasTable('copy_trading_executions')) {
                return;
            }

            $copyExecution = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingExecution::find(
                $this->executionData['copy_trading_execution_id']
            );

            if ($copyExecution) {
                $copyExecution->update([
                    'follower_execution_log_id' => $executionPosition->execution_log_id,
                    'status' => 'executed',
                ]);

                Log::info('CopyTradingExecution updated', [
                    'execution_id' => $copyExecution->id,
                    'follower_execution_log_id' => $executionPosition->execution_log_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update CopyTradingExecution', [
                'copy_trading_execution_id' => $this->executionData['copy_trading_execution_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create adapter for execution connection using ExchangeConnectionService
     */
    protected function createAdapter(ExecutionConnection $connection)
    {
        $service = app(ExchangeConnectionService::class);
        
        // Hydrate ExchangeConnection model from ExecutionConnection data
        // They share the same table (execution_connections) so we can cast/hydrate safely
        // This avoids an extra DB query while ensuring the service gets the expected model class
        $exchangeConnection = new ExchangeConnection();
        $exchangeConnection->setRawAttributes($connection->getAttributes(), true);
        $exchangeConnection->exists = true;
        $exchangeConnection->setConnection($connection->getConnectionName()); // Preserve DB connection
        
        return $service->getAdapter($exchangeConnection);
    }

    /**
     * Update bot statistics after trade execution
     */
    protected function updateBotStatistics(int $botId, bool $success): void
    {
        try {
            $bot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::find($botId);
            if (!$bot) {
                return;
            }

            // Recalculate from database (more accurate)
            $executionQuery = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::where('connection_id', $bot->exchange_connection_id);
            $bot->total_executions = $executionQuery->count();
            $bot->successful_executions = (clone $executionQuery)->whereIn('status', ['executed', 'success', 'filled', 'completed'])->count();
            $bot->failed_executions = (clone $executionQuery)->whereIn('status', ['failed', 'rejected', 'cancelled'])->count();
            
            // Recalculate win rate
            if ($bot->total_executions > 0) {
                $bot->win_rate = ($bot->successful_executions / $bot->total_executions) * 100;
            } else {
                $bot->win_rate = 0;
            }
            
            // Update profit from TradingBotPosition
            if (\Schema::hasTable('trading_bot_positions')) {
                $positions = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition::where('bot_id', $botId)->get();
                $bot->total_profit = $positions->sum('profit_loss') ?? 0;
            } else {
                $bot->total_profit = 0;
            }
            
            $bot->save();
            
            Log::info('Bot statistics updated', [
                'bot_id' => $botId,
                'total_executions' => $bot->total_executions,
                'successful_executions' => $bot->successful_executions,
                'win_rate' => $bot->win_rate,
                'total_profit' => $bot->total_profit,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update bot statistics', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Setup bot-specific logging to write to trading-bot-{id}.log
     */
    protected function setupBotLogger(int $botId): void
    {
        try {
            $logPath = storage_path("logs/trading-bot-{$botId}.log");
            
            // Configure a custom log channel for this bot
            config(['logging.channels.trading-bot-' . $botId => [
                'driver' => 'single',
                'path' => $logPath,
                'level' => env('LOG_LEVEL', 'debug'),
            ]]);
            
            // Set this as the default channel for this job
            config(['logging.default' => 'trading-bot-' . $botId]);
            
            // Clear the log manager cache to pick up the new config
            app()->forgetInstance('log');
        } catch (\Exception $e) {
            // Fallback to default logging if setup fails
            Log::warning('ExecutionJob: Failed to setup bot logger, using default', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log market closed/stale data error to bot log
     */
    protected function logMarketClosedError(array $validation, ExecutionConnection $connection, ?int $botId): void
    {
        $freshnessCheck = $validation['freshness_check'] ?? [];
        
        Log::error('ExecutionJob: Trade rejected - Market closed or data stale', [
            'bot_id' => $botId,
            'connection_id' => $connection->id,
            'connection_name' => $connection->name,
            'symbol' => $this->executionData['symbol'] ?? null,
            'direction' => $this->executionData['direction'] ?? null,
            'reason' => $validation['reason'] ?? 'Unknown',
            'market_status' => $freshnessCheck['status'] ?? 'unknown',
            'data_age_minutes' => $freshnessCheck['age_minutes'] ?? null,
            'max_age_minutes' => $freshnessCheck['max_age_minutes'] ?? null,
            'last_candle_timestamp' => $freshnessCheck['last_timestamp'] ?? null,
            'recommendation' => 'Wait for market to open or check data stream connection',
        ]);
        
        // Also create failed execution log for tracking
        \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::create([
            'connection_id' => $connection->id,
            'signal_id' => $this->executionData['signal_id'] ?? null,
            'status' => 'failed',
            'execution_type' => ($this->executionData['entry_price'] ?? null) ? 'limit' : 'market',
            'symbol' => $this->executionData['symbol'] ?? '',
            'direction' => $this->executionData['direction'] ?? '',
            'quantity' => $this->executionData['quantity'] ?? 0,
            'entry_price' => $this->executionData['entry_price'] ?? null,
            'sl_price' => $this->executionData['stop_loss'] ?? null,
            'tp_price' => $this->executionData['take_profit'] ?? null,
            'error_message' => $validation['reason'] ?? 'Market validation failed',
        ]);
    }

    /**
     * Create virtual position for paper trading
     * Uses InternalBrokerService to create demo trades instead of real exchange trades
     */
    protected function createVirtualPosition(
        string $symbol,
        string $direction,
        float $quantity,
        ?float $entryPrice,
        ?float $stopLoss,
        ?float $takeProfit,
        ?int $connectionId = null
    ): array {
        try {
            $userId = $this->executionData['user_id'] ?? auth()->id();
            if (!$userId) {
                throw new \Exception('User ID not found in execution data');
            }

            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('User not found: ' . $userId);
            }

            // Use InternalBrokerService to place paper trading order
            $internalBrokerService = app(InternalBrokerService::class);
            $trade = $internalBrokerService->placeOrder(
                $user,
                $symbol,
                $direction,
                $quantity,
                $entryPrice ?? 0,
                $stopLoss,
                $takeProfit,
                true
            );

            Log::info('Paper trading position created', [
                'trade_id' => $trade->id,
                'user_id' => $userId,
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
                'entry_price' => $entryPrice,
                'stop_loss' => $stopLoss,
                'take_profit' => $takeProfit,
            ]);

            return [
                'success' => true,
                'trade_id' => $trade->id,
                'internal_trade' => true,
                'message' => 'Paper trading position created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create virtual position', [
                'error' => $e->getMessage(),
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create paper trading position'
            ];
        }
    }
}
