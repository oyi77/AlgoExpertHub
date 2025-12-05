<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class GlobalSettingsController extends Controller
{
    public function index()
    {
        $title = 'Global Settings';
        
        // Get current settings from cache or defaults
        $settings = Cache::get('trading_management_global_settings', [
            'mtapi_enabled' => false,
            'mtapi_api_key' => '',
            'mtapi_account_id' => '',
            'mtapi_base_url' => 'https://api.mtapi.io',
        ]);
        
        // Decrypt if encrypted
        if (!empty($settings['mtapi_api_key']) && strpos($settings['mtapi_api_key'], 'eyJpdiI6') === 0) {
            try {
                $settings['mtapi_api_key'] = Crypt::decryptString($settings['mtapi_api_key']);
            } catch (\Exception $e) {
                // If decryption fails, keep as is
            }
        }
        
        return view('trading-management::backend.trading-management.config.global-settings.index', compact('title', 'settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mtapi_enabled' => 'nullable|boolean',
            'mtapi_api_key' => 'nullable|string',
            'mtapi_account_id' => 'nullable|string',
            'mtapi_base_url' => 'nullable|url',
        ]);

        // Encrypt sensitive data
        if (!empty($validated['mtapi_api_key'])) {
            $validated['mtapi_api_key'] = Crypt::encryptString($validated['mtapi_api_key']);
        }
        
        if (!empty($validated['mtapi_account_id'])) {
            $validated['mtapi_account_id'] = Crypt::encryptString($validated['mtapi_account_id']);
        }

        // Merge with existing settings
        $existing = Cache::get('trading_management_global_settings', []);
        $settings = array_merge($existing, $validated);

        Cache::put('trading_management_global_settings', $settings, now()->addYear());

        return redirect()->route('admin.trading-management.config.global-settings.index')
            ->with('success', 'Global settings updated successfully');
    }

    /**
     * Get global MTAPI credentials (for use in connections)
     */
    public static function getMtapiCredentials(): ?array
    {
        $settings = Cache::get('trading_management_global_settings', []);
        
        if (empty($settings['mtapi_enabled']) || empty($settings['mtapi_api_key'])) {
            return null;
        }

        try {
            return [
                'api_key' => Crypt::decryptString($settings['mtapi_api_key']),
                'account_id' => !empty($settings['mtapi_account_id']) 
                    ? Crypt::decryptString($settings['mtapi_account_id']) 
                    : '',
                'base_url' => $settings['mtapi_base_url'] ?? 'https://api.mtapi.io',
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt MTAPI credentials', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
