<?php

namespace Addons\TradingManagement\Modules\CopyTrading\Listeners;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\CopyTrading\Jobs\CopyTradeJob;
use Illuminate\Support\Facades\Log;

/**
 * PositionCreatedListener
 * 
 * Listens for new ExecutionPosition creation and triggers copy trading
 */
class PositionCreatedListener
{
    /**
     * Handle the ExecutionPosition "created" event.
     */
    public function handle(ExecutionPosition $position): void
    {
        // Only copy if position is open and from a user connection (not admin)
        if ($position->status !== 'open') {
            return;
        }

        // Check if connection belongs to a user (not admin)
        $connection = $position->connection;
        if (!$connection || !$connection->user_id) {
            return; // Admin connection or no user, skip copy trading
        }

        // Check if connection has copy trading enabled
        if (!$connection->canCopyTrade()) {
            return;
        }

        // Only copy signal-based positions (not manual trades)
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
}
