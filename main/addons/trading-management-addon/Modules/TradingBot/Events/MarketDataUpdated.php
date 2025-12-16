<?php

namespace Addons\TradingManagement\Modules\TradingBot\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MarketDataUpdated Event
 * 
 * Broadcasts market price updates in real-time via WebSocket
 */
class MarketDataUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $symbol;
    public float $bid;
    public float $ask;
    public float $last;
    public ?int $botId;
    public ?int $userId;

    public function __construct(string $symbol, float $bid, float $ask, float $last, ?int $botId = null, ?int $userId = null)
    {
        $this->symbol = $symbol;
        $this->bid = $bid;
        $this->ask = $ask;
        $this->last = $last;
        $this->botId = $botId;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel("market.{$this->symbol}"), // Public channel for market data
        ];
        
        if ($this->botId) {
            $channels[] = new PrivateChannel("admin.trading-bot.{$this->botId}");
        }
        
        if ($this->userId) {
            $channels[] = new PrivateChannel("user.{$this->userId}.market");
        }
        
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'symbol' => $this->symbol,
            'bid' => $this->bid,
            'ask' => $this->ask,
            'last' => $this->last,
            'spread' => round($this->ask - $this->bid, 8),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'market.updated';
    }
}

