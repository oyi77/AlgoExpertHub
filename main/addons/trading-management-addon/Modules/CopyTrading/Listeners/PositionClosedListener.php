<?php

namespace Addons\TradingManagement\Modules\CopyTrading\Listeners;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\CopyTrading\Services\TradeCopyService;
use Illuminate\Support\Facades\Log;

/**
 * PositionClosedListener
 * 
 * Listens for ExecutionPosition close and closes copied positions
 */
class PositionClosedListener
{
    protected TradeCopyService $copyService;

    public function __construct(TradeCopyService $copyService)
    {
        $this->copyService = $copyService;
    }

    /**
     * Handle the ExecutionPosition "updated" event (when status changes to closed).
     */
    public function handle(ExecutionPosition $position): void
    {
        // Only process if position was just closed
        if ($position->status !== 'closed' || !$position->wasChanged('status')) {
            return;
        }

        // Only process user connections (not admin)
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
