<?php

namespace Addons\TradingManagement\Modules\TradingBot\Events;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BotStatusChanged Event
 * 
 * Fired when trading bot status changes (start, stop, pause, resume)
 */
class BotStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TradingBot $bot;
    public string $oldStatus;
    public string $newStatus;
    public ?int $executedByUserId;
    public ?int $executedByAdminId;

    /**
     * Create a new event instance
     */
    public function __construct(
        TradingBot $bot,
        string $oldStatus,
        string $newStatus,
        ?int $executedByUserId = null,
        ?int $executedByAdminId = null
    ) {
        $this->bot = $bot;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->executedByUserId = $executedByUserId;
        $this->executedByAdminId = $executedByAdminId;
    }
}
