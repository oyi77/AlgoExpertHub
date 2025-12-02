<?php

namespace Addons\TradingBotSignalAddon\App\Listeners;

use Addons\TradingBotSignalAddon\App\Services\SignalProcessorService;
use Illuminate\Support\Facades\Log;

class NotificationListener
{
    protected $processor;

    public function __construct(SignalProcessorService $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Handle notification
     */
    public function handle(array $notification): void
    {
        try {
            // Check if it's a relevant notification
            if ($this->isRelevantNotification($notification)) {
                $this->processor->processNotification($notification);
                Log::info('Notification processed', ['notification_id' => $notification['id'] ?? null]);
            }
        } catch (\Exception $e) {
            Log::error('NotificationListener error: ' . $e->getMessage(), [
                'notification' => $notification
            ]);
        }
    }

    /**
     * Check if notification is relevant for signal processing
     */
    protected function isRelevantNotification(array $notification): bool
    {
        // Process if it has signal-related fields
        return !empty($notification['extracted_symbol']) ||
               !empty($notification['is_stop_signal']) ||
               !empty($notification['is_stop_loss']) ||
               !empty($notification['action']);
    }
}

