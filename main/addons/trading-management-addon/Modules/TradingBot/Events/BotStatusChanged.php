<?php

namespace Addons\TradingManagement\Modules\TradingBot\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BotStatusChanged Event
 * 
 * Broadcasts bot status changes (started, stopped, error, etc.)
 */
class BotStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $botId;
    public ?int $userId;
    public string $status; // 'running', 'stopped', 'error', 'paused'
    public ?string $message;
    public array $metrics;

    public function __construct(int $botId, ?int $userId, string $status, ?string $message = null, array $metrics = [])
    {
        $this->botId = $botId;
        $this->userId = $userId;
        $this->status = $status;
        $this->message = $message;
        $this->metrics = $metrics;
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
            'status' => $this->status,
            'message' => $this->message,
            'metrics' => $this->metrics,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bot.status';
    }
}
