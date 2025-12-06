<?php

namespace Addons\CopyTrading\App\Listeners;

use Addons\CopyTrading\App\Jobs\CloseCopiedPositionJob;
use Illuminate\Support\Facades\Log;

class PositionClosedListener
{
    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        try {
            // Check if ExecutionPosition class exists
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class)) {
                return;
            }
            
            $executionPositionClass = \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class;
            $position = $event instanceof $executionPositionClass ? $event : $event->model ?? null;
            
            if (!$position instanceof $executionPositionClass) {
                return;
            }

            // Only process if position was just closed
            if ($position->status !== 'closed' || !$position->wasChanged('status')) {
                return;
            }

            // Only process if has a connection with a user or admin
            if (!$position->connection) {
                return;
            }
            
            // Must have either user_id or admin_id
            if (!$position->connection->user_id && !$position->connection->admin_id) {
                return;
            }

            // Dispatch job to close copied positions asynchronously
            CloseCopiedPositionJob::dispatch($position->id);
        } catch (\Exception $e) {
            Log::error("PositionClosedListener error", [
                'position_id' => $position->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

