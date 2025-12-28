<?php

namespace Addons\TradingManagement\Modules\TradingBot\Listeners;

use Addons\TradingManagement\Modules\TradingBot\Events\BotStatusChanged;
use Illuminate\Support\Facades\Log;

/**
 * LogBotStatusChange Listener
 * 
 * Logs bot status changes for audit trail
 */
class LogBotStatusChange
{
    /**
     * Handle the event
     */
    public function handle(BotStatusChanged $event): void
    {
        Log::info('Trading bot status changed', [
            'bot_id' => $event->botId,
            'status' => $event->status,
            'user_id' => $event->userId,
            'message' => $event->message,
            'metrics' => $event->metrics,
        ]);
    }
}
