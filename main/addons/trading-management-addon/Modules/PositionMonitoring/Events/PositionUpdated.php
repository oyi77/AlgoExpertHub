<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Events;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * PositionUpdated Event
 * 
 * Broadcasts position updates via WebSocket
 */
class PositionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Maximum number of times the job may be attempted.
     * Set to 3 to prevent infinite retries on broadcast failures.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [5, 10, 30];

    public ExecutionPosition $position;
    public ?int $userId;

    public function __construct(ExecutionPosition $position)
    {
        // Store position without any eager loaded relationships to prevent serialization issues
        $this->position = $position->withoutRelations();
        
        // Extract user_id immediately to avoid loading relationships during deserialization
        try {
            // Get user_id directly from connection_id to avoid loading relationships
            // Use fresh query to avoid any cached relationships
            $connectionId = $position->connection_id;
            if ($connectionId) {
                $connection = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::without(['dataConnection', 'preset', 'user', 'admin'])
                    ->find($connectionId);
                $this->userId = $connection->user_id ?? null;
            } else {
                $this->userId = null;
            }
        } catch (\Exception $e) {
            // If connection or dataConnection table doesn't exist, gracefully handle it
            Log::debug('PositionUpdated: Could not load connection', [
                'position_id' => $position->id,
                'error' => $e->getMessage()
            ]);
            $this->userId = null;
        }
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        // Skip broadcasting if not configured
        if (config('broadcasting.default') === 'null' || !config('broadcasting.default')) {
            // Return a dummy channel to prevent errors, but broadcasting will be skipped
            return new Channel('positions');
        }

        if ($this->userId) {
            return new Channel('user.' . $this->userId);
        }
        return new Channel('positions');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'position.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'position_id' => $this->position->id,
            'symbol' => $this->position->symbol,
            'direction' => $this->position->direction,
            'entry_price' => $this->position->entry_price,
            'current_price' => $this->position->current_price,
            'sl_price' => $this->position->sl_price,
            'tp_price' => $this->position->tp_price,
            'quantity' => $this->position->quantity,
            'pnl' => $this->position->pnl,
            'pnl_percentage' => $this->position->pnl_percentage,
            'status' => $this->position->status,
            'updated_at' => $this->position->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Handle a job failure.
     * 
     * Called when the broadcast job fails after all retry attempts.
     * Logs the failure but doesn't throw an exception to prevent queue blocking.
     */
    public function failed(\Throwable $exception): void
    {
        Log::warning('PositionUpdated broadcast failed after all retries', [
            'position_id' => $this->position->id ?? null,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'broadcast_driver' => config('broadcasting.default'),
        ]);
        
        // Don't rethrow - this is a non-critical feature (WebSocket broadcasting)
        // The position update itself succeeded, only the broadcast failed
    }
}
