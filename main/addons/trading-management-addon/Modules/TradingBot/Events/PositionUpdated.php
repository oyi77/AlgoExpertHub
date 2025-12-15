<?php

namespace Addons\TradingManagement\Modules\TradingBot\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PositionUpdated Event
 * 
 * Broadcasts position updates in real-time via WebSocket
 * 
 * Usage:
 *   1. Configure BROADCAST_DRIVER in .env (pusher, redis, etc.)
 *   2. Install Laravel Echo and Pusher/Soketi client on frontend
 *   3. Listen to channel: Echo.private(`trading-bot.${botId}`).listen('PositionUpdated', callback)
 * 
 * To enable real-time:
 *   - Set BROADCAST_DRIVER=pusher (or redis with laravel-echo-server)
 *   - Configure Pusher/Ably/Soketi credentials
 *   - Call event(new PositionUpdated($bot, $positions, $stats)) when positions change
 */
class PositionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $botId;
    public ?int $userId;
    public array $positions;
    public array $stats;

    /**
     * Create a new event instance.
     */
    public function __construct(int $botId, ?int $userId, array $positions, array $stats)
    {
        $this->botId = $botId;
        $this->userId = $userId;
        $this->positions = $positions;
        $this->stats = $stats;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Admin channel (admins can see all bots)
        $channels[] = new PrivateChannel("admin.trading-bot.{$this->botId}");
        
        // User channel (if bot belongs to a user)
        if ($this->userId) {
            $channels[] = new PrivateChannel("user.{$this->userId}.trading-bot.{$this->botId}");
        }
        
        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'bot_id' => $this->botId,
            'positions' => $this->positions,
            'stats' => $this->stats,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'position.updated';
    }
}

