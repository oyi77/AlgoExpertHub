<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend;

use Addons\MultiChannelSignalAddon\App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern;
use Addons\MultiChannelSignalAddon\App\Services\ChannelAssignmentService;
use Addons\MultiChannelSignalAddon\App\Services\TelegramMtprotoService;
use App\Helpers\Helper\Helper;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Channel Forwarding Controller - Admin
 * Manages channel forwarding, assignment to users/plans, and channel selection
 * Does NOT handle connection/auth (that's SignalSourceController)
 */
class ChannelForwardingController extends Controller
{
    protected ChannelAssignmentService $assignmentService;
    protected TelegramMtprotoService $telegramMtprotoService;

    public function __construct(
        ChannelAssignmentService $assignmentService,
        TelegramMtprotoService $telegramMtprotoService
    ) {
        $this->assignmentService = $assignmentService;
        $this->telegramMtprotoService = $telegramMtprotoService;
    }

    /**
     * Display channels available for forwarding (admin + user sources).
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Channel Forwarding';

        // Show all sources that can be forwarded (admin + user sources)
        $query = ChannelSource::with(['user', 'assignedUsers', 'assignedPlans', 'defaultPlan', 'defaultMarket', 'defaultTimeframe']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->scope) {
            $query->where('scope', $request->scope);
        }

        if ($request->ownership) {
            if ($request->ownership === 'admin') {
                $query->adminOwned();
            } elseif ($request->ownership === 'user') {
                $query->userOwned();
            }
        }

        $channels = $query->latest()->paginate(Helper::pagination());

        // Add assignment summaries
        foreach ($channels as $channel) {
            $channel->assignment_summary = $this->assignmentService->getAssignmentSummary($channel);
        }

        $data['channels'] = $channels;
        $data['stats'] = [
            'total' => ChannelSource::count(),
            'admin' => ChannelSource::adminOwned()->count(),
            'user' => ChannelSource::userOwned()->count(),
            'assigned' => ChannelSource::whereNotNull('scope')->count(),
            'global' => ChannelSource::where('scope', 'global')->count(),
        ];

        return view('multi-channel-signal-addon::backend.channel-forwarding.index', $data);
    }

    /**
     * Select channel to forward (for Telegram MTProto sources).
     */
    public function selectChannel(int $id, Request $request): View|RedirectResponse
    {
        $source = ChannelSource::findOrFail($id);

        // For Telegram MTProto sources, verify actual authentication by trying to connect
        // Don't rely on status or flag alone - test actual connectivity
        if ($source->type === 'telegram_mtproto') {
            // Check if password is required (2FA)
            if ($source->config['password_required'] ?? false) {
                return redirect()->route('admin.signal-sources.authenticate', [
                    'id' => $source->id,
                    'step' => 'password'
                ])->with('error', 'Two-factor authentication password required. Please complete password authentication first.');
        }

            // Check authentication status first (quick check)
            if (!($source->config['authenticated'] ?? false)) {
            return redirect()->route('admin.signal-sources.authenticate', $source->id)
                    ->with('error', 'Please complete authentication first.');
            }
        } else {
            // Channel selection is only available for Telegram MTProto sources
            return redirect()->route('admin.channel-forwarding.index')
                ->with('error', 'Channel selection is only available for Telegram MTProto sources.');
        }

        // Handle channel selection
        if ($request->isMethod('post')) {
            $request->validate([
                'channels' => 'required|array|min:1',
                'channels.*' => 'required|string',
                'channel_data' => 'nullable|string', // Channel data from form as JSON string
            ]);

            $selectedChannelIds = $request->channels;
            $selectedChannels = [];
            
            // Use channel data from form if available (faster, avoids fetching all dialogs)
            $channelDataJson = $request->input('channel_data');
            if (!empty($channelDataJson)) {
                // Handle both JSON string and array
                if (is_string($channelDataJson)) {
                    $channelDataArray = json_decode($channelDataJson, true);
                } else {
                    $channelDataArray = $channelDataJson;
                }
                
                if (is_array($channelDataArray)) {
                    foreach ($channelDataArray as $channelData) {
                        if (isset($channelData['id']) && in_array((string)$channelData['id'], $selectedChannelIds)) {
                            $selectedChannels[] = [
                                'id' => $channelData['id'],
                                'username' => $channelData['username'] ?? null,
                                'title' => $channelData['title'] ?? 'Unknown',
                                'type' => $channelData['type'] ?? 'unknown',
                            ];
                        }
                    }
                }
            }
            
            // If we don't have all channel data from form, fetch missing ones from dialogs
            // This should rarely happen, but provides fallback
            if (count($selectedChannels) < count($selectedChannelIds)) {
                $missingIds = array_diff($selectedChannelIds, array_column($selectedChannels, 'id'));
                
                if (!empty($missingIds)) {
                    // Only fetch dialogs if we're missing some channel data
                    $dialogsResult = $this->telegramMtprotoService->getDialogs($source);
                    
                    if ($dialogsResult['type'] === 'error') {
                        return redirect()->back()->with('error', $dialogsResult['message']);
                    }

                    // Find missing channels from dialogs
            foreach ($dialogsResult['dialogs'] as $dialog) {
                        if (isset($dialog['id']) && in_array((string)$dialog['id'], $missingIds)) {
                            $isChannel = (strpos($dialog['type'] ?? '', 'channel') !== false || 
                                         strpos($dialog['type'] ?? '', 'supergroup') !== false);
                            $isGroup = (strpos($dialog['type'] ?? '', 'chat') !== false || 
                                       strpos($dialog['type'] ?? '', 'group') !== false ||
                                       strpos($dialog['type'] ?? '', 'megagroup') !== false);
                            
                            $selectedChannels[] = [
                                'id' => $dialog['id'],
                                'username' => $dialog['username'] ?? null,
                                'title' => $dialog['title'] ?? 'Unknown',
                                'type' => $isChannel ? 'channel' : ($isGroup ? 'group' : 'unknown'),
                            ];
                        }
                    }
                }
            }

            if (empty($selectedChannels)) {
                return redirect()->back()->with('error', 'No valid channels selected.');
            }

            // Update source config with selected channels
            $config = $source->config;
            
            // Store channels as array
            $config['channels'] = $selectedChannels;
            
            // For backward compatibility, also set the first channel as primary
            if (!empty($selectedChannels)) {
                $primaryChannel = $selectedChannels[0];
                $config['channel_id'] = $primaryChannel['id'];
                $config['channel_username'] = $primaryChannel['username'];
                $config['channel_title'] = $primaryChannel['title'];
                $config['channel_type'] = $primaryChannel['type'];
            }

            // Update source name to reflect multiple channels
            // Remove any existing channel count suffix first
            $baseName = preg_replace('/\s*\(\d+\s+channels?\)\s*$/', '', $source->name);
            
            $channelCount = count($selectedChannels);
            // Only update name if we have valid channels (validation should prevent 0, but safety check)
            if ($channelCount > 0) {
                if ($channelCount === 1) {
                    $source->name = $selectedChannels[0]['title'] ?? $baseName;
                } else {
                    $source->name = $baseName . ' (' . $channelCount . ' channels)';
                }
            }
            // If channelCount is 0, don't update the name at all (shouldn't happen due to validation)

            $source->update(['config' => $config]);

            $message = count($selectedChannels) === 1 
                ? 'Channel selected successfully! You can now assign it to users/plans.'
                : count($selectedChannels) . ' channels selected successfully! You can now assign them to users/plans.';

            return redirect()->route('admin.channel-forwarding.index')
                ->with('success', $message);
        }

        // Refresh source to get updated config
        $source->refresh();
        
        // Get currently selected channels for pre-checking
        $selectedChannels = $source->config['channels'] ?? [];
        $selectedChannelIds = [];
        foreach ($selectedChannels as $channel) {
            $selectedChannelIds[] = (string)($channel['id'] ?? $channel['username'] ?? '');
        }

        $data = [
            'title' => 'Select Channels to Forward',
            'source' => $source,
            'dialogs' => [], // Will be loaded via AJAX
            'selectedChannelIds' => $selectedChannelIds, // For pre-checking
            'selectedChannels' => $selectedChannels, // For display
        ];

        return view('multi-channel-signal-addon::backend.channel-forwarding.select-channel', $data);
    }

    /**
     * Load dialogs asynchronously via AJAX (chunked/progressive loading).
     */
    public function loadDialogs(int $id, Request $request): JsonResponse
    {
        try {
            $source = ChannelSource::findOrFail($id);

            if ($source->type !== 'telegram_mtproto') {
                return response()->json([
                    'success' => false,
                    'message' => 'Channel selection is only available for Telegram MTProto sources.',
                ], 400);
            }

            $chunk = (int) $request->get('chunk', 0);
            $chunkSize = 15; // Process 15 dialogs at a time

            // Check if password is required
            if ($source->config['password_required'] ?? false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Two-factor authentication password required. Please complete password authentication first.',
                    'password_required' => true,
                    'hint' => $source->config['password_hint'] ?? '',
                    'dialogs' => [],
                    'has_more' => false,
                ], 400);
            }

            // Get dialogs in chunks for progressive loading
            $dialogsResult = $this->telegramMtprotoService->getDialogsChunked($source, $chunk, $chunkSize);
            
            if ($dialogsResult['type'] === 'error') {
                // Check if error is due to password requirement
                if (strpos($dialogsResult['message'], 'password') !== false || 
                    strpos($dialogsResult['message'], 'Password') !== false) {
                    // Update source config to mark password as required
                    $config = $source->config;
                    $config['password_required'] = true;
                    $source->update(['config' => $config]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Two-factor authentication password required. Please complete password authentication first.',
                        'password_required' => true,
                        'hint' => $source->config['password_hint'] ?? '',
                        'dialogs' => [],
                        'has_more' => false,
                    ], 400);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $dialogsResult['message'],
                    'dialogs' => [],
                    'has_more' => false,
                ], 400);
            }

            // Update authenticated flag if missing (only on first chunk)
            if ($chunk === 0 && !($source->config['authenticated'] ?? false)) {
                $config = $source->config;
                $config['authenticated'] = true;
                $source->update(['config' => $config]);
            }

            return response()->json([
                'success' => true,
                'dialogs' => $dialogsResult['dialogs'] ?? [],
                'has_more' => $dialogsResult['has_more'] ?? false,
                'chunk' => $chunk,
                'total_loaded' => $dialogsResult['total_loaded'] ?? 0,
            ])->header('Content-Type', 'application/json');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Channel source not found.',
                'dialogs' => [],
                'has_more' => false,
            ], 404);
        } catch (\Exception $e) {
            \Log::error("Failed to load dialogs: " . $e->getMessage(), [
                'source_id' => $id,
                'chunk' => $request->get('chunk', 0),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load channels: ' . $e->getMessage(),
                'dialogs' => [],
                'has_more' => false,
            ], 500)->header('Content-Type', 'application/json');
        }
    }

    /**
     * Show assignment management UI.
     */
    public function assign(int $id): View
    {
        $channel = ChannelSource::with(['assignedUsers', 'assignedPlans'])->findOrFail($id);

        $data['title'] = 'Assign Channel to Users/Plans';
        $data['channel'] = $channel;
        $data['users'] = User::where('status', 1)->orderBy('username')->get();
        $data['plans'] = Plan::whereStatus(true)->orderBy('name')->get();
        $data['assignedUserIds'] = $channel->assignedUsers()->pluck('users.id')->toArray();
        $data['assignedPlanIds'] = $channel->assignedPlans()->pluck('plans.id')->toArray();
        $data['isGlobal'] = $channel->scope === 'global';

        return view('multi-channel-signal-addon::backend.channel-forwarding.assign', $data);
    }

    /**
     * Store channel assignments.
     */
    public function storeAssignments(Request $request, int $id): RedirectResponse
    {
        $channel = ChannelSource::findOrFail($id);

        // Only admin-owned channels can be assigned
        if (!$channel->is_admin_owned) {
            return redirect()->back()->with('error', 'Only admin-owned channels can be assigned to users/plans.');
        }

        $request->validate([
            'assignment_type' => 'required|in:user,plan,global',
            'user_ids' => 'required_if:assignment_type,user|array',
            'user_ids.*' => 'exists:users,id',
            'plan_ids' => 'required_if:assignment_type,plan|array',
            'plan_ids.*' => 'exists:plans,id',
        ]);

        try {
            switch ($request->assignment_type) {
                case 'user':
                    $this->assignmentService->assignToUsers($channel, $request->user_ids ?? []);
                    break;
                case 'plan':
                    $this->assignmentService->assignToPlans($channel, $request->plan_ids ?? []);
                    break;
                case 'global':
                    $this->assignmentService->setGlobal($channel, true);
                    break;
            }

            return redirect()->route('admin.channel-forwarding.index')
                ->with('success', 'Channel assignments updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove user assignment.
     */
    public function removeUserAssignment(int $id, int $userId): RedirectResponse
    {
        $channel = ChannelSource::findOrFail($id);
        $this->assignmentService->removeUserAssignment($channel, $userId);

        return redirect()->back()->with('success', 'User assignment removed');
    }

    /**
     * Remove plan assignment.
     */
    public function removePlanAssignment(int $id, int $planId): RedirectResponse
    {
        $channel = ChannelSource::findOrFail($id);
        $this->assignmentService->removePlanAssignment($channel, $planId);

        return redirect()->back()->with('success', 'Plan assignment removed');
    }

    /**
     * Show channel details and forwarded signals.
     */
    public function show(int $id): View
    {
        $channel = ChannelSource::with(['assignedUsers', 'assignedPlans', 'signals'])
            ->findOrFail($id);

        $data['title'] = 'Channel Forwarding Details';
        $data['channel'] = $channel;
        $data['signals'] = $channel->signals()->latest()->paginate(20);
        $data['assignment_summary'] = $this->assignmentService->getAssignmentSummary($channel);

        return view('multi-channel-signal-addon::backend.channel-forwarding.show', $data);
    }

    /**
     * View sample messages from a channel and create parser.
     *
     * @param int $id Channel source ID
     * @param Request $request
     * @return View|JsonResponse
     */
    public function viewSampleMessages(int $id, Request $request)
    {
        $source = ChannelSource::findOrFail($id);

        if ($source->type !== 'telegram_mtproto') {
            return redirect()->route('admin.channel-forwarding.index')
                ->with('error', 'Sample messages are only available for Telegram MTProto sources.');
        }

        // Get channel ID from request or use first selected channel
        $channelId = $request->get('channel_id');
        $channels = $source->config['channels'] ?? [];
        
        if (empty($channels)) {
            return redirect()->route('admin.channel-forwarding.select-channel', $id)
                ->with('error', 'Please select channels first before viewing sample messages.');
        }

        // If no channel_id specified, use first channel
        if (!$channelId && !empty($channels)) {
            $channelId = $channels[0]['id'] ?? $channels[0]['username'] ?? null;
        }

        // Find channel info
        $selectedChannel = null;
        foreach ($channels as $channel) {
            if (($channel['id'] ?? null) == $channelId || ($channel['username'] ?? null) == $channelId) {
                $selectedChannel = $channel;
                break;
            }
        }

        if (!$selectedChannel) {
            return redirect()->route('admin.channel-forwarding.select-channel', $id)
                ->with('error', 'Channel not found in selected channels.');
        }

        // Fetch sample messages
        $limit = (int) $request->get('limit', 20);
        $messagesResult = $this->telegramMtprotoService->fetchSampleMessages($source, $channelId, $limit);

        // If AJAX request, return JSON
        if ($request->ajax()) {
            return response()->json($messagesResult);
        }

        // Get existing patterns for this channel
        $patterns = MessageParsingPattern::where('channel_source_id', $id)
            ->orWhereNull('channel_source_id') // Global patterns
            ->orderByDesc('priority')
            ->orderByDesc('success_count')
            ->get();

        $data = [
            'title' => 'View Sample Messages & Create Parser',
            'source' => $source,
            'selectedChannel' => $selectedChannel,
            'channels' => $channels,
            'messages' => $messagesResult['success'] ? ($messagesResult['messages'] ?? []) : [],
            'messagesError' => $messagesResult['success'] ? null : ($messagesResult['error'] ?? 'Unknown error'),
            'patterns' => $patterns,
        ];

        return view('multi-channel-signal-addon::backend.channel-forwarding.view-samples', $data);
    }

    /**
     * Store a new parsing pattern.
     *
     * @param int $id Channel source ID
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeParser(int $id, Request $request): RedirectResponse
    {
        $source = ChannelSource::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pattern_type' => 'required|in:regex,template',
            'pattern_config' => 'required|array',
            'priority' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $pattern = MessageParsingPattern::create([
            'channel_source_id' => $id,
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'pattern_type' => $request->pattern_type,
            'pattern_config' => $request->pattern_config,
            'priority' => $request->priority ?? 100,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->back()
            ->with('success', 'Parser pattern created successfully!');
    }

    /**
     * Test a parser pattern against sample messages.
     *
     * @param int $id Channel source ID
     * @param Request $request
     * @return JsonResponse
     */
    public function testParser(int $id, Request $request): JsonResponse
    {
        $source = ChannelSource::findOrFail($id);
        
        $request->validate([
            'pattern_config' => 'required|array',
            'pattern_type' => 'required|in:regex,template',
            'test_message' => 'required|string',
        ]);

        try {
            // Temporarily create a pattern record to test with
            $tempPattern = MessageParsingPattern::create([
                'channel_source_id' => $id,
                'user_id' => Auth::id(),
                'name' => 'Temporary Test Pattern',
                'pattern_type' => $request->pattern_type,
                'pattern_config' => $request->pattern_config,
                'priority' => 999,
                'is_active' => true,
            ]);

            try {
                // Use AdvancedPatternParser to test
                $parser = new \Addons\MultiChannelSignalAddon\App\Parsers\AdvancedPatternParser($source);
                
                // Parse the test message
                $result = $parser->parse($request->test_message);
                
                // Delete temporary pattern
                $tempPattern->delete();

                return response()->json([
                    'success' => true,
                    'parsed' => $result ? [
                        'currency_pair' => $result->currency_pair ?? null,
                        'direction' => $result->direction ?? null,
                        'open_price' => $result->open_price ?? null,
                        'sl' => $result->sl ?? null,
                        'tp' => $result->tp ?? null,
                        'timeframe' => $result->timeframe ?? null,
                        'confidence' => $result->confidence ?? 0,
                    ] : null,
                    'message' => $result ? 'Pattern matched successfully!' : 'Pattern did not match the message.',
                ]);
            } catch (\Exception $e) {
                // Ensure temp pattern is deleted even on error
                $tempPattern->delete();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

