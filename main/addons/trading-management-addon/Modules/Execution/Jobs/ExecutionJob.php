<?php

namespace Addons\TradingManagement\Modules\Execution\Jobs;

use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        Log::info('ExecutionJob: Starting trade execution', [
            'bot_id' => $this->executionData['bot_id'] ?? null,
            'connection_id' => $this->executionData['connection_id'] ?? null,
            'symbol' => $this->executionData['symbol'] ?? null,
            'direction' => $this->executionData['direction'] ?? null,
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

            // Get adapter for connection - create directly based on connection type
            $adapter = $this->createAdapter($connection);

            Log::info('ExecutionJob: Adapter created, executing trade', [
                'connection_id' => $connection->id,
                'provider' => $connection->provider,
                'symbol' => $this->executionData['symbol'],
                'direction' => $this->executionData['direction'],
            ]);

            // Execute trade
            $result = $this->executeTrade($adapter, $connection);

            if ($result['success']) {
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
                ]);
            } else {
                Log::error('ExecutionJob: Trade execution failed', [
                    'connection_id' => $connection->id,
                    'bot_id' => $this->executionData['bot_id'] ?? null,
                    'symbol' => $this->executionData['symbol'],
                    'direction' => $this->executionData['direction'],
                    'error' => $result['error'] ?? 'Unknown error',
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
            Log::error('ExecutionJob: Exception during trade execution', [
                'symbol' => $this->executionData['symbol'] ?? null,
                'direction' => $this->executionData['direction'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
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
                    'execution_type' => $entryPrice ? 'limit' : 'market',
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

            // Prepare position data
            $positionData = [
                'connection_id' => $connection->id,
                'execution_log_id' => $executionLogId,
                'symbol' => $this->executionData['symbol'],
                'direction' => $this->executionData['direction'],
                'entry_price' => $this->executionData['entry_price'],
                'current_price' => $this->executionData['entry_price'],
                'sl_price' => $this->executionData['stop_loss'],
                'tp_price' => $this->executionData['take_profit'],
                'quantity' => $this->executionData['quantity'],
                'status' => 'open',
                'order_id' => $result['order_id'] ?? null,
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
     * Create adapter for execution connection
     */
    /**
     * Get adapter from ExchangeConnectionService
     */
    protected function createAdapter(ExecutionConnection $connection)
    {
        // Try to find the linked ExchangeConnection model if this is an ExecutionConnection
        // Note: ExecutionConnection might be an alias or legacy model. 
        // We assume we can cast or find the ExchangeConnection.
        
        $service = app(\Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService::class);
        
        // If ExecutionConnection is just an ExchangeConnection (same table/model), pass it directly
        // If they are different, we might need to find the ExchangeConnection.
        // Based on analysis, they seem to share structure or be related.
        // Let's assume for now we can pass it if it implements contract or keys exist.
        // Actually, ExchangeConnectionService expects ExchangeConnection model.
        
        // Hack: Create ExchangeConnection instance from ExecutionConnection data if needed
        // Or better: Use the adapter creation logic BUT using the proper classes.
        
        // Since we already updated Adapters to be consistent, we can just instantiate them matching ExchangeConnectionService logic
        // but it's better to use the Service if possible.
        
        // If ExecutionConnection is legacy, we might need manual instantiation.
        // But we want to use the new Adapters we updated.
        
        $provider = $connection->provider ?? $connection->exchange_name ?? 'binance';
        $credentials = $connection->credentials ?? [];
        
        if ($provider === 'metaapi') {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter($credentials);
        } elseif (strpos($provider, 'mtapi') !== false) {
             return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter($credentials);
        }
        
        // Default CCXT
        return new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter($provider, $credentials);
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
}
