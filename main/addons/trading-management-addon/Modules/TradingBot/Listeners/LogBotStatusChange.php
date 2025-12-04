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
            'bot_id' => $event->bot->id,
            'bot_name' => $event->bot->name,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'executed_by_user_id' => $event->executedByUserId,
            'executed_by_admin_id' => $event->executedByAdminId,
        ]);
    }
}
