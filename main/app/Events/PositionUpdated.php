<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PositionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public array $position;

    public function __construct(int $userId, array $position)
    {
        $this->userId = $userId;
        $this->position = $position;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->userId . '.positions');
    }

    public function broadcastAs()
    {
        return 'position.updated';
    }

    public function broadcastWith()
    {
        return [
            'position' => $this->position,
            'timestamp' => now()->timestamp,
        ];
    }
}
