<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MultiChannelSignalController extends Controller
{
    /**
     * Display unified Multi-Channel Signal page with tabs
     */
    public function index(Request $request)
    {
        $data['title'] = __('Multi-Channel Signal');
        $data['activeTab'] = $request->get('tab', 'all-signals');
        
        // Check if addon is enabled
        $data['multiChannelEnabled'] = \App\Support\AddonRegistry::active('multi-channel-signal-addon') 
            && \App\Support\AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'user_ui');

        // Load data for each tab
        if ($data['multiChannelEnabled']) {
            try {
                // All Signals tab
                if ($data['activeTab'] === 'all-signals') {
                    try {
                        $data['signals'] = \App\Models\Signal::where('auto_created', 1)
                            ->with(['pair', 'time', 'market', 'channelSource'])
                            ->latest()
                            ->paginate(20, ['*'], 'signals_page');
                    } catch (\Exception $e) {
                        \Log::error('MultiChannelSignal: Error loading signals', ['error' => $e->getMessage()]);
                        $data['signals'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                }

                // Signal Sources tab
                if ($data['activeTab'] === 'signal-sources') {
                    if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
                        try {
                            $data['sources'] = \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::where('user_id', Auth::id())
                                ->where('is_admin_owned', false)
                                ->latest()
                                ->paginate(20, ['*'], 'sources_page');
                        } catch (\Exception $e) {
                            \Log::error('MultiChannelSignal: Error loading sources', ['error' => $e->getMessage()]);
                            $data['sources'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        }
                    }
                }

                // Channel Forwarding tab
                if ($data['activeTab'] === 'channel-forwarding') {
                    if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
                        try {
                            $data['channels'] = \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::assignedToUser(Auth::id())
                                ->where('status', 'active')
                                ->with(['assignedUsers', 'assignedPlans', 'signals'])
                                ->latest()
                                ->paginate(20, ['*'], 'channels_page');
                            
                            // Calculate stats for channel forwarding
                            $data['stats'] = [
                                'total' => \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::assignedToUser(Auth::id())
                                    ->where('status', 'active')
                                    ->count(),
                                'by_user' => \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::assignedToUser(Auth::id())
                                    ->whereHas('assignedUsers', fn($q) => $q->where('users.id', Auth::id()))
                                    ->where('status', 'active')
                                    ->count(),
                                'by_plan' => \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::assignedToUser(Auth::id())
                                    ->whereHas('assignedPlans', function ($q) {
                                        $q->whereHas('subscriptions', function ($sq) {
                                            $sq->where('user_id', Auth::id())
                                                ->where('is_current', 1)
                                                ->where(function($dateQuery) {
                                                    $dateQuery->where('plan_expired_at', '>', now())
                                                              ->orWhereNull('plan_expired_at');
                                                });
                                        });
                                    })
                                    ->where('status', 'active')
                                    ->count(),
                                'global' => \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::assignedToUser(Auth::id())
                                    ->where('scope', 'global')
                                    ->where('status', 'active')
                                    ->count(),
                            ];
                            
                            // Add assignment info for each channel
                            foreach ($data['channels'] as $channel) {
                                $channel->assignment_info = $this->getChannelAssignmentInfo($channel);
                            }
                        } catch (\Exception $e) {
                            \Log::error('MultiChannelSignal: Error loading channels', ['error' => $e->getMessage()]);
                            $data['channels'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                            $data['stats'] = [
                                'total' => 0,
                                'by_user' => 0,
                                'by_plan' => 0,
                                'global' => 0,
                            ];
                        }
                    }
                }

                // Signal Review tab
                if ($data['activeTab'] === 'signal-review') {
                    try {
                        $data['reviewSignals'] = \App\Models\Signal::where('auto_created', 1)
                            ->where('is_published', 0) // Draft signals
                            ->with(['pair', 'time', 'market', 'channelSource'])
                            ->latest()
                            ->paginate(20, ['*'], 'review_page');
                    } catch (\Exception $e) {
                        \Log::error('MultiChannelSignal: Error loading review signals', ['error' => $e->getMessage()]);
                        $data['reviewSignals'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                }

                // Pattern Templates tab
                if ($data['activeTab'] === 'pattern-templates') {
                    // User can view and create pattern templates
                    if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern::class)) {
                        try {
                            $data['patterns'] = \Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern::where('user_id', Auth::id())
                                ->latest()
                                ->paginate(20, ['*'], 'patterns_page');
                        } catch (\Exception $e) {
                            \Log::error('MultiChannelSignal: Error loading patterns', ['error' => $e->getMessage()]);
                            $data['patterns'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        }
                    }
                }

                // Analytics tab
                if ($data['activeTab'] === 'analytics') {
                    try {
                        // Load analytics data
                        $data['analytics'] = [
                            'total_signals' => \App\Models\Signal::where('auto_created', 1)->count(),
                            'published_signals' => \App\Models\Signal::where('auto_created', 1)->where('is_published', 1)->count(),
                            'draft_signals' => \App\Models\Signal::where('auto_created', 1)->where('is_published', 0)->count(),
                            'active_sources' => class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class) 
                                ? \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::where('user_id', Auth::id())
                                    ->where('is_admin_owned', false)
                                    ->where('status', 'active')
                                    ->count() 
                                : 0,
                        ];
                    } catch (\Exception $e) {
                        \Log::error('MultiChannelSignal: Error loading analytics', ['error' => $e->getMessage()]);
                        $data['analytics'] = [
                            'total_signals' => 0,
                            'published_signals' => 0,
                            'draft_signals' => 0,
                            'active_sources' => 0,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('MultiChannelSignal: General error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        }

        return view(Helper::themeView('user.trading.multi-channel-signal'), $data);
    }

    /**
     * Get assignment information for a channel (helper method).
     */
    protected function getChannelAssignmentInfo($channel): array
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
            $userPlan = Auth::user()->subscriptions()
                ->where('is_current', 1)
                ->where(function($q) {
                    $q->where('plan_expired_at', '>', now())
                      ->orWhereNull('plan_expired_at');
                })
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
