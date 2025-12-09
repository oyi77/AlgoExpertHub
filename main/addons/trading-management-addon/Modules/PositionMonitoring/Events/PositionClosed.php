<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Events;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PositionClosed Event
 * 
 * Broadcasts position close events via WebSocket
 */
class PositionClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ExecutionPosition $position;

    public function __construct(ExecutionPosition $position)
    {
        $this->position = $position;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        $userId = $this->position->connection->user_id ?? null;
        if ($userId) {
            return new Channel('user.' . $userId);
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
