<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketPriceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $symbol;
    public float $price;
    public array $stats;

    public function __construct(string $symbol, float $price, array $stats = [])
    {
        $this->symbol = $symbol;
        $this->price = $price;
        $this->stats = $stats;
    }

    public function broadcastOn()
    {
        return new Channel('market.' . strtolower($this->symbol));
    }

    public function broadcastAs()
    {
        return 'price.updated';
    }

    public function broadcastWith()
    {
        return [
            'symbol' => $this->symbol,
            'price' => $this->price,
            'stats' => $this->stats,
            'timestamp' => now()->timestamp,
        ];
    }
}
