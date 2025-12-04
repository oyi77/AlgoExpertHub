<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
