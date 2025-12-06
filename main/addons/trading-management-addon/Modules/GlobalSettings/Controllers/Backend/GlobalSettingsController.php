<?php

namespace Addons\TradingManagement\Modules\GlobalSettings\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\GlobalConfigurationService;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Global Settings Controller
 * 
 * Manages global MTAPI settings for Trading Management (Admin Only)
 */
class GlobalSettingsController extends Controller
{
    /**
     * Show global settings page
     */
    public function index()
    {
        $title = 'Global Settings';
        
        $mtapiConfig = GlobalConfigurationService::get('mtapi_global_settings', [
            'api_key' => '',
            'base_url' => 'mt5grpc.mtapi.io:443',
            'timeout' => 30,
            'default_host' => '78.140.180.198',
            'default_port' => 443,
            'demo_account' => [
                'user' => '62333850',
                'password' => 'tecimil4',
                'host' => '78.140.180.198',
                'port' => 443,
            ],
        ]);

        // Decrypt MTAPI API key if present
        if (!empty($mtapiConfig['api_key'])) {
            try {
                $mtapiConfig['api_key'] = Crypt::decryptString($mtapiConfig['api_key']);
            } catch (\Exception $e) {
                $mtapiConfig['api_key'] = '';
            }
        }

        // Get MetaApi global settings
        $metaapiConfig = GlobalConfigurationService::get('metaapi_global_settings', [
            'api_token' => '',
            'base_url' => 'https://mt-client-api-v1.london.agiliumtrade.ai',
            'market_data_base_url' => 'https://mt-market-data-client-api-v1.london.agiliumtrade.ai',
            'provisioning_base_url' => 'https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai',
            'billing_base_url' => 'https://billing-api-v1.agiliumtrade.agiliumtrade.ai',
            'timeout' => 30,
        ]);

        // Decrypt MetaApi token if present
        if (!empty($metaapiConfig['api_token'])) {
            try {
                $metaapiConfig['api_token'] = Crypt::decryptString($metaapiConfig['api_token']);
            } catch (\Exception $e) {
                $metaapiConfig['api_token'] = '';
            }
        }

        return view('trading-management::backend.trading-management.config.global-settings', compact('title', 'mtapiConfig', 'metaapiConfig'));
    }

