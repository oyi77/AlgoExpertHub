<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Signal;

/**
 * Signal Created Event
 * 
 * Fired when a new trading signal is created
 */
class SignalCreated extends BaseEvent
{
    public function __construct(
        public Signal $signal
    ) {}
}
