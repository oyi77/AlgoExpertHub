<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * External Signal Controller
 * Wrapper page that combines Signal Sources, Channel Forwarding, and Pattern Templates
 * into a single multi-tab interface
 */
class ExternalSignalController extends Controller
{
    /**
     * Display the external signals page with tabs
     */
    public function index(Request $request): View
    {
        $data['title'] = 'External Signal';
        $data['activeTab'] = $request->get('tab', 'sources'); // Default to 'sources' tab

        // Check if addon is enabled
        $data['multiChannelEnabled'] = \App\Support\AddonRegistry::active('multi-channel-signal-addon') 
            && \App\Support\AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'user_ui');

        return view(Helper::theme() . 'user.external_signals')->with($data);
    }
}