    /**
     * Update global settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // MTAPI settings
            'mtapi_api_key' => 'nullable|string',
            'mtapi_base_url' => 'nullable|string',
            'mtapi_timeout' => 'nullable|integer|min:5|max:300',
            'mtapi_default_host' => 'nullable|string',
            'mtapi_default_port' => 'nullable|integer|min:1|max:65535',
            'mtapi_demo_user' => 'nullable|string',
            'mtapi_demo_password' => 'nullable|string',
            'mtapi_demo_host' => 'nullable|string',
            'mtapi_demo_port' => 'nullable|integer|min:1|max:65535',
            // MetaApi settings
            'metaapi_api_token' => 'nullable|string',
            'metaapi_base_url' => 'nullable|url',
            'metaapi_market_data_base_url' => 'nullable|url',
            'metaapi_provisioning_base_url' => 'nullable|url',
            'metaapi_billing_base_url' => 'nullable|url',
            'metaapi_timeout' => 'nullable|integer|min:5|max:300',
        ]);

        // Update MTAPI settings
        $existingMtapi = GlobalConfigurationService::get('mtapi_global_settings', []);
        $mtapiConfig = [
            'base_url' => $validated['mtapi_base_url'] ?? $existingMtapi['base_url'] ?? 'mt5grpc.mtapi.io:443',
            'timeout' => $validated['mtapi_timeout'] ?? $existingMtapi['timeout'] ?? 30,
            'default_host' => $validated['mtapi_default_host'] ?? $existingMtapi['default_host'] ?? '78.140.180.198',
            'default_port' => $validated['mtapi_default_port'] ?? $existingMtapi['default_port'] ?? 443,
            'demo_account' => [
                'user' => $validated['mtapi_demo_user'] ?? $existingMtapi['demo_account']['user'] ?? '62333850',
                'password' => $validated['mtapi_demo_password'] ?? $existingMtapi['demo_account']['password'] ?? 'tecimil4',
                'host' => $validated['mtapi_demo_host'] ?? $existingMtapi['demo_account']['host'] ?? '78.140.180.198',
                'port' => $validated['mtapi_demo_port'] ?? $existingMtapi['demo_account']['port'] ?? 443,
            ],
        ];

        // Encrypt MTAPI API key if provided
        if (!empty($validated['mtapi_api_key'])) {
            $mtapiConfig['api_key'] = Crypt::encryptString($validated['mtapi_api_key']);
        } elseif (!empty($existingMtapi['api_key'])) {
            $mtapiConfig['api_key'] = $existingMtapi['api_key'];
        }

        GlobalConfigurationService::set(
            'mtapi_global_settings',
            $mtapiConfig,
            'MTAPI gRPC global settings including API key, base URL, timeout, and demo account credentials'
        );

        // Update MetaApi settings
        $existingMetaapi = GlobalConfigurationService::get('metaapi_global_settings', []);
        $metaapiConfig = [
            'base_url' => $validated['metaapi_base_url'] ?? $existingMetaapi['base_url'] ?? 'https://mt-client-api-v1.london.agiliumtrade.ai',
            'market_data_base_url' => $validated['metaapi_market_data_base_url'] ?? $existingMetaapi['market_data_base_url'] ?? 'https://mt-market-data-client-api-v1.london.agiliumtrade.ai',
            'provisioning_base_url' => $validated['metaapi_provisioning_base_url'] ?? $existingMetaapi['provisioning_base_url'] ?? 'https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai',
            'billing_base_url' => $validated['metaapi_billing_base_url'] ?? $existingMetaapi['billing_base_url'] ?? 'https://billing-api-v1.agiliumtrade.agiliumtrade.ai',
            'timeout' => $validated['metaapi_timeout'] ?? $existingMetaapi['timeout'] ?? 30,
        ];

        // Encrypt MetaApi token if provided
        if (!empty($validated['metaapi_api_token'])) {
            $metaapiConfig['api_token'] = Crypt::encryptString($validated['metaapi_api_token']);
        } elseif (!empty($existingMetaapi['api_token'])) {
            $metaapiConfig['api_token'] = $existingMetaapi['api_token'];
        }

        GlobalConfigurationService::set(
            'metaapi_global_settings',
            $metaapiConfig,
            'MetaApi.cloud global settings including API token, base URLs, and timeout'
        );

        return redirect()->route('admin.trading-management.config.global-settings.index')
            ->with('success', 'Global settings updated successfully');
    }

    /**
     * Test connection using demo credentials
     */
    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'user' => 'required|string',
            'password' => 'required|string',
            'host' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
        ]);

        try {
            $adapter = new MtapiGrpcAdapter([
                'user' => $validated['user'],
                'password' => $validated['password'],
                'host' => $validated['host'],
                'port' => $validated['port'],
            ]);

            $result = $adapter->testConnection();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'latency' => 0,
            ], 400);
        }
    }

    /**
     * Test connection using saved demo credentials
     */
    public function testDemoConnection()
    {
        $config = GlobalConfigurationService::get('mtapi_global_settings', []);
        
        $demoAccount = $config['demo_account'] ?? [
            'user' => '62333850',
            'password' => 'tecimil4',
            'host' => '78.140.180.198',
            'port' => 443,
        ];

        try {
            $adapter = new MtapiGrpcAdapter([
                'user' => $demoAccount['user'],
                'password' => $demoAccount['password'],
                'host' => $demoAccount['host'],
                'port' => $demoAccount['port'],
                'base_url' => $config['base_url'] ?? 'mt5grpc.mtapi.io:443',
                'timeout' => $config['timeout'] ?? 30,
            ]);

            $result = $adapter->testConnection();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'latency' => 0,
            ], 400);
        }
    }
}
