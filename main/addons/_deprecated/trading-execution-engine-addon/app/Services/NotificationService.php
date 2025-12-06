<?php

namespace Addons\TradingExecutionEngine\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionNotification;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notify about execution.
     */
    public function notifyExecution(
        ExecutionConnection $connection,
        Signal $signal,
        ?ExecutionPosition $position = null,
        string $type = 'execution',
        string $message = 'Order executed'
    ): void {
        try {
            $userId = $connection->user_id;
            $adminId = $connection->admin_id;

            ExecutionNotification::create([
                'user_id' => $userId,
                'admin_id' => $adminId,
                'connection_id' => $connection->id,
                'signal_id' => $signal->id,
                'position_id' => $position?->id,
                'type' => $type,
                'title' => 'Order Executed',
                'message' => $message,
                'metadata' => [
                    'symbol' => $position?->symbol ?? $signal->pair?->name,
                    'direction' => $signal->direction,
                    'quantity' => $position?->quantity ?? 0,
                    'price' => $position?->entry_price ?? $signal->open_price,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create execution notification", [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id,
                'signal_id' => $signal->id,
            ]);
        }
    }

    /**
     * Notify about position opened.
     */
    public function notifyPositionOpened(ExecutionPosition $position): void
    {
        try {
            $connection = $position->connection;
            $signal = $position->signal;

            $this->notifyExecution(
                $connection,
                $signal,
                $position,
                'open',
                "Position opened: {$position->symbol} {$position->direction}"
            );
        } catch (\Exception $e) {
            Log::error("Failed to create position opened notification", [
                'error' => $e->getMessage(),
                'position_id' => $position->id,
            ]);
        }
    }

    /**
     * Notify about position closed.
     */
    public function notifyPositionClosed(ExecutionPosition $position, string $reason): void
    {
        try {
            $connection = $position->connection;
            $signal = $position->signal;

            $userId = $connection->user_id;
            $adminId = $connection->admin_id;

            ExecutionNotification::create([
                'user_id' => $userId,
                'admin_id' => $adminId,
                'connection_id' => $connection->id,
                'signal_id' => $signal->id,
                'position_id' => $position->id,
                'type' => 'close',
                'title' => 'Position Closed',
                'message' => "Position closed: {$position->symbol} - Reason: {$reason} - PnL: {$position->pnl}",
                'metadata' => [
                    'symbol' => $position->symbol,
                    'direction' => $position->direction,
                    'pnl' => $position->pnl,
                    'pnl_percentage' => $position->pnl_percentage,
                    'reason' => $reason,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create position closed notification", [
                'error' => $e->getMessage(),
                'position_id' => $position->id,
            ]);
        }
    }

    /**
     * Notify about SL/TP hit.
     */
    public function notifySlTpHit(ExecutionPosition $position, string $type): void
    {
        try {
            $connection = $position->connection;
            $signal = $position->signal;

            $userId = $connection->user_id;
            $adminId = $connection->admin_id;

            $title = $type === 'sl' ? 'Stop Loss Hit' : 'Take Profit Hit';

            ExecutionNotification::create([
                'user_id' => $userId,
                'admin_id' => $adminId,
                'connection_id' => $connection->id,
                'signal_id' => $signal->id,
                'position_id' => $position->id,
                'type' => $type === 'sl' ? 'sl_hit' : 'tp_hit',
                'title' => $title,
                'message' => "{$title}: {$position->symbol} - PnL: {$position->pnl}",
                'metadata' => [
                    'symbol' => $position->symbol,
                    'pnl' => $position->pnl,
                    'pnl_percentage' => $position->pnl_percentage,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create SL/TP hit notification", [
                'error' => $e->getMessage(),
                'position_id' => $position->id,
            ]);
        }
    }

    /**
     * Notify about error.
     */
    public function notifyError(
        ExecutionConnection $connection,
        ?Signal $signal = null,
        string $type = 'error',
        string $message = 'An error occurred'
    ): void {
        try {
            $userId = $connection->user_id;
            $adminId = $connection->admin_id;

            ExecutionNotification::create([
                'user_id' => $userId,
                'admin_id' => $adminId,
                'connection_id' => $connection->id,
                'signal_id' => $signal?->id,
                'type' => $type,
                'title' => 'Execution Error',
                'message' => $message,
                'metadata' => [
                    'error_type' => $type,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create error notification", [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id,
            ]);
        }
    }

    /**
     * Notify about liquidation.
     */
    public function notifyLiquidation(ExecutionPosition $position): void
    {
        try {
            $connection = $position->connection;
            $signal = $position->signal;

            $userId = $connection->user_id;
            $adminId = $connection->admin_id;

            ExecutionNotification::create([
                'user_id' => $userId,
                'admin_id' => $adminId,
                'connection_id' => $connection->id,
                'signal_id' => $signal->id,
                'position_id' => $position->id,
                'type' => 'liquidation',
                'title' => 'Position Liquidated',
                'message' => "Position liquidated: {$position->symbol} - Loss: {$position->pnl}",
                'metadata' => [
                    'symbol' => $position->symbol,
                    'pnl' => $position->pnl,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create liquidation notification", [
                'error' => $e->getMessage(),
                'position_id' => $position->id,
            ]);
        }
    }
}

