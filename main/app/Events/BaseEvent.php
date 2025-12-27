<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base Event Class
 * 
 * All domain events should extend this class
 */
abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Get the event name for logging/debugging
     */
    public function getEventName(): string
    {
        return class_basename($this);
    }
}
