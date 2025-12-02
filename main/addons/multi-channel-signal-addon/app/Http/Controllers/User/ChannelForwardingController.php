<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\User;

use Addons\MultiChannelSignalAddon\App\Http\Controllers\Controller;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Services\TelegramMtprotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Channel Forwarding Controller - User
 * Shows channels assigned to the user (by user, plan, or global)
 * Users can only view channels assigned to them, not manage assignments
 */
class ChannelForwardingController extends Controller
{
    protected TelegramMtprotoService $telegramMtprotoService;

    public function __construct(TelegramMtprotoService $telegramMtprotoService)
    {
        $this->telegramMtprotoService = $telegramMtprotoService;
    }

    /**
     * Display channels assigned to the current user.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Channel Forwarding';

        // Only show channels assigned to this user
        $query = ChannelSource::assignedToUser(Auth::id())
            ->with(['assignedUsers', 'assignedPlans', 'signals'])
            ->where('status', 'active'); // Only show active channels

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $channels = $query->latest()->paginate(20);

        // Add assignment info for each channel
        foreach ($channels as $channel) {
            $channel->assignment_info = $this->getAssignmentInfo($channel);
        }

        $data['channels'] = $channels;
        $data['stats'] = [
            'total' => ChannelSource::assignedToUser(Auth::id())->where('status', 'active')->count(),
            'by_user' => ChannelSource::assignedToUser(Auth::id())
                ->whereHas('assignedUsers', fn($q) => $q->where('users.id', Auth::id()))
                ->where('status', 'active')
                ->count(),
            'by_plan' => ChannelSource::assignedToUser(Auth::id())
                ->whereHas('assignedPlans', function ($q) {
                    $q->whereHas('users', fn($uq) => $uq->where('users.id', Auth::id())
                        ->where('is_current', 1)
                        ->where('plan_expired_at', '>', now()));
                })
                ->where('status', 'active')
                ->count(),
            'global' => ChannelSource::assignedToUser(Auth::id())
                ->where('scope', 'global')
                ->where('status', 'active')
                ->count(),
        ];

        return view('multi-channel-signal-addon::user.channel-forwarding.index', $data);
    }

    /**
     * Show channel details and forwarded signals.
     */
    public function show(int $id): View
    {
        // Verify user has access to this channel
        $channel = ChannelSource::assignedToUser(Auth::id())
            ->with(['assignedUsers', 'assignedPlans', 'signals'])
            ->findOrFail($id);

        $data['title'] = 'Channel Details';
        $data['channel'] = $channel;
        $data['signals'] = $channel->signals()->latest()->paginate(20);
        $data['assignment_info'] = $this->getAssignmentInfo($channel);

        return view('multi-channel-signal-addon::user.channel-forwarding.show', $data);
    }

    /**
     * Select channel to forward (for user's own Telegram MTProto sources).
     */
    public function selectChannel(int $id, Request $request): View|RedirectResponse
    {
        // Only allow if user owns this source
        $source = ChannelSource::where('user_id', Auth::id())
            ->findOrFail($id);

        // Only Telegram MTProto sources can select channels
        if ($source->type !== 'telegram_mtproto') {
            return redirect()->route('user.signal-sources.index')
                ->with('error', 'Channel selection is only available for Telegram MTProto sources.');
        }

        // Verify actual authentication by trying to connect
        // Don't rely on status or flag alone - test actual connectivity
        $dialogsResult = $this->telegramMtprotoService->getDialogs($source);

        if ($dialogsResult['type'] === 'error') {
            return redirect()->route('user.signal-sources.authenticate', $source->id)
                ->with('error', $dialogsResult['message']);
        }
        
        // If we got here, authentication is valid - update flag if missing
        if (!($source->config['authenticated'] ?? false)) {
            $config = $source->config;
            $config['authenticated'] = true;
            $source->update(['config' => $config]);
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'channel_id' => 'required',
                'channel_type' => 'required|in:channel,group',
            ]);

            $selectedChannelId = $request->channel_id;
            $channelType = $request->channel_type;

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

            $config = $source->config;
            $config['channel_id'] = $selectedChannel['id'];
            $config['channel_username'] = $selectedChannel['username'] ?? null;
            $config['channel_title'] = $selectedChannel['title'] ?? null;
            $config['channel_type'] = $channelType;

            $source->update([
                'config' => $config,
                'name' => $selectedChannel['title'] ?? $source->name,
            ]);

            return redirect()->route('user.channel-forwarding.index')
                ->with('success', 'Channel selected successfully!');
        }

        $data = [
            'title' => 'Select Channel to Forward',
            'source' => $source,
            'dialogs' => $dialogsResult['dialogs'] ?? [],
        ];

        return view('multi-channel-signal-addon::user.channel-forwarding.select-channel', $data);
    }

    /**
     * Get assignment information for a channel.
     */
    protected function getAssignmentInfo(ChannelSource $channel): array
    {
        $info = [
            'type' => 'none',
            'description' => 'Not assigned',
        ];

        if ($channel->scope === 'global') {
            $info = [
                'type' => 'global',
                'description' => 'Available to all users',
            ];
        } elseif ($channel->scope === 'user') {
            $assignedUsers = $channel->assignedUsers()->pluck('username')->toArray();
            $isAssignedToMe = in_array(Auth::user()->username, $assignedUsers);
            
            $info = [
                'type' => 'user',
                'description' => $isAssignedToMe 
                    ? 'Assigned directly to you'
                    : 'Assigned to specific users',
                'users' => $assignedUsers,
            ];
        } elseif ($channel->scope === 'plan') {
            $userPlan = Auth::user()->currentplan()
                ->where('is_current', 1)
                ->where('plan_expired_at', '>', now())
                ->first();
            
            $assignedPlans = $channel->assignedPlans()->pluck('name')->toArray();
            $isAssignedToMyPlan = $userPlan && in_array($userPlan->plan->name ?? '', $assignedPlans);
            
            $info = [
                'type' => 'plan',
                'description' => $isAssignedToMyPlan
                    ? 'Assigned to your plan'
                    : 'Assigned to specific plans',
                'plans' => $assignedPlans,
            ];
        }

        return $info;
    }
}

