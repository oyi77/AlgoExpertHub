<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Events;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BalanceUpdated Event
 * 
 * Broadcasts balance updates via WebSocket
 */
class BalanceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ExchangeConnection $connection;
    public array $balance;

    public function __construct(ExchangeConnection $connection, array $balance)
    {
        $this->connection = $connection;
        $this->balance = $balance;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        $userId = $this->connection->user_id ?? null;
        if ($userId) {
            return new Channel('user.' . $userId);
        }
        return new Channel('balance');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'balance.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'connection_id' => $this->connection->id,
            'balance' => $this->balance,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
