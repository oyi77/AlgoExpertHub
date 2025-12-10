<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * @group Webhooks
 * Telegram webhook endpoint for receiving channel updates.
 * 
 * This endpoint receives Telegram updates and processes them as trading signals.
 * No authentication required - uses channel source ID for identification.
 *
 * @urlParam channelSourceId integer required The channel source ID. Example: 1
 * @bodyParam update_id integer required Telegram update ID. Example: 123456789
 * @bodyParam message object optional Message object (for private messages)
 * @bodyParam channel_post object optional Channel post object (for channel messages)
 * @response 200 {
 *   "status": "ok"
 * }
 * @response 200 {
 *   "status": "ignored"
 * }
 * @response 404 {
 *   "status": "error",
 *   "message": "Invalid channel"
 * }
 * @response 500 {
 *   "status": "error"
 * }
 */
class TelegramWebhookController extends Controller
{
    public function handle(Request $request, $channelSourceId)
    {
        try {
            $payload = $request->all();
            $channelSource = ChannelSource::find($channelSourceId);
            if (!$channelSource) {
                return response()->json(['status' => 'error', 'message' => 'Invalid channel'], 404);
            }

            $message = $payload['message'] ?? null;
            if (!$message) {
                return response()->json(['status' => 'ignored']);
            }

            // Ingestion controller: do not mutate user state here
            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('Telegram webhook error', ['exception' => $e]);
            return response()->json(['status' => 'error'], 500);
        }
    }
}
