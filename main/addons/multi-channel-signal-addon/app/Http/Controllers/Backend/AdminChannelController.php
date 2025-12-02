<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend;

use Addons\MultiChannelSignalAddon\App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Services\ChannelAssignmentService;
use Addons\MultiChannelSignalAddon\App\Services\TelegramMtprotoService;
use App\Helpers\Helper\Helper;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AdminChannelController extends Controller
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
     * Display a listing of admin channels.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Admin Channels';

        $query = ChannelSource::adminOwned()
            ->with(['assignedUsers', 'assignedPlans', 'defaultPlan', 'defaultMarket', 'defaultTimeframe']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->scope) {
            $query->where('scope', $request->scope);
        }

        $channels = $query->latest()->paginate(Helper::pagination());

        // Add assignment summaries
        foreach ($channels as $channel) {
            $channel->assignment_summary = $this->assignmentService->getAssignmentSummary($channel);
        }

        $data['channels'] = $channels;
        $data['stats'] = [
            'total' => ChannelSource::adminOwned()->count(),
            'active' => ChannelSource::adminOwned()->where('status', 'active')->count(),
            'paused' => ChannelSource::adminOwned()->where('status', 'paused')->count(),
            'error' => ChannelSource::adminOwned()->where('status', 'error')->count(),
        ];

        return view('multi-channel-signal-addon::backend.admin-channel.index', $data);
    }

    /**
     * Show the form for creating a new admin channel.
     */
    public function create(string $type = 'telegram_mtproto'): View
    {
        $allowedTypes = ['telegram_mtproto', 'telegram', 'api', 'web_scrape', 'rss'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'telegram_mtproto';
        }

        $data['title'] = 'Create Admin Channel';
        $data['type'] = $type;
        $data['plans'] = Plan::whereStatus(true)->get();
        $data['markets'] = \App\Models\Market::whereStatus(true)->get();
        $data['timeframes'] = \App\Models\TimeFrame::whereStatus(true)->get();
        $data['madelineproto_installed'] = class_exists('\danog\MadelineProto\API');

        return view('multi-channel-signal-addon::backend.admin-channel.create', $data);
    }

    /**
     * Store a newly created admin channel.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:telegram,telegram_mtproto,api,web_scrape,rss',
        ]);

        $payload = $request->all();
        $payload['is_admin_owned'] = true;
        $payload['user_id'] = null; // Admin channels don't belong to a user

        try {
            if ($payload['type'] === 'telegram_mtproto') {
                return $this->storeTelegramMtprotoChannel($payload, $request);
            }

            // For other types, create directly
            $channelSource = ChannelSource::create([
                'user_id' => null,
                'is_admin_owned' => true,
                'name' => $payload['name'],
                'type' => $payload['type'],
                'config' => $this->buildConfig($payload),
                'status' => 'active',
                'default_plan_id' => $payload['default_plan_id'] ?? null,
                'default_market_id' => $payload['default_market_id'] ?? null,
                'default_timeframe_id' => $payload['default_timeframe_id'] ?? null,
                'auto_publish_confidence_threshold' => $payload['auto_publish_confidence_threshold'] ?? 90,
            ]);

            return redirect()->route('admin.channels.index')
                ->with('success', 'Admin channel created successfully');
        } catch (\Throwable $th) {
            // Always redirect to index on error, not back to create form
            return redirect()->route('admin.channels.index')
                ->with('error', 'Error: ' . $th->getMessage());
        }
    }

    /**
     * Store Telegram MTProto channel.
     */
    protected function storeTelegramMtprotoChannel(array $data, Request $request): RedirectResponse
    {
        $request->validate([
            'api_id' => 'required|string',
            'api_hash' => 'required|string',
            // channel_username and channel_id will be set after authentication and channel selection
        ]);
        
        // Ensure no bot_token is provided for MTProto (it uses user auth via phone number)
        if (!empty($data['bot_token'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'MadelineProto (telegram_mtproto) uses USER authentication with phone number, NOT bot token. Bot tokens are only for regular Telegram bot channels (type: telegram).');
        }

        $result = $this->telegramMtprotoService->createChannel([
            'user_id' => null, // Admin channel
            'name' => $data['name'],
            'api_id' => $data['api_id'],
            'api_hash' => $data['api_hash'],
            'phone_number' => $data['phone_number'] ?? null,
            'channel_username' => $data['channel_username'] ?? null,
            'channel_id' => $data['channel_id'] ?? null,
            'default_plan_id' => $data['default_plan_id'] ?? null,
            'default_market_id' => $data['default_market_id'] ?? null,
            'default_timeframe_id' => $data['default_timeframe_id'] ?? null,
            'auto_publish_confidence_threshold' => $data['auto_publish_confidence_threshold'] ?? 90,
        ]);

        if ($result['type'] === 'success') {
            // Mark as admin-owned (should already be set, but ensure it)
            $channelSource = $result['channel_source'];
            if (!$channelSource->is_admin_owned) {
                $channelSource->update(['is_admin_owned' => true, 'user_id' => null]);
            }

            return redirect()->route('admin.channels.index')
                ->with('success', 'Telegram MTProto channel created successfully');
        }

        if (in_array($result['type'], ['phone_required', 'code_required'], true)) {
            if (!isset($result['channel_source']) || !$result['channel_source']) {
                return redirect()->route('admin.channels.index')
                    ->with('error', 'Failed to create channel. Please try again.');
            }
            
            // Ensure channel type is telegram_mtproto before redirecting to authenticate
            $channelSource = $result['channel_source'];
            if ($channelSource->type !== 'telegram_mtproto') {
                return redirect()->route('admin.channels.index')
                    ->with('error', 'Invalid channel type for authentication. Only telegram_mtproto channels require user authentication.');
            }
            
            return redirect()->route('admin.channels.authenticate', [
                'id' => $channelSource->id,
                'step' => $result['step'] ?? 'phone',
            ])->with('info', $result['message']);
        }

        // On error, redirect to index instead of back to avoid showing create form
        return redirect()->route('admin.channels.index')
            ->with('error', $result['message'] ?? 'Failed to create channel');
    }

    /**
     * Show the form for editing an admin channel.
     */
    public function edit(int $id): View
    {
        $channel = ChannelSource::adminOwned()->findOrFail($id);

        $data['title'] = 'Edit Admin Channel';
        $data['channel'] = $channel;
        $data['plans'] = Plan::whereStatus(true)->get();
        $data['markets'] = \App\Models\Market::whereStatus(true)->get();
        $data['timeframes'] = \App\Models\TimeFrame::whereStatus(true)->get();

        return view('multi-channel-signal-addon::backend.admin-channel.edit', $data);
    }

    /**
     * Update an admin channel.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $channel = ChannelSource::adminOwned()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'default_plan_id' => 'nullable|exists:plans,id',
            'default_market_id' => 'nullable|exists:markets,id',
            'default_timeframe_id' => 'nullable|exists:time_frames,id',
            'auto_publish_confidence_threshold' => 'nullable|integer|min:0|max:100',
        ]);

        // For telegram_mtproto channels, update API credentials if provided
        if ($channel->type === 'telegram_mtproto') {
            $request->validate([
                'api_id' => 'required|string',
                'api_hash' => 'required|string',
            ]);

            $config = $channel->config;
            $config['api_id'] = $request->api_id;
            $config['api_hash'] = $request->api_hash;
            
            // If API credentials changed, delete old session file to force re-authentication
            if (($config['api_id'] !== ($channel->config['api_id'] ?? '')) || 
                ($config['api_hash'] !== ($channel->config['api_hash'] ?? ''))) {
                $sessionFile = storage_path('app/madelineproto/admin/' . $channel->id . '.madeline');
                if (file_exists($sessionFile)) {
                    @unlink($sessionFile);
                }
                $channel->update(['status' => 'pending']); // Reset to pending for re-authentication
            }
            
            $channel->update(['config' => $config]);
        }

        $channel->update($request->only([
            'name',
            'default_plan_id',
            'default_market_id',
            'default_timeframe_id',
            'auto_publish_confidence_threshold',
        ]));

        return redirect()->route('admin.channels.index')
            ->with('success', 'Channel updated successfully');
    }

    /**
     * Show assignment management UI.
     */
    public function assign(int $id): View
    {
        $channel = ChannelSource::adminOwned()
            ->with(['assignedUsers', 'assignedPlans'])
            ->findOrFail($id);

        $data['title'] = 'Manage Channel Assignments';
        $data['channel'] = $channel;
        $data['users'] = User::where('status', 1)->orderBy('username')->get();
        $data['plans'] = Plan::whereStatus(true)->orderBy('name')->get();
        $data['assignedUserIds'] = $channel->assignedUsers()->pluck('users.id')->toArray();
        $data['assignedPlanIds'] = $channel->assignedPlans()->pluck('plans.id')->toArray();
        $data['isGlobal'] = $channel->scope === 'global';

        return view('multi-channel-signal-addon::backend.admin-channel.assign', $data);
    }

    /**
     * Store channel assignments.
     */
    public function storeAssignments(Request $request, int $id): RedirectResponse
    {
        $channel = ChannelSource::adminOwned()->findOrFail($id);

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

            return redirect()->route('admin.channels.index')
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
        $channel = ChannelSource::adminOwned()->findOrFail($id);
        $this->assignmentService->removeUserAssignment($channel, $userId);

        return redirect()->back()->with('success', 'User assignment removed');
    }

    /**
     * Remove plan assignment.
     */
    public function removePlanAssignment(int $id, int $planId): RedirectResponse
    {
        $channel = ChannelSource::adminOwned()->findOrFail($id);
        $this->assignmentService->removePlanAssignment($channel, $planId);

        return redirect()->back()->with('success', 'Plan assignment removed');
    }

    /**
     * Handle MTProto authentication.
     */
    public function authenticate(int $id, Request $request): View|RedirectResponse
    {
        // Start output buffering at the very beginning to catch any MadelineProto web UI output
        ob_start();
        ob_start();
        
        $channel = ChannelSource::adminOwned()->findOrFail($id);
        
        // Ensure this is a telegram_mtproto channel (user auth, not bot token)
        if ($channel->type !== 'telegram_mtproto') {
            // Clear buffers before redirect
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            return redirect()->route('admin.channels.index')
                ->with('error', 'This channel type does not require user authentication. Bot token authentication is used for regular Telegram bot channels.');
        }

        // Prevent MadelineProto from showing web UI
        // CRITICAL: Only call startAuth() during POST requests when we have a phone number
        // Never call startAuth() during GET requests - this prevents web UI from appearing
        // Store original POST data but DON'T clear it here - CSRF validation needs it
        // We'll clear it in the adapter only when needed
        $originalPost = $_POST ?? [];
        // DO NOT clear $_POST here - it contains CSRF token needed for validation

        $step = $request->get('step', 'phone');
        
        // IMPORTANT: During GET requests, we should NEVER initialize MadelineProto
        // Only initialize it during POST requests when we're actually authenticating
        // This prevents start() from being called and outputting web UI
        $data = [
            'title' => 'Authenticate Telegram Account (User Login)',
            'channel' => $channel,
            'step' => $step,
        ];

        if ($request->isMethod('post') && $step === 'phone') {
            $request->validate(['phone_number' => 'required|string']);

            // Get current config and update phone number
            $config = $channel->config ?? [];
            $config['phone_number'] = $request->phone_number;
            
            // Use forceFill and save to ensure config is properly saved
            // This bypasses any casting issues and ensures the mutator is called
            $channel->forceFill(['config' => $config]);
            $channel->save();
            
            // Refresh to get the latest data from database
            $channel->refresh();
            
            // Log to verify phone number was saved
            Log::info("Phone number saved", [
                'channel_id' => $channel->id,
                'phone_number' => $request->phone_number,
                'config_after_save' => $channel->config ?? [],
                'phone_in_config' => $channel->config['phone_number'] ?? 'NOT FOUND'
            ]);

            // CRITICAL: Set programmatic auth mode BEFORE initializing adapter
            // This prevents MadelineProto from outputting web UI
            putenv('MADELINE_PROGRAMMATIC_AUTH=1');
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            $_SERVER['MADELINE_PROGRAMMATIC_AUTH'] = '1';
            
            // CRITICAL: Do NOT set $_POST['phone_number'] here
            // Setting it will cause MadelineProto's start() to call webPhoneLogin() which outputs HTML
            // Instead, we'll call phoneLogin() programmatically after start() completes
            // Only set 'type' to prevent webEcho() from being called (though it may still be called)
            $_POST['type'] = 'phone';
            // DO NOT set $_POST['phone_number'] - it triggers webPhoneLogin()
            
            // Use output buffering to catch any output
            ob_start();
            ob_start();
            ob_start();
            
            $authResult = null;
            try {
                $adapter = new \Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter($channel);
                $authResult = $adapter->startAuth();
                
                // Log the result for debugging
                Log::info("startAuth() result", [
                    'type' => $authResult['type'] ?? 'unknown',
                    'message' => $authResult['message'] ?? 'no message',
                    'has_phone_code_hash' => isset($authResult['phone_code_hash'])
                ]);
            } catch (\Exception $e) {
                // Clear buffers on exception
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                Log::error("startAuth() exception", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->route('admin.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', 'Authentication error: ' . $e->getMessage());
            }
            
            // Get any captured output
            $output = '';
            while (ob_get_level() > 0) {
                $output .= ob_get_clean();
            }
            
            // Log if there was any output (might be MadelineProto web UI)
            if (!empty($output)) {
                Log::warning("Output captured during startAuth()", [
                    'output_length' => strlen($output),
                    'output_preview' => substr($output, 0, 500),
                    'contains_html' => strpos($output, '<html') !== false,
                    'contains_madeline' => strpos($output, 'MadelineProto') !== false
                ]);
            }
            
            // Ensure we have a valid result
            if (!is_array($authResult) || !isset($authResult['type'])) {
                Log::error("Invalid startAuth() result", ['result' => $authResult]);
                return redirect()->route('admin.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', 'Invalid authentication response. Please try again.');
            }

            if ($authResult['type'] === 'code_required') {
                $_POST = $originalPost;
                
                $phoneCodeHash = $authResult['phone_code_hash'] ?? null;
                if (!$phoneCodeHash) {
                    Log::error("code_required but no phone_code_hash", ['result' => $authResult]);
                    return redirect()->route('admin.channels.authenticate', [
                        'id' => $channel->id,
                        'step' => 'phone',
                    ])->with('error', 'Failed to get verification code hash. Please try again.');
                }
                
                $request->session()->put('admin_phone_code_hash', $phoneCodeHash);
                // CRITICAL: Save session before redirect (DO NOT regenerate token - it will invalidate the form)
                $request->session()->save();

                return redirect()->route('admin.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'code',
                ])->with('info', $authResult['message'] ?? 'Verification code sent. Please check your Telegram app.');
            }

            if ($authResult['type'] === 'error') {
                $_POST = $originalPost;
                
                return redirect()->route('admin.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', $authResult['message'] ?? 'Failed to send verification code. Please check your API credentials and phone number format.');
            }

            if ($authResult['type'] === 'success') {
                $_POST = $originalPost;
                return redirect()->route('admin.channels.select-channel', $channel->id)
                    ->with('success', 'Telegram account authenticated successfully!');
            }
            
            if ($authResult['type'] === 'phone_required') {
                $_POST = $originalPost;
                // This shouldn't happen since we already have phone_number, but handle it anyway
                return redirect()->route('admin.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', 'Phone number is required.');
            }
            
            // Fallback for any other unexpected result type
            Log::warning("Unexpected startAuth() result type", [
                'type' => $authResult['type'],
                'result' => $authResult
            ]);
            $_POST = $originalPost;
            return redirect()->route('admin.channels.authenticate', [
                'id' => $channel->id,
                'step' => 'phone',
            ])->with('error', 'Unexpected authentication response. Please try again.');
        }

        if ($request->isMethod('post') && $step === 'code') {
            // CRITICAL: Save session before any operations to prevent CSRF token expiration
            $request->session()->save();
            
            $request->validate(['code' => 'required|string']);

            $phoneCodeHash = $request->session()->get('admin_phone_code_hash') ?? $request->phone_code_hash;
            if (!$phoneCodeHash) {
                // Redirect to phone step instead of back
                $request->session()->save(); // Save session before redirect
                return redirect()->route('admin.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', 'Invalid session. Please start over.');
            }

            // CRITICAL: Refresh channel from database to get latest config (including phone_number)
            // The channel instance might be stale if it was loaded before phone number was saved
            // Use fresh() to get a completely new instance from database
            $channel = $channel->fresh();
            
            // Verify phone number exists before proceeding
            if (empty($channel->config['phone_number'] ?? null)) {
                Log::error("Phone number missing after refresh", [
                    'channel_id' => $channel->id,
                    'config' => $channel->config
                ]);
                return redirect()->route('admin.channels.authenticate', [
                    'id' => $channel->id,
                    'step' => 'phone',
                ])->with('error', 'Phone number not found. Please re-enter your phone number.');
            }

            // Prevent MadelineProto web UI
            $_ENV['MADELINE_PROGRAMMATIC_AUTH'] = true;
            // DO NOT clear $_POST here - CSRF token is already validated by middleware
            // Clearing $_POST here won't affect CSRF validation since it happens before controller
            
            // Suppress any output from MadelineProto
            ob_start();
            ob_start();
            $result = $this->telegramMtprotoService->completeAuth($channel, $request->code, $phoneCodeHash);
            $output = ob_get_clean();
            ob_end_clean();
            
            // Log if web UI was suppressed
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, 'MadelineProto') !== false)) {
                Log::warning("MadelineProto web UI output suppressed in completeAuth", ['output_length' => strlen($output)]);
            }

            if ($result['type'] === 'success') {
                // Clear buffers before redirect
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                $_POST = $originalPost;
                
                $request->session()->forget('admin_phone_code_hash');
                // CRITICAL: Save session and regenerate CSRF token before redirect
                $request->session()->save();
                $request->session()->regenerateToken(); // Regenerate CSRF token

                // Redirect to channel selection after successful authentication
                return redirect()->route('admin.channels.select-channel', $channel->id)
                    ->with('success', 'Telegram account authenticated successfully! Please select a channel to monitor.');
            }

            // Clear buffers before redirect
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $_POST = $originalPost;
            return redirect()->back()->with('error', $result['message']);
        }

        // Clear all output buffers before returning view
        // This ensures any MadelineProto web UI output is discarded
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Restore original POST data before returning view
        $_POST = $originalPost;

        return view('multi-channel-signal-addon::backend.admin-channel.authenticate', $data);
    }

    /**
     * Select channel after authentication.
     */
    public function selectChannel(int $id, Request $request): View|RedirectResponse
    {
        $channel = ChannelSource::adminOwned()
            ->where('type', 'telegram_mtproto')
            ->findOrFail($id);

        // Check if already authenticated
        if ($channel->status !== 'active') {
            return redirect()->route('admin.channels.authenticate', $channel->id)
                ->with('error', 'Please authenticate first.');
        }

        // Get dialogs/channels
        $dialogsResult = $this->telegramMtprotoService->getDialogs($channel);

        if ($dialogsResult['type'] === 'error') {
            return redirect()->route('admin.channels.authenticate', $channel->id)
                ->with('error', $dialogsResult['message']);
        }

        // Handle channel selection
        if ($request->isMethod('post')) {
            $request->validate([
                'channel_id' => 'required',
                'channel_type' => 'required|in:channel,group',
            ]);

            $selectedChannelId = $request->channel_id;
            $channelType = $request->channel_type;

            // Find the selected channel from dialogs
            $selectedChannel = null;
            foreach ($dialogsResult['dialogs'] as $dialog) {
                if (isset($dialog['id']) && $dialog['id'] == $selectedChannelId) {
                    $selectedChannel = $dialog;
                    break;
                }
            }

            if (!$selectedChannel) {
                return redirect()->back()->with('error', 'Selected channel not found.');
            }

            // Update channel config with selected channel
            $config = $channel->config;
            $config['channel_id'] = $selectedChannel['id'];
            $config['channel_username'] = $selectedChannel['username'] ?? null;
            $config['channel_title'] = $selectedChannel['title'] ?? null;
            $config['channel_type'] = $channelType;

            $channel->update([
                'config' => $config,
                'name' => $selectedChannel['title'] ?? $channel->name,
            ]);

            return redirect()->route('admin.channels.index')
                ->with('success', 'Channel selected successfully!');
        }

        $data = [
            'title' => 'Select Channel to Monitor',
            'channel' => $channel,
            'dialogs' => $dialogsResult['dialogs'] ?? [],
        ];

        return view('multi-channel-signal-addon::backend.admin-channel.select-channel', $data);
    }

    /**
     * Update channel status.
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $channel = ChannelSource::adminOwned()->findOrFail($id);

        $status = $request->input('status');
        if (!in_array($status, ['active', 'paused'], true)) {
            return redirect()->back()->with('error', 'Invalid status');
        }

        $channel->update(['status' => $status]);

        $message = $status === 'active' ? 'Channel resumed' : 'Channel paused';
        return redirect()->back()->with('success', $message);
    }

    /**
     * Remove the specified channel.
     */
    public function destroy(int $id): RedirectResponse
    {
        $channel = ChannelSource::adminOwned()->findOrFail($id);

        // Cleanup MTProto session if exists
        if ($channel->type === 'telegram_mtproto') {
            $sessionFile = storage_path('app/madelineproto/admin/' . $channel->id . '.session');
            if (file_exists($sessionFile)) {
                @unlink($sessionFile);
            }
        }

        $channel->delete();

        return redirect()->route('admin.channels.index')
            ->with('success', 'Channel deleted successfully');
    }

    /**
     * Build config array from request payload.
     */
    protected function buildConfig(array $data): array
    {
        $config = [];

        switch ($data['type']) {
            case 'api':
                $config = [
                    'webhook_url' => $data['webhook_url'] ?? null,
                    'secret_key' => $data['secret_key'] ?? null,
                ];
                break;
            case 'web_scrape':
                $config = [
                    'url' => $data['url'] ?? null,
                    'selector' => $data['selector'] ?? null,
                    'selector_type' => $data['selector_type'] ?? 'css',
                ];
                break;
            case 'rss':
                $config = [
                    'feed_url' => $data['feed_url'] ?? null,
                ];
                break;
        }

        return $config;
    }
}

