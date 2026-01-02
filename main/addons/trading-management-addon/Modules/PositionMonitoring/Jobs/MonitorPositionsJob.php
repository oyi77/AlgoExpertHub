<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Jobs;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\TradingBot\Services\PositionMonitoringService;
use Addons\TradingManagement\Modules\RiskManagement\Services\SlippageProtectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Schema;

/**
 * MonitorPositionsJob
 * 
 * Scheduled job to monitor all open positions, update prices, check SL/TP
 * Runs every minute
 */
class MonitorPositionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 300; // 5 minutes max

    public function handle(PositionMonitoringService $positionService): void
    {
        try {
            // Check if table and columns exist before querying
            if (!Schema::hasTable('trading_bots')) {
                Log::warning('Table trading_bots does not exist. Skipping position monitoring.');
                return;
            }
            
            // Get all active trading bots
            // Check if status column exists, otherwise use is_active only
            $query = TradingBot::where('is_active', true);
            
            if (Schema::hasColumn('trading_bots', 'status')) {
                $query->where('status', 'running');
            }
            
            $bots = $query->get();

            $totalChecked = 0;
            $slClosed = 0;
            $tpClosed = 0;

            foreach ($bots as $bot) {
                try {
                    $result = $positionService->monitorPositions($bot);
                    $totalChecked += $result['total_checked'] ?? 0;
                    $slClosed += $result['sl_closed'] ?? 0;
                    $tpClosed += $result['tp_closed'] ?? 0;
                } catch (\Exception $e) {
                    Log::error('Failed to monitor positions for bot', [
                        'bot_id' => $bot->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Also monitor ExecutionPositions (from signal-based execution)
            $this->monitorExecutionPositions();

            Log::info('Position monitoring completed', [
                'bots_checked' => $bots->count(),
                'total_positions_checked' => $totalChecked,
                'sl_closed' => $slClosed,
                'tp_closed' => $tpClosed,
            ]);
        } catch (\Exception $e) {
            Log::error('Position monitoring job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Monitor ExecutionPositions (from signal-based execution)
     */
    protected function monitorExecutionPositions(): void
    {
        try {
            // Check if table exists
            if (!Schema::hasTable('execution_positions')) {
                return; // Silently skip if table doesn't exist
            }
            
            $openPositions = ExecutionPosition::where('status', 'open')->get();

            foreach ($openPositions as $position) {
                try {
                    // Update current price
                    $this->updatePositionPrice($position);

                    // Check SL
                    if ($position->sl_price && $position->shouldCloseBySL($position->current_price)) {
                        $this->closePosition($position, 'stop_loss_hit');
                        continue;
                    }

                    // Check TP
                    if ($position->tp_price && $position->shouldCloseByTP($position->current_price)) {
                        $this->closePosition($position, 'take_profit_hit');
                        continue;
                    }

                    // Update PnL only if current_price is valid
                    if ($position->current_price && $position->current_price > 0) {
                        $position->updatePnL($position->current_price);
                    }

                    // Broadcast update via WebSocket
                    $this->broadcastPositionUpdate($position);
                } catch (\Exception $e) {
                    Log::error('Failed to monitor execution position', [
                        'position_id' => $position->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to monitor execution positions', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update position current price from exchange
     */
    protected function updatePositionPrice(ExecutionPosition $position): void
    {
        try {
            $connection = $position->connection;
            if (!$connection) {
                Log::warning('No connection found for position', ['position_id' => $position->id]);
                return;
            }

            $adapter = $this->getAdapter($connection, $position->id);
            if (!$adapter) {
                // Warning already logged in getAdapter if needed (only once per connection)
                return;
            }
            
            if (method_exists($adapter, 'getCurrentPrice')) {
                $result = $adapter->getCurrentPrice($position->symbol);
                if (isset($result['last'])) {
                    $position->current_price = $result['last'];
                }
            } elseif (method_exists($adapter, 'fetchCurrentPrice')) {
                $result = $adapter->fetchCurrentPrice($position->symbol);
                if ($result['success'] && isset($result['data']['last'])) {
                    $position->current_price = $result['data']['last'];
                }
            } elseif (method_exists($adapter, 'getTicker')) {
                $ticker = $adapter->getTicker($position->symbol);
                if ($ticker && isset($ticker['last'])) {
                    $position->current_price = $ticker['last'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update position price', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Close position
     */
    protected function closePosition(ExecutionPosition $position, string $reason): void
    {
        try {
            $connection = $position->connection;
            if (!$connection) {
                Log::warning('No connection found for position', ['position_id' => $position->id]);
                return;
            }

            // Determine expected close price (SL or TP price)
            $expectedPrice = null;
            if ($reason === 'stop_loss_hit' && $position->sl_price) {
                $expectedPrice = $position->sl_price;
            } elseif ($reason === 'take_profit_hit' && $position->tp_price) {
                $expectedPrice = $position->tp_price;
            } else {
                $expectedPrice = $position->current_price;
            }

            $adapter = $this->getAdapter($connection, $position->id);
            $executionPrice = $expectedPrice; // Default to expected price if adapter unavailable
            $slippagePips = 0.0;

            if (!$adapter) {
                // Warning already logged in getAdapter if needed (only once per connection)
                // Still update position status even if we can't close on exchange
                $position->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'closed_reason' => $reason . '_local_only',
                    'execution_price' => $executionPrice,
                    'slippage_pips' => $slippagePips,
                ]);
                return;
            }

            // Close on exchange and get actual execution price
            if (method_exists($adapter, 'closePosition')) {
                $result = $adapter->closePosition($position->order_id, $position->symbol);
                
                if ($result['success'] && isset($result['price'])) {
                    $executionPrice = (float) $result['price'];
                } elseif ($result['success'] && isset($result['data']['price'])) {
                    $executionPrice = (float) $result['data']['price'];
                } elseif ($result['success'] && isset($result['data']['average'])) {
                    $executionPrice = (float) $result['data']['average'];
                }
                
                // Calculate slippage
                if ($expectedPrice > 0 && $executionPrice > 0) {
                    $slippageService = app(SlippageProtectionService::class);
                    $slippagePips = $slippageService->calculateSlippage(
                        $expectedPrice,
                        $executionPrice,
                        $position->direction === 'buy' ? 'sell' : 'buy', // Opposite direction for closing
                        $position->symbol
                    );
                    
                    // Validate slippage
                    $maxSlippage = $slippageService->getMaxAllowedSlippage([]);
                    $slippageValidation = $slippageService->validateSlippage($slippagePips, $maxSlippage);
                    
                    if (!$slippageValidation['acceptable']) {
                        Log::warning('Slippage exceeded on position close', [
                            'position_id' => $position->id,
                            'reason' => $reason,
                            'slippage_pips' => $slippagePips,
                            'max_allowed' => $maxSlippage,
                            'expected_price' => $expectedPrice,
                            'execution_price' => $executionPrice,
                        ]);
                    }
                }
                
                if (!$result['success']) {
                    Log::warning('Failed to close position on exchange', [
                        'position_id' => $position->id,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            }

            // Update position status with execution price and slippage
            $position->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_reason' => $reason,
                'execution_price' => $executionPrice,
                'slippage_pips' => $slippagePips,
            ]);

            // Update PnL using execution price
            $position->updatePnL($executionPrice);

            // Broadcast close event
            $this->broadcastPositionClose($position);

            Log::info('Position closed', [
                'position_id' => $position->id,
                'reason' => $reason,
                'expected_price' => $expectedPrice,
                'execution_price' => $executionPrice,
                'slippage_pips' => $slippagePips,
                'pnl' => $position->pnl,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to close position', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast position update via WebSocket
     */
    protected function broadcastPositionUpdate(ExecutionPosition $position): void
    {
        // Skip broadcasting if not configured
        if (config('broadcasting.default') === 'null' || !config('broadcasting.default')) {
            return;
        }

        try {
            // Broadcast to user's private channel
            if ($position->connection && $position->connection->user_id) {
                event(new \Addons\TradingManagement\Modules\PositionMonitoring\Events\PositionUpdated($position));
            }
        } catch (\Exception $e) {
            // Silently fail - WebSocket broadcasting is optional
            Log::debug('WebSocket broadcast failed', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast position close via WebSocket
     */
    protected function broadcastPositionClose(ExecutionPosition $position): void
    {
        try {
            if ($position->connection && $position->connection->user_id) {
                event(new \Addons\TradingManagement\Modules\PositionMonitoring\Events\PositionClosed($position));
            }
        } catch (\Exception $e) {
            // Silently fail - WebSocket broadcasting is optional
            Log::debug('WebSocket broadcast failed', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track which connections we've already warned about (to avoid spam)
     * Format: [connection_id => timestamp] - only warn once per hour per connection
     */
    protected static $warnedConnections = [];

    /**
     * Get adapter for connection
     */
    protected function getAdapter($executionConnection, $positionId = null)
    {
        try {
            // ExecutionConnection should have a related DataConnection
            $dataConnection = $executionConnection->dataConnection;
            
            if (!$dataConnection) {
                // Only log warning once per hour per connection to avoid log spam
                $connectionId = $executionConnection->id;
                $lastWarning = static::$warnedConnections[$connectionId] ?? null;
                $shouldWarn = !$lastWarning || (now()->timestamp - $lastWarning) > 3600; // 1 hour
                
                if ($shouldWarn) {
                    Log::warning('No DataConnection linked to ExecutionConnection - skipping price updates', [
                        'execution_connection_id' => $connectionId,
                        'connection_name' => $executionConnection->name ?? 'Unknown',
                        'exchange_type' => $executionConnection->exchange_type ?? 'unknown',
                        'exchange_name' => $executionConnection->exchange_name ?? 'unknown',
                        'note' => 'Positions with this connection will not have price updates until DataConnection is linked. Please edit the ExecutionConnection and assign a DataConnection.',
                        'help' => 'To fix: Go to Execution Connections, edit this connection, and assign a DataConnection for price updates.',
                    ]);
                    static::$warnedConnections[$connectionId] = now()->timestamp;
                }
                return null;
            }

            $adapterFactory = app(\Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory::class);
            return $adapterFactory->create($dataConnection);
        } catch (\Exception $e) {
            Log::error('Failed to create adapter', [
                'execution_connection_id' => $executionConnection->id ?? null,
                'connection_name' => $executionConnection->name ?? 'Unknown',
                'position_id' => $positionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
