<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Events;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PositionUpdated Event
 * 
 * Broadcasts position updates via WebSocket
 */
class PositionUpdated implements ShouldBroadcast
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
}
