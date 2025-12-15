<?php

namespace Addons\TradingManagement\Modules\TradingBot\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderExecuted Event
 * 
 * Broadcasts when an order is executed (filled, cancelled, etc.)
 */
class OrderExecuted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $botId;
    public ?int $userId;
    public array $order;
    public string $action; // 'opened', 'closed', 'modified', 'cancelled'

    public function __construct(int $botId, ?int $userId, array $order, string $action)
    {
        $this->botId = $botId;
        $this->userId = $userId;
        $this->order = $order;
        $this->action = $action;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("admin.trading-bot.{$this->botId}"),
        ];
        
        if ($this->userId) {
            $channels[] = new PrivateChannel("user.{$this->userId}.trading-bot.{$this->botId}");
        }
        
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'bot_id' => $this->botId,
            'action' => $this->action,
            'order' => $this->order,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.executed';
    }
}

