<?php

namespace Addons\TradingBotSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App\Models\ChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Jobs\ProcessChannelMessage;
use Illuminate\Support\Facades\Log;

class SignalProcessorService
{
    protected $firebaseService;
    protected $channelSource;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Set the channel source for this processor
     */
    public function setChannelSource(ChannelSource $channelSource): void
    {
        $this->channelSource = $channelSource;
    }

    /**
     * Process a notification and convert to signal message
     */
    public function processNotification(array $notification): ?ChannelMessage
    {
        if (!$this->channelSource) {
            Log::error('Channel source not set for SignalProcessorService');
            return null;
        }

        try {
            // Extract signal data from notification
            $messageText = $this->extractSignalFromNotification($notification);

            if (empty($messageText)) {
                return null;
            }

            // Generate message hash
            $timestamp = $notification['timestamp'] ?? time();
            $messageHash = ChannelMessage::generateHash($messageText, $timestamp);

            // Check for duplicate
            $existingMessage = ChannelMessage::where('message_hash', $messageHash)
                ->where('channel_source_id', $this->channelSource->id)
                ->where('created_at', '>=', now()->subDay())
                ->first();

            if ($existingMessage) {
                return null;
            }

            // Create channel message
            $channelMessage = ChannelMessage::create([
                'channel_source_id' => $this->channelSource->id,
                'raw_message' => $messageText,
                'message_hash' => $messageHash,
                'status' => 'pending',
            ]);

            // Dispatch job to process message
            ProcessChannelMessage::dispatch($channelMessage);

            return $channelMessage;
        } catch (\Exception $e) {
            Log::error('Failed to process notification: ' . $e->getMessage(), [
                'notification' => $notification
            ]);
            return null;
        }
    }

    /**
     * Process a signal directly
     */
    public function processSignal(array $signal): ?ChannelMessage
    {
        if (!$this->channelSource) {
            Log::error('Channel source not set for SignalProcessorService');
            return null;
        }

        try {
            $messageText = $this->formatSignalAsMessage($signal);

            if (empty($messageText)) {
                return null;
            }

            $timestamp = $signal['timestamp'] ?? time();
            $messageHash = ChannelMessage::generateHash($messageText, $timestamp);

            // Check for duplicate
            $existingMessage = ChannelMessage::where('message_hash', $messageHash)
                ->where('channel_source_id', $this->channelSource->id)
                ->where('created_at', '>=', now()->subDay())
                ->first();

            if ($existingMessage) {
                return null;
            }

            // Create channel message
            $channelMessage = ChannelMessage::create([
                'channel_source_id' => $this->channelSource->id,
                'raw_message' => $messageText,
                'message_hash' => $messageHash,
                'status' => 'pending',
            ]);

            // Dispatch job to process message
            ProcessChannelMessage::dispatch($channelMessage);

            return $channelMessage;
        } catch (\Exception $e) {
            Log::error('Failed to process signal: ' . $e->getMessage(), [
                'signal' => $signal
            ]);
            return null;
        }
    }

    /**
     * Extract signal data from notification
     */
    protected function extractSignalFromNotification(array $notification): string
    {
        $parts = [];

        // Check if it's a stop signal
        if (!empty($notification['is_stop_signal']) || !empty($notification['is_stop_loss'])) {
            $parts[] = "STOP SIGNAL";
            if (!empty($notification['extracted_symbol'])) {
                $parts[] = "Symbol: " . $notification['extracted_symbol'];
            }
            if (!empty($notification['message'])) {
                $parts[] = $notification['message'];
            }
            return implode("\n", $parts);
        }

        // Extract symbol
        if (!empty($notification['extracted_symbol'])) {
            $parts[] = "Symbol: " . $notification['extracted_symbol'];
        }

        // Extract action (BUY/SELL)
        if (!empty($notification['action'])) {
            $parts[] = "Action: " . strtoupper($notification['action']);
        }

        // Extract entry price
        if (!empty($notification['entry'])) {
            $parts[] = "Entry: " . $notification['entry'];
        }

        // Extract stop loss
        if (!empty($notification['stop_loss'])) {
            $parts[] = "Stop Loss: " . $notification['stop_loss'];
        }

        // Extract take profit
        if (!empty($notification['take_profit'])) {
            $parts[] = "Take Profit: " . $notification['take_profit'];
        }

        // Extract timeframe
        if (!empty($notification['timeframe'])) {
            $parts[] = "Timeframe: " . $notification['timeframe'];
        }

        // Add message if available
        if (!empty($notification['message'])) {
            $parts[] = "\n" . $notification['message'];
        }

        return implode("\n", $parts);
    }

    /**
     * Format signal as message text
     */
    protected function formatSignalAsMessage(array $signal): string
    {
        $parts = [];

        if (!empty($signal['symbol'])) {
            $parts[] = "Symbol: " . $signal['symbol'];
        }

        if (!empty($signal['action'])) {
            $parts[] = "Action: " . strtoupper($signal['action']);
        }

        if (!empty($signal['entry'])) {
            $parts[] = "Entry: " . $signal['entry'];
        }

        if (!empty($signal['stop_loss'])) {
            $parts[] = "Stop Loss: " . $signal['stop_loss'];
        }

        if (!empty($signal['take_profit'])) {
            $parts[] = "Take Profit: " . $signal['take_profit'];
        }

        if (!empty($signal['timeframe'])) {
            $parts[] = "Timeframe: " . $signal['timeframe'];
        }

        if (!empty($signal['description'])) {
            $parts[] = "\n" . $signal['description'];
        }

        return implode("\n", $parts);
    }
}

