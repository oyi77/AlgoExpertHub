<?php

namespace Addons\TradingExecutionEngine\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Illuminate\Support\Facades\Log;

class PositionService
{
    protected ConnectionService $connectionService;
    protected NotificationService $notificationService;

    public function __construct(
        ConnectionService $connectionService,
        NotificationService $notificationService
    ) {
        $this->connectionService = $connectionService;
        $this->notificationService = $notificationService;
    }

    /**
     * Monitor and update all open positions.
     */
    public function monitorPositions(): void
    {
        $openPositions = ExecutionPosition::open()->get();

        foreach ($openPositions as $position) {
            try {
                $this->updatePosition($position);
                $this->handleTrailingStop($position);
                $this->handleBreakeven($position);
                $this->checkSlTp($position);
            } catch (\Exception $e) {
                Log::error("Failed to monitor position", [
                    'position_id' => $position->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Update position with current price and PnL.
     */
    public function updatePosition(ExecutionPosition $position): void
    {
        try {
            $connection = $position->connection;
            $adapter = $this->connectionService->getAdapter($connection);

            if (!$adapter) {
                return;
            }

            // Get current price
            $currentPrice = $adapter->getCurrentPrice($position->symbol);
            if ($currentPrice === null) {
                return;
            }

            // Update position PnL
            $position->updatePnL($currentPrice);

            // Check if position still exists on exchange
            $exchangePosition = $adapter->getPosition($position->order_id);
            if (!$exchangePosition) {
                // Position closed on exchange but not in our system
                $this->syncPositionFromExchange($position);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update position", [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check stop loss and take profit.
     */
    public function checkSlTp(ExecutionPosition $position): void
    {
        if (!$position->isOpen()) {
            return;
        }

        $currentPrice = $position->current_price ?? $position->entry_price;

        // Check stop loss
        if ($position->sl_price > 0) {
            $slHit = false;

            if ($position->direction === 'buy') {
                $slHit = $currentPrice <= $position->sl_price;
            } else {
                $slHit = $currentPrice >= $position->sl_price;
            }

            if ($slHit) {
                $this->closePosition($position, 'sl', $position->sl_price);
                $this->notificationService->notifySlTpHit($position, 'sl');
                return;
            }
        }

        // Check multiple take profits
        $signal = $position->signal;
        if ($signal && $signal->hasMultipleTps()) {
            // Check each TP level for partial closes
            $openTps = $signal->openTakeProfits()->orderBy('tp_level')->get();
            $remainingQuantity = $position->quantity;
            
            foreach ($openTps as $tp) {
                $tpHit = false;
                
                if ($position->direction === 'buy') {
                    $tpHit = $currentPrice >= $tp->tp_price;
                } else {
                    $tpHit = $currentPrice <= $tp->tp_price;
                }
                
                if ($tpHit) {
                    // Calculate partial close quantity
                    $closePercentage = $tp->lot_percentage ?? $tp->tp_percentage ?? (100 / $openTps->count());
                    $closeQuantity = ($remainingQuantity * $closePercentage) / 100;
                    
                    // Partial close
                    $this->partialClosePosition($position, $tp, $closeQuantity, $tp->tp_price);
                    
                    // Mark TP as closed
                    $tp->markAsClosed();
                    
                    // Update remaining quantity
                    $remainingQuantity -= $closeQuantity;
                    $position->quantity = $remainingQuantity;
                    $position->save();
                    
                    // If all quantity closed, fully close position
                    if ($remainingQuantity <= 0.001) {
                        $position->close('tp', $tp->tp_price);
                        $this->notificationService->notifySlTpHit($position, 'tp');
                        return;
                    }
                    
                    // Send notification for partial close
                    $this->notificationService->notifyPartialTpHit($position, $tp, $closeQuantity);
                }
            }
        } elseif ($position->tp_price > 0) {
            // Single TP (backward compatibility)
            $tpHit = false;

            if ($position->direction === 'buy') {
                $tpHit = $currentPrice >= $position->tp_price;
            } else {
                $tpHit = $currentPrice <= $position->tp_price;
            }

            if ($tpHit) {
                $this->closePosition($position, 'tp', $position->tp_price);
                $this->notificationService->notifySlTpHit($position, 'tp');
                return;
            }
        }
    }

    /**
     * Close a position manually or via SL/TP.
     */
    public function closePosition(ExecutionPosition $position, string $reason, ?float $closePrice = null): bool
    {
        try {
            $connection = $position->connection;
            $adapter = $this->connectionService->getAdapter($connection);

            if (!$adapter) {
                return false;
            }

            // Close on exchange
            $result = $adapter->closePosition($position->order_id);

            if ($result['success']) {
                $closePrice = $closePrice ?? $result['close_price'] ?? $position->current_price ?? $position->entry_price;
                $position->close($reason, $closePrice);
                $this->notificationService->notifyPositionClosed($position, $reason);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to close position", [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync position from exchange (in case it was closed externally).
     */
    protected function syncPositionFromExchange(ExecutionPosition $position): void
    {
        try {
            $connection = $position->connection;
            $adapter = $this->connectionService->getAdapter($connection);

            if (!$adapter) {
                return;
            }

            // Position doesn't exist on exchange, it was closed
            $closePrice = $position->current_price ?? $position->entry_price;
            $position->close('manual', $closePrice);
            $this->notificationService->notifyPositionClosed($position, 'external_close');
        } catch (\Exception $e) {
            Log::error("Failed to sync position from exchange", [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get open positions for a connection.
     */
    public function getOpenPositions(ExecutionConnection $connection)
    {
        return ExecutionPosition::open()
            ->byConnection($connection->id)
            ->get();
    }

    /**
     * Get closed positions for a connection.
     */
    public function getClosedPositions(ExecutionConnection $connection, int $limit = 100)
    {
        return ExecutionPosition::closed()
            ->byConnection($connection->id)
            ->orderBy('closed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Partial close position at a TP level.
     */
    protected function partialClosePosition(ExecutionPosition $position, $tp, float $closeQuantity, float $closePrice): bool
    {
        try {
            $connection = $position->connection;
            $adapter = $this->connectionService->getAdapter($connection);

            if (!$adapter) {
                return false;
            }

            // Partial close on exchange (if adapter supports it)
            $result = $adapter->partialClosePosition($position->order_id, $closeQuantity);

            if ($result['success']) {
                // Log partial close
                Log::info('Partial TP close', [
                    'position_id' => $position->id,
                    'tp_level' => $tp->tp_level,
                    'close_quantity' => $closeQuantity,
                    'close_price' => $closePrice,
                ]);

                // Update position quantity
                $position->quantity -= $closeQuantity;
                $position->save();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to partial close position", [
                'position_id' => $position->id,
                'tp_level' => $tp->tp_level,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle trailing stop loss.
     */
    protected function handleTrailingStop(ExecutionPosition $position): void
    {
        if (!$position->isOpen() || !$position->trailing_stop_enabled) {
            return;
        }

        $currentPrice = $position->current_price ?? $position->entry_price;
        if (!$currentPrice) {
            return;
        }

        // Update highest/lowest price
        if ($position->direction === 'buy') {
            if (!$position->highest_price || $currentPrice > $position->highest_price) {
                $position->highest_price = $currentPrice;
                $position->save();
            }

            // Calculate new trailing SL
            $trailingDistance = $position->trailing_stop_distance ?? 0;
            if ($position->trailing_stop_percentage) {
                $trailingDistance = ($position->highest_price * $position->trailing_stop_percentage) / 100;
            }

            $newSlPrice = $position->highest_price - $trailingDistance;

            // Only move SL up, never down
            if ($newSlPrice > $position->sl_price) {
                $this->updateStopLoss($position, $newSlPrice);
            }
        } else {
            // Sell position
            if (!$position->lowest_price || $currentPrice < $position->lowest_price) {
                $position->lowest_price = $currentPrice;
                $position->save();
            }

            // Calculate new trailing SL
            $trailingDistance = $position->trailing_stop_distance ?? 0;
            if ($position->trailing_stop_percentage) {
                $trailingDistance = ($position->lowest_price * $position->trailing_stop_percentage) / 100;
            }

            $newSlPrice = $position->lowest_price + $trailingDistance;

            // Only move SL down, never up
            if ($newSlPrice < $position->sl_price || $position->sl_price == 0) {
                $this->updateStopLoss($position, $newSlPrice);
            }
        }
    }

    /**
     * Handle moving SL to breakeven.
     */
    protected function handleBreakeven(ExecutionPosition $position): void
    {
        if (!$position->isOpen() || !$position->breakeven_enabled || $position->sl_moved_to_breakeven) {
            return;
        }

        $currentPrice = $position->current_price ?? $position->entry_price;
        if (!$currentPrice || !$position->breakeven_trigger_price) {
            return;
        }

        $shouldMoveToBreakeven = false;

        if ($position->direction === 'buy') {
            // For buy: move to breakeven when price reaches trigger (above entry)
            $shouldMoveToBreakeven = $currentPrice >= $position->breakeven_trigger_price;
        } else {
            // For sell: move to breakeven when price reaches trigger (below entry)
            $shouldMoveToBreakeven = $currentPrice <= $position->breakeven_trigger_price;
        }

        if ($shouldMoveToBreakeven) {
            // Move SL to entry price (breakeven)
            $this->updateStopLoss($position, $position->entry_price);
            $position->sl_moved_to_breakeven = true;
            $position->save();

            Log::info('SL moved to breakeven', [
                'position_id' => $position->id,
                'entry_price' => $position->entry_price,
            ]);
        }
    }

    /**
     * Update stop loss on exchange and in database.
     */
    protected function updateStopLoss(ExecutionPosition $position, float $newSlPrice): bool
    {
        try {
            $connection = $position->connection;
            $adapter = $this->connectionService->getAdapter($connection);

            if (!$adapter) {
                return false;
            }

            // Update on exchange
            $result = $adapter->updateStopLoss($position->order_id, $newSlPrice);

            if ($result['success']) {
                // Update in database
                $position->sl_price = $newSlPrice;
                $position->save();

                Log::info('Stop loss updated', [
                    'position_id' => $position->id,
                    'new_sl_price' => $newSlPrice,
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to update stop loss", [
                'position_id' => $position->id,
                'new_sl_price' => $newSlPrice,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update take profit on exchange and in database.
     */
    protected function updateTakeProfit(ExecutionPosition $position, float $newTpPrice): bool
    {
        try {
            $connection = $position->connection;
            $adapter = $this->connectionService->getAdapter($connection);

            if (!$adapter) {
                return false;
            }

            // Update on exchange
            $result = $adapter->updateTakeProfit($position->order_id, $newTpPrice);

            if ($result['success']) {
                // Update in database
                $position->tp_price = $newTpPrice;
                $position->save();

                Log::info('Take profit updated', [
                    'position_id' => $position->id,
                    'new_tp_price' => $newTpPrice,
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to update take profit", [
                'position_id' => $position->id,
                'new_tp_price' => $newTpPrice,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

