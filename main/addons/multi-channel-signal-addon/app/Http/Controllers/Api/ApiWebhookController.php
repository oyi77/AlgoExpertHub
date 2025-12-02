<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Api;

use Addons\MultiChannelSignalAddon\App\Adapters\ApiAdapter;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Jobs\ProcessChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiWebhookController extends Controller
{
    /**
     * Handle API webhook requests.
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

            // Verify it's an API channel
            if ($channelSource->type !== 'api') {
                return response()->json(['error' => 'Invalid channel type'], 400);
            }

            // Verify channel is active
            if (!$channelSource->isActive()) {
                return response()->json(['error' => 'Channel is not active'], 400);
            }

            // Verify signature if configured
            $adapter = app(ApiAdapter::class, ['channelSource' => $channelSource]);
            $adapter->connect($channelSource);

            $signature = $request->header('X-Signature') ?? $request->header('Signature');
            $payload = $request->getContent();

            if ($signature && !$adapter->verifySignature($payload, $signature)) {
                Log::warning("API webhook signature verification failed", [
                    'channel_source_id' => $channelSourceId,
                    'ip' => $request->ip()
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Extract message from payload
            $messageText = $this->extractMessageFromPayload($request);

            if (!$messageText) {
                return response()->json(['error' => 'No message found in payload'], 400);
            }

            // Generate message hash
            $messageHash = ChannelMessage::generateHash($messageText);

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
            Log::error("API webhook error: " . $e->getMessage(), [
                'exception' => $e,
                'channel_source_id' => $channelSourceId,
                'request' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Extract message text from payload.
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractMessageFromPayload(Request $request): ?string
    {
        // Try JSON payload first
        if ($request->isJson()) {
            $data = $request->json()->all();
            
            // Common field names for message
            $messageFields = ['message', 'text', 'content', 'body', 'signal', 'data'];
            
            foreach ($messageFields as $field) {
                if (isset($data[$field])) {
                    if (is_string($data[$field])) {
                        return $data[$field];
                    } elseif (is_array($data[$field])) {
                        return json_encode($data[$field]);
                    }
                }
            }

            // If no message field, try to convert entire payload to string
            return json_encode($data);
        }

        // Try form data
        if ($request->has('message')) {
            return $request->input('message');
        }

        // Try raw content
        $content = $request->getContent();
        if (!empty($content)) {
            return $content;
        }

        return null;
    }
}

