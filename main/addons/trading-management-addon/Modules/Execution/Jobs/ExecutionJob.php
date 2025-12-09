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
        try {
            $connection = ExecutionConnection::find($this->executionData['connection_id']);
            if (!$connection || !$connection->canExecuteTrades()) {
                Log::warning('Connection not available for execution', [
                    'connection_id' => $this->executionData['connection_id'],
                ]);
                return;
            }

            // Get adapter for connection
            $adapterFactory = app(AdapterFactory::class);
            $adapter = $adapterFactory->create($connection->provider, $connection->credentials ?? []);

            // Execute trade
            $result = $this->executeTrade($adapter, $connection);

            if ($result['success']) {
                // Create position record
                $this->createPosition($connection, $result);
                
                Log::info('Trade executed successfully', [
                    'connection_id' => $connection->id,
                    'bot_id' => $this->executionData['bot_id'] ?? null,
                    'order_id' => $result['order_id'] ?? null,
                ]);
            } else {
                Log::error('Trade execution failed', [
                    'connection_id' => $connection->id,
                    'bot_id' => $this->executionData['bot_id'] ?? null,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Execution job failed', [
                'execution_data' => $this->executionData,
                'error' => $e->getMessage(),
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

            // Determine order type (market or limit)
            $orderType = $entryPrice ? 'limit' : 'market';

            if ($orderType === 'limit') {
                $result = $adapter->placeLimitOrder(
                    $symbol,
                    $direction,
                    $quantity,
                    $entryPrice,
                    $stopLoss,
                    $takeProfit,
                    'Bot: ' . ($this->executionData['bot_id'] ?? 'N/A')
                );
            } else {
                $result = $adapter->placeMarketOrder(
                    $symbol,
                    $direction,
                    $quantity,
                    $stopLoss,
                    $takeProfit,
                    'Bot: ' . ($this->executionData['bot_id'] ?? 'N/A')
                );
            }

            return [
                'success' => $result['success'] ?? false,
                'order_id' => $result['orderId'] ?? $result['order_id'] ?? null,
                'position_id' => $result['positionId'] ?? $result['position_id'] ?? null,
                'data' => $result,
            ];

        } catch (\Exception $e) {
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
                    'status' => $result['success'] ? 'success' : 'failed',
                    'message' => 'Bot execution',
                    'executed_at' => now(),
                ]);
                $executionLogId = $executionLog->id;
            }

            $executionPosition = ExecutionPosition::create([
                'connection_id' => $connection->id,
                'execution_log_id' => $executionLogId,
                'signal_id' => $this->executionData['signal_id'] ?? null,
                'symbol' => $this->executionData['symbol'],
                'direction' => $this->executionData['direction'],
                'entry_price' => $this->executionData['entry_price'],
                'current_price' => $this->executionData['entry_price'],
                'sl_price' => $this->executionData['stop_loss'],
                'tp_price' => $this->executionData['take_profit'],
                'quantity' => $this->executionData['quantity'],
                'status' => 'open',
                'order_id' => $result['order_id'] ?? null,
            ]);

            // If this is a bot execution, also create TradingBotPosition
            if (isset($this->executionData['bot_id'])) {
                $this->createTradingBotPosition($executionPosition);
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
}
