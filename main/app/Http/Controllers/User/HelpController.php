<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    /**
     * Show help page
     */
    public function index(Request $request)
    {
        $topic = $request->get('topic', 'general');
        
        $data['title'] = __('Help Center');
        $data['topic'] = $topic;
        $data['topics'] = [
            'general' => __('General Help'),
            'signals' => __('Trading Signals'),
            'auto-trading' => __('Auto Trading'),
            'presets' => __('Trading Presets'),
            'marketplaces' => __('Marketplaces'),
            'wallet' => __('Wallet & Payments'),
        ];
        
        return view(\App\Helpers\Helper\Helper::themeView('user.help.index')->with($data);
    }
    
    /**
     * Show specific help topic
     */
    public function topic(string $topic)
    {
        $data['title'] = __('Help: :topic', ['topic' => ucfirst($topic)]);
        $data['topic'] = $topic;
        
        return view(\App\Helpers\Helper\Helper::themeView('user.help.topic')->with($data);
    }
}

