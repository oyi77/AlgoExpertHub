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
        
        $config = GlobalConfigurationService::get('mtapi_global_settings', [
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

        // Decrypt API key if present
        if (!empty($config['api_key'])) {
            try {
                $config['api_key'] = Crypt::decryptString($config['api_key']);
            } catch (\Exception $e) {
                $config['api_key'] = '';
            }
        }

        return view('trading-management::backend.trading-management.config.global-settings', compact('title', 'config'));
    }

    /**
     * Update global settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string',
            'base_url' => 'required|string',
            'timeout' => 'required|integer|min:5|max:300',
            'default_host' => 'nullable|string',
            'default_port' => 'nullable|integer|min:1|max:65535',
            'demo_user' => 'nullable|string',
            'demo_password' => 'nullable|string',
            'demo_host' => 'nullable|string',
            'demo_port' => 'nullable|integer|min:1|max:65535',
        ]);

        // Prepare config array
        $config = [
            'base_url' => $validated['base_url'],
            'timeout' => $validated['timeout'],
            'default_host' => $validated['default_host'] ?? '78.140.180.198',
            'default_port' => $validated['default_port'] ?? 443,
            'demo_account' => [
                'user' => $validated['demo_user'] ?? '62333850',
                'password' => $validated['demo_password'] ?? 'tecimil4',
                'host' => $validated['demo_host'] ?? '78.140.180.198',
                'port' => $validated['demo_port'] ?? 443,
            ],
        ];

        // Encrypt API key if provided
        if (!empty($validated['api_key'])) {
            $config['api_key'] = Crypt::encryptString($validated['api_key']);
        } else {
            // Keep existing API key if not provided
            $existing = GlobalConfigurationService::get('mtapi_global_settings', []);
            if (!empty($existing['api_key'])) {
                $config['api_key'] = $existing['api_key'];
            }
        }

        // Save to global configurations
        GlobalConfigurationService::set(
            'mtapi_global_settings',
            $config,
            'MTAPI gRPC global settings including API key, base URL, timeout, and demo account credentials'
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
