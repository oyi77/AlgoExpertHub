<?php

namespace App\Jobs;

use App\Models\Signal;
use App\Models\User;
use App\Services\TelegramChannelService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SignalNotificationMail;

class SendSignalNotificationJob extends OptimizedJob
{
    protected string $priority = 'default';
    public int $tries = 3;
    public int $timeout = 60;

    protected int $signalId;
    protected int $userId;

    public function __construct(int $signalId, int $userId)
    {
        $this->signalId = $signalId;
        $this->userId = $userId;
        $this->tags = ['signal-notification', 'user-communication'];
    }

    protected function process(): void
    {
        $signal = Signal::with(['pair:id,name', 'time:id,name', 'market:id,name'])
            ->find($this->signalId);
            
        $user = User::find($this->userId);

        if (!$signal || !$user) {
            Log::warning('Signal or user not found for notification', [
                'signal_id' => $this->signalId,
                'user_id' => $this->userId
            ]);
            return;
        }

        // Check user's notification preferences
        $preferences = $this->getUserNotificationPreferences($user);
        
        $notificationsSent = [];

        // Send Telegram notification if enabled
        if ($preferences['telegram'] && $user->telegram_chat_id) {
            try {
                $this->sendTelegramNotification($signal, $user);
                $notificationsSent[] = 'telegram';
            } catch (\Throwable $e) {
                Log::error('Failed to send Telegram notification', [
                    'signal_id' => $this->signalId,
                    'user_id' => $this->userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send email notification if enabled
        if ($preferences['email'] && $user->email) {
            try {
                $this->sendEmailNotification($signal, $user);
                $notificationsSent[] = 'email';
            } catch (\Throwable $e) {
                Log::error('Failed to send email notification', [
                    'signal_id' => $this->signalId,
                    'user_id' => $this->userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send SMS notification if enabled
        if ($preferences['sms'] && $user->phone) {
            try {
                $this->sendSmsNotification($signal, $user);
                $notificationsSent[] = 'sms';
            } catch (\Throwable $e) {
                Log::error('Failed to send SMS notification', [
                    'signal_id' => $this->signalId,
                    'user_id' => $this->userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Signal notification sent', [
            'signal_id' => $this->signalId,
            'user_id' => $this->userId,
            'channels' => $notificationsSent
        ]);
    }

    /**
     * Get user's notification preferences
     */
    protected function getUserNotificationPreferences(User $user): array
    {
        // Check user's current plan for notification settings
        $subscription = $user->currentplan()->first();
        
        if (!$subscription || !$subscription->plan) {
            return [
                'telegram' => false,
                'email' => false,
                'sms' => false
            ];
        }

        $plan = $subscription->plan;
        
        return [
            'telegram' => $plan->telegram ?? true,
            'email' => $plan->email ?? true,
            'sms' => $plan->sms ?? false
        ];
    }

    /**
     * Send Telegram notification
     */
    protected function sendTelegramNotification(Signal $signal, User $user): void
    {
        $telegramService = app(TelegramChannelService::class);
        
        $message = $this->formatSignalMessage($signal);
        
        $telegramService->sendMessage($user->telegram_chat_id, $message);
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(Signal $signal, User $user): void
    {
        Mail::to($user->email)->queue(new SignalNotificationMail($signal, $user));
    }

    /**
     * Send SMS notification
     */
    protected function sendSmsNotification(Signal $signal, User $user): void
    {
        $message = $this->formatSignalMessage($signal, true); // Short format for SMS
        
        // Use your SMS service here (Vonage, Twilio, etc.)
        // This is a placeholder implementation
        try {
            $basic = new \Vonage\Client\Credentials\Basic(config("services.nexmo.key"), config("services.nexmo.secret"));
            $client = new \Vonage\Client($basic);
            
            $client->sms()->send(
                new \Vonage\SMS\Message\SMS(
                    $user->phone,
                    config('app.name'),
                    $message
                )
            );
        } catch (\Throwable $e) {
            Log::error('SMS service error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Format signal message for notifications
     */
    protected function formatSignalMessage(Signal $signal, bool $shortFormat = false): string
    {
        if ($shortFormat) {
            return sprintf(
                "🔔 %s\n📈 %s %s\n💰 Entry: %s | SL: %s | TP: %s",
                $signal->title,
                $signal->pair->name,
                strtoupper($signal->direction),
                $signal->open_price,
                $signal->sl,
                $signal->tp
            );
        }

        return sprintf(
            "🔔 *New Signal Alert*\n\n" .
            "📊 *Title:* %s\n" .
            "📈 *Pair:* %s\n" .
            "🔄 *Direction:* %s\n" .
            "💰 *Entry Price:* %s\n" .
            "🛑 *Stop Loss:* %s\n" .
            "🎯 *Take Profit:* %s\n" .
            "⏰ *Timeframe:* %s\n" .
            "🏦 *Market:* %s\n\n" .
            "📝 *Description:*\n%s",
            $signal->title,
            $signal->pair->name,
            strtoupper($signal->direction),
            $signal->open_price,
            $signal->sl,
            $signal->tp,
            $signal->time->name,
            $signal->market->name,
            strip_tags($signal->description ?? 'No additional details')
        );
    }

    /**
     * Handle job failure
     */
    protected function onFailure(\Throwable $exception): void
    {
        Log::error('Signal notification job failed permanently', [
            'signal_id' => $this->signalId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage()
        ]);
        
        // Could implement fallback notification method here
        // or add to a manual review queue
    }
}