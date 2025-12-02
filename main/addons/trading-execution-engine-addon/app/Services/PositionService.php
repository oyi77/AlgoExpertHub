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

        // Check take profit
        if ($position->tp_price > 0) {
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
}

