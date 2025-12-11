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
 * PositionClosed Event
 * 
 * Broadcasts position close events via WebSocket
 */
class PositionClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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
            Log::debug('PositionClosed: Could not load connection', [
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
        return 'position.closed';
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
            'exit_price' => $this->position->current_price,
            'quantity' => $this->position->quantity,
            'pnl' => $this->position->pnl,
            'pnl_percentage' => $this->position->pnl_percentage,
            'closed_reason' => $this->position->closed_reason,
            'closed_at' => $this->position->closed_at?->toIso8601String(),
        ];
    }
}
