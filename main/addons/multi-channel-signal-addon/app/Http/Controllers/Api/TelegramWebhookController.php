<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Api;

use Addons\MultiChannelSignalAddon\App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Jobs\ProcessChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle Telegram webhook updates.
     *
     * @param Request $request
     * @param int $channelSourceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, $channelSourceId)
    {
        try {
            // Find channel source
            $channelSource = ChannelSource::findOrFail($channelSourceId);

            // Verify it's a Telegram channel
            if ($channelSource->type !== 'telegram') {
                return response()->json(['error' => 'Invalid channel type'], 400);
            }

            // Verify channel is active
            if (!$channelSource->isActive()) {
                return response()->json(['error' => 'Channel is not active'], 400);
            }

            // Get update data
            $update = $request->all();

            // Extract message from update
            $messageText = null;
            $chatId = null;
            $messageId = null;
            $timestamp = now()->timestamp;

            if (isset($update['channel_post'])) {
                $message = $update['channel_post'];
                $messageText = $message['text'] ?? $message['caption'] ?? null;
                $chatId = $message['chat']['id'] ?? null;
                $messageId = $message['message_id'] ?? null;
                $timestamp = $message['date'] ?? $timestamp;
            } elseif (isset($update['message'])) {
                $message = $update['message'];
                $messageText = $message['text'] ?? $message['caption'] ?? null;
                $chatId = $message['chat']['id'] ?? null;
                $messageId = $message['message_id'] ?? null;
                $timestamp = $message['date'] ?? $timestamp;
            }

            // If no message text, ignore
            if (!$messageText) {
                return response()->json(['ok' => true]);
            }

            // Check if this is the channel we're monitoring
            $config = $channelSource->config;
            $targetChatId = $config['chat_id'] ?? null;
            if ($targetChatId && $chatId != $targetChatId) {
                return response()->json(['ok' => true]);
            }

            // Generate message hash
            $messageHash = ChannelMessage::generateHash($messageText, $timestamp);

            // Check for duplicate
            $existingMessage = ChannelMessage::where('message_hash', $messageHash)
                ->where('channel_source_id', $channelSource->id)
                ->where('created_at', '>=', now()->subDay())
                ->first();

            if ($existingMessage) {
                return response()->json(['ok' => true]);
            }

            // Create channel message
            $channelMessage = ChannelMessage::create([
                'channel_source_id' => $channelSource->id,
                'raw_message' => $messageText,
                'message_hash' => $messageHash,
                'status' => 'pending',
            ]);

            // Update channel source last processed
            $channelSource->updateLastProcessed();

            // Dispatch job to process message
            ProcessChannelMessage::dispatch($channelMessage);

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error("Telegram webhook error: " . $e->getMessage(), [
                'exception' => $e,
                'channel_source_id' => $channelSourceId,
                'request' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}

