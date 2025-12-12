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
            // All Signals tab
            if ($data['activeTab'] === 'all-signals') {
                $data['signals'] = \App\Models\Signal::where('auto_created', 1)
                    ->with(['pair', 'time', 'market', 'channelSource'])
                    ->latest()
                    ->paginate(20, ['*'], 'signals_page');
            }

            // Signal Sources tab
            if ($data['activeTab'] === 'signal-sources') {
                if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
                    $data['sources'] = \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::where('user_id', Auth::id())
                        ->where('is_admin_owned', false)
                        ->latest()
                        ->paginate(20, ['*'], 'sources_page');
                }
            }

            // Channel Forwarding tab
            if ($data['activeTab'] === 'channel-forwarding') {
                if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
                    $data['channels'] = \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::assignedToUser(Auth::id())
                        ->where('status', 'active')
                        ->with(['assignedUsers', 'assignedPlans', 'signals'])
                        ->latest()
                        ->paginate(20, ['*'], 'channels_page');
                }
            }

            // Signal Review tab
            if ($data['activeTab'] === 'signal-review') {
                $data['reviewSignals'] = \App\Models\Signal::where('auto_created', 1)
                    ->where('is_published', 0) // Draft signals
                    ->with(['pair', 'time', 'market', 'channelSource'])
                    ->latest()
                    ->paginate(20, ['*'], 'review_page');
            }

            // Pattern Templates tab
            if ($data['activeTab'] === 'pattern-templates') {
                // User can view and create pattern templates
                if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern::class)) {
                    $data['patterns'] = \Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern::where('user_id', Auth::id())
                        ->latest()
                        ->paginate(20, ['*'], 'patterns_page');
                }
            }

            // Analytics tab
            if ($data['activeTab'] === 'analytics') {
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
            }
        }

        return view(Helper::themeView('user.trading.multi-channel-signal', $data);
    }
}
