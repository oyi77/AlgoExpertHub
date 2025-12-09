<?php

namespace Addons\TradingManagement\Modules\CopyTrading\Observers;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\CopyTrading\Jobs\CopyTradeJob;
use Addons\TradingManagement\Modules\CopyTrading\Services\TradeCopyService;
use Illuminate\Support\Facades\Log;

/**
 * ExecutionPositionObserver
 * 
 * Handles copy trading when positions are created/closed
 */
class ExecutionPositionObserver
{
    protected TradeCopyService $copyService;

    public function __construct(TradeCopyService $copyService)
    {
        $this->copyService = $copyService;
    }

    /**
     * Handle the ExecutionPosition "created" event.
     */
    public function created(ExecutionPosition $position): void
    {
        // Only copy if position is open and from a user connection
        if ($position->status !== 'open') {
            return;
        }

        $connection = $position->connection;
        if (!$connection || !$connection->user_id) {
            return; // Admin connection or no user, skip copy trading
        }

        // Check if connection has copy trading enabled
        if (method_exists($connection, 'canCopyTrade') && !$connection->canCopyTrade()) {
            return;
        }

        // Only copy signal-based positions
        if (!$position->signal_id) {
            return; // Manual trade, skip
        }

        try {
            // Dispatch copy trading job
            CopyTradeJob::dispatch($position);

            Log::info('Copy trading job dispatched', [
                'position_id' => $position->id,
                'trader_id' => $connection->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch copy trading job', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ExecutionPosition "updated" event.
     */
    public function updated(ExecutionPosition $position): void
    {
        // Only process if position was just closed
        if ($position->status !== 'closed' || !$position->wasChanged('status')) {
            return;
        }

        // Only process user connections
        $connection = $position->connection;
        if (!$connection || !$connection->user_id) {
            return;
        }

        try {
            // Close all copied positions
            $this->copyService->closeCopiedPositions($position);

            Log::info('Copied positions closed', [
                'trader_position_id' => $position->id,
                'trader_id' => $connection->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to close copied positions', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
