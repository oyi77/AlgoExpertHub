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
                        } catch (\Exception $e) {
                            \Log::error('MultiChannelSignal: Error loading channels', ['error' => $e->getMessage()]);
                            $data['channels'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
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
}
