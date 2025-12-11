<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Multi-Channel Signals
 *
 * Endpoints for managing channel sources and message patterns.
 */
class ChannelSourceApiController extends Controller
{
    /**
     * List Channel Sources
     */
    public function index()
    {
        if (!class_exists(\Addons\MultiChannelSignal\Models\ChannelSource::class)) {
            return response()->json(['success' => false, 'message' => 'Multi-channel signal addon not available'], 503);
        }

        $sources = \Addons\MultiChannelSignal\Models\ChannelSource::where('user_id', Auth::id())
            ->orWhere('is_global', true)
            ->get();

        return response()->json(['success' => true, 'data' => $sources]);
    }

    /**
     * Create Channel Source
     */
    public function store(Request $request)
    {
        if (!class_exists(\Addons\MultiChannelSignal\Models\ChannelSource::class)) {
            return response()->json(['success' => false, 'message' => 'Multi-channel signal addon not available'], 503);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'type' => 'required|in:TELEGRAM,DISCORD,SLACK,WEBHOOK',
            'credentials' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $source = \Addons\MultiChannelSignal\Models\ChannelSource::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'type' => $request->type,
            'credentials' => $request->credentials,
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'data' => $source], 201);
    }

    /**
     * Update Channel Source
     */
    public function update(Request $request, $id)
    {
        if (!class_exists(\Addons\MultiChannelSignal\Models\ChannelSource::class)) {
            return response()->json(['success' => false, 'message' => 'Multi-channel signal addon not available'], 503);
        }

        $source = \Addons\MultiChannelSignal\Models\ChannelSource::where('user_id', Auth::id())->findOrFail($id);
        $source->update($request->only(['name', 'credentials', 'is_active']));

        return response()->json(['success' => true, 'data' => $source]);
    }

    /**
     * Delete Channel Source
     */
    public function destroy($id)
    {
        if (!class_exists(\Addons\MultiChannelSignal\Models\ChannelSource::class)) {
            return response()->json(['success' => false, 'message' => 'Multi-channel signal addon not available'], 503);
        }

        \Addons\MultiChannelSignal\Models\ChannelSource::where('user_id', Auth::id())->findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Channel source deleted']);
    }
}
