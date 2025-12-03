<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend;

use Addons\MultiChannelSignalAddon\App\Models\AiParsingProfile;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AiParsingProfileController extends Controller
{
    protected $aiConnectionService;

    public function __construct(AiConnectionService $aiConnectionService)
    {
        $this->aiConnectionService = $aiConnectionService;
    }

    /**
     * Display a listing of parsing profiles
     */
    public function index(Request $request)
    {
        $query = AiParsingProfile::with(['aiConnection.provider', 'channelSource']);

        // Filter by channel
        if ($request->has('channel_id') && $request->channel_id != '') {
            if ($request->channel_id == 'global') {
                $query->whereNull('channel_source_id');
            } else {
                $query->where('channel_source_id', $request->channel_id);
            }
        }

        $profiles = $query->orderBy('priority')->paginate(20);
        $channels = ChannelSource::all();
        $connections = AiConnection::with('provider')->active()->get();

        return view('multi-channel-signal-addon::backend.ai-parsing-profiles.index', compact('profiles', 'channels', 'connections'));
    }

    /**
     * Show the form for creating a new parsing profile
     */
    public function create()
    {
        $connections = AiConnection::with('provider')->active()->get();
        $channels = ChannelSource::all();

        // Check if there are any connections available
        if ($connections->isEmpty()) {
            return redirect()->route('admin.ai-connections.connections.create')
                ->with('info', 'Please create an AI connection first before creating a parsing profile.');
        }

        return view('multi-channel-signal-addon::backend.ai-parsing-profiles.create', compact('connections', 'channels'));
    }

    /**
     * Store a newly created parsing profile
     */
    public function store(Request $request)
    {
        $request->validate([
            'ai_connection_id' => 'required|exists:ai_connections,id',
            'name' => 'required|string|max:255',
            'channel_source_id' => 'nullable|exists:channel_sources,id',
            'parsing_prompt' => 'nullable|string',
            'settings' => 'nullable|array',
            'priority' => 'required|integer|min:1',
            'enabled' => 'sometimes|boolean',
        ]);

        AiParsingProfile::create([
            'ai_connection_id' => $request->ai_connection_id,
            'name' => $request->name,
            'channel_source_id' => $request->channel_source_id,
            'parsing_prompt' => $request->parsing_prompt,
            'settings' => $request->settings ?? [],
            'priority' => $request->priority,
            'enabled' => $request->has('enabled') ? true : false,
        ]);

        return redirect()->route('admin.multi-channel.ai-parsing-profiles.index')
            ->with('success', 'AI Parsing Profile created successfully');
    }

    /**
     * Show the form for editing the specified profile
     */
    public function edit(AiParsingProfile $profile)
    {
        $connections = AiConnection::with('provider')->active()->get();
        $channels = ChannelSource::all();

        return view('multi-channel-signal-addon::backend.ai-parsing-profiles.edit', compact('profile', 'connections', 'channels'));
    }

    /**
     * Update the specified parsing profile
     */
    public function update(Request $request, AiParsingProfile $profile)
    {
        $request->validate([
            'ai_connection_id' => 'required|exists:ai_connections,id',
            'name' => 'required|string|max:255',
            'channel_source_id' => 'nullable|exists:channel_sources,id',
            'parsing_prompt' => 'nullable|string',
            'settings' => 'nullable|array',
            'priority' => 'required|integer|min:1',
            'enabled' => 'sometimes|boolean',
        ]);

        $profile->update([
            'ai_connection_id' => $request->ai_connection_id,
            'name' => $request->name,
            'channel_source_id' => $request->channel_source_id,
            'parsing_prompt' => $request->parsing_prompt,
            'settings' => $request->settings ?? [],
            'priority' => $request->priority,
            'enabled' => $request->has('enabled') ? true : false,
        ]);

        return redirect()->route('admin.multi-channel.ai-parsing-profiles.index')
            ->with('success', 'AI Parsing Profile updated successfully');
    }

    /**
     * Remove the specified parsing profile
     */
    public function destroy(AiParsingProfile $profile)
    {
        $profile->delete();

        return redirect()->route('admin.multi-channel.ai-parsing-profiles.index')
            ->with('success', 'AI Parsing Profile deleted successfully');
    }

    /**
     * Test parsing with this profile
     */
    public function testParsing(Request $request, AiParsingProfile $profile)
    {
        $request->validate([
            'test_message' => 'required|string',
        ]);

        try {
            $parser = new \Addons\MultiChannelSignalAddon\App\Parsers\AiMessageParser($profile);
            $result = $parser->parse($request->test_message);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Parsing successful',
                    'parsed_data' => [
                        'currency_pair' => $result->currencyPair,
                        'direction' => $result->direction,
                        'open_price' => $result->openPrice,
                        'sl' => $result->sl,
                        'tp' => $result->tp,
                        'timeframe' => $result->timeframe,
                        'title' => $result->title,
                        'confidence' => $result->confidence,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to parse message - AI could not extract signal data',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parsing error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle profile status
     */
    public function toggleStatus(AiParsingProfile $profile)
    {
        $profile->update(['enabled' => !$profile->enabled]);

        return redirect()->back()
            ->with('success', 'Profile ' . ($profile->enabled ? 'enabled' : 'disabled'));
    }
}

