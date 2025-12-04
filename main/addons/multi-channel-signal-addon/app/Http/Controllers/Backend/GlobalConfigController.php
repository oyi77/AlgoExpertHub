<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Global Configuration Controller
 * 
 * Manages global settings for Multi-Channel Signal Addon (Admin Only)
 */
class GlobalConfigController extends Controller
{
    /**
     * Show global configuration page
     */
    public function index()
    {
        $title = 'Multi-Channel Global Settings';
        
        $config = Cache::get('multi_channel_global_config', [
            // Telegram MTProto Global Config
            'telegram_api_id' => '',
            'telegram_api_hash' => '',
            'telegram_enabled' => false,
            
            // Default Parser Settings
            'default_parser' => 'regex',
            'auto_publish_enabled' => false,
            'default_confidence_threshold' => 80,
        ]);
        
        return view('multi-channel-signal-addon::backend.global-config.index', compact('title', 'config'));
    }

    /**
     * Update global configuration
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'telegram_api_id' => 'nullable|string',
            'telegram_api_hash' => 'nullable|string',
            'telegram_enabled' => 'nullable|boolean',
            'default_parser' => 'required|in:regex,ai,pattern',
            'auto_publish_enabled' => 'nullable|boolean',
            'default_confidence_threshold' => 'required|integer|min:0|max:100',
        ]);

        Cache::put('multi_channel_global_config', $validated, now()->addYear());

        return redirect()->route('admin.multi-channel.global-config.index')
            ->with('success', 'Global configuration updated successfully');
    }

    /**
     * Get global Telegram config (for use in ChannelSource creation)
     */
    public static function getTelegramConfig(): array
    {
        $config = Cache::get('multi_channel_global_config', []);
        
        return [
            'api_id' => $config['telegram_api_id'] ?? '',
            'api_hash' => $config['telegram_api_hash'] ?? '',
            'enabled' => $config['telegram_enabled'] ?? false,
        ];
    }
}

