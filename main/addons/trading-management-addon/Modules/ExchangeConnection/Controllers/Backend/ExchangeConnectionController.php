<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\DataProvider\Services\MetaApiProvisioningService;
use Illuminate\Http\Request;

/**
 * Unified Exchange Connection Controller
 * 
 * Manages connections that can be used for BOTH data fetching AND trade execution
 */
class ExchangeConnectionController extends Controller
{
    public function index()
    {
        $title = 'Exchange Connections';
        $connections = ExchangeConnection::with(['admin', 'user', 'preset'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('trading-management::backend.exchange-connections.index', compact('title', 'connections'));
    }

    public function create()
    {
        $title = 'Create Exchange Connection';
        $presets = TradingPreset::where('is_default_template', 1)->get();
        
        return view('trading-management::backend.exchange-connections.create', compact('title', 'presets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'connection_type' => 'required|in:CRYPTO_EXCHANGE,FX_BROKER',
            'provider' => 'required|string',
            'credentials' => 'required|array',
            'data_fetching_enabled' => 'nullable|boolean',
            'trade_execution_enabled' => 'nullable|boolean',
            'preset_id' => 'nullable|exists:trading_presets,id',
            'data_settings' => 'nullable|array',
        ]);

        // Validate credentials based on provider
        if ($validated['provider'] === 'metaapi') {
            if (empty($validated['credentials']['account_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['credentials.account_id' => 'MetaApi Account ID is required']);
            }
            // Auto-fill api_token from config if not provided
            if (empty($validated['credentials']['api_token'])) {
                $validated['credentials']['api_token'] = config('trading-management.metaapi.api_token');
            }
        }

        $connection = ExchangeConnection::create([
            ...$validated,
            'admin_id' => auth()->guard('admin')->id(),
            'is_admin_owned' => true,
            'status' => 'pending',
        ]);

        return redirect()->route('admin.trading-management.config.exchange-connections.show', $connection)
            ->with('success', 'Exchange connection created. Test it below.');
    }

    public function show(ExchangeConnection $exchangeConnection)
    {
        $title = 'Exchange Connection - ' . $exchangeConnection->name;
        $connection = $exchangeConnection->load('preset');
        
        return view('trading-management::backend.exchange-connections.show', compact('title', 'connection'));
    }

    /**
     * Test data fetching
     */
    public function testDataFetch(Request $request)
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:exchange_connections,id',
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $connection = ExchangeConnection::findOrFail($validated['connection_id']);
        
        try {
            // Use appropriate adapter based on connection type
            $adapter = $this->getAdapter($connection);
            $result = $adapter->fetchCandles($validated['symbol'], $validated['timeframe'], $validated['limit'] ?? 100);

            $connection->update([
                'last_data_fetch_at' => now(),
                'status' => 'connected',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data fetched successfully',
                'data' => $result['data'] ?? [],
                'count' => count($result['data'] ?? []),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Test trade execution capabilities
     */
    public function testExecution(Request $request)
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:exchange_connections,id',
            'test_type' => 'required|in:balance,positions,test_order',
        ]);

        $connection = ExchangeConnection::findOrFail($validated['connection_id']);
        
        try {
            $adapter = $this->getAdapter($connection);
            
            $result = match($validated['test_type']) {
                'balance' => $this->testFetchBalance($adapter),
                'positions' => $this->testFetchPositions($adapter),
                'test_order' => $this->testPlaceOrder($adapter),
            };

            $connection->update([
                'last_trade_execution_at' => now(),
                'status' => 'connected',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test successful',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    protected function getAdapter(ExchangeConnection $connection)
    {
        // Return appropriate adapter (CCXT or MT4/MT5)
        if ($connection->connection_type === 'CRYPTO_EXCHANGE') {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter(
                $connection->credentials,
                $connection->provider
            );
        } else {
            // Check provider type
            if ($connection->provider === 'mtapi_grpc' || 
                (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'mtapi_grpc')) {
                // Merge global settings if available
                $credentials = $connection->credentials;
                $globalSettings = \App\Services\GlobalConfigurationService::get('mtapi_global_settings', []);
                
                if (!empty($globalSettings['base_url'])) {
                    $credentials['base_url'] = $globalSettings['base_url'];
                }
                if (!empty($globalSettings['timeout'])) {
                    $credentials['timeout'] = $globalSettings['timeout'];
                }
                
                return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter($credentials);
            } elseif ($connection->provider === 'metaapi') {
                return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter(
                    $connection->credentials
                );
            } else {
                return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter(
                    $connection->credentials
                );
            }
        }
    }

    protected function testFetchBalance($adapter): array
    {
        // Implement balance fetching
        return ['total' => 0, 'available' => 0, 'used' => 0];
    }

    protected function testFetchPositions($adapter): array
    {
        // Implement positions fetching
        return [];
    }

    protected function testPlaceOrder($adapter): array
    {
        // Implement test order (dry run)
        return ['orderId' => 'TEST_' . time(), 'status' => 'test'];
    }

    /**
     * Add MT account to MetaApi
     */
    public function addMetaApiAccount(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'server' => 'required|string',
            'name' => 'required|string|max:255',
            'platform' => 'required|in:MT4,MT5,mt4,mt5',
            'provisioning_profile_id' => 'nullable|string',
            'account_type' => 'nullable|in:cloud-g1,cloud-g2',
            'magic' => 'nullable|integer|min:0',
            'manual_trades' => 'nullable|boolean',
        ]);

        try {
            $provisioningService = new MetaApiProvisioningService();

            $result = $provisioningService->addAccount([
                'login' => $validated['login'],
                'password' => $validated['password'],
                'server' => $validated['server'],
                'name' => $validated['name'],
                'platform' => $validated['platform'],
                'provisioningProfileId' => $validated['provisioning_profile_id'] ?? null,
                'type' => $validated['account_type'] ?? 'cloud-g2',
                'magic' => $validated['magic'] ?? null,
                'manualTrades' => $validated['manual_trades'] ?? false,
            ]);

            if ($result['success']) {
                // Create exchange connection automatically
                $connection = ExchangeConnection::create([
                    'name' => $validated['name'],
                    'connection_type' => 'FX_BROKER',
                    'provider' => 'metaapi',
                    'credentials' => [
                        'api_token' => config('trading-management.metaapi.api_token'),
                        'account_id' => $result['account_id'],
                    ],
                    'admin_id' => auth()->guard('admin')->id(),
                    'is_admin_owned' => true,
                    'status' => 'pending',
                    'data_fetching_enabled' => true,
                    'trade_execution_enabled' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'metaapi_account_id' => $result['account_id'],
                    'connection_id' => $connection->id,
                    'data' => $result['data'] ?? [],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to add MetaApi account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get MetaApi account status
     */
    public function getMetaApiAccountStatus(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|string',
        ]);

        try {
            $provisioningService = new MetaApiProvisioningService();
            $result = $provisioningService->getAccountStatus($validated['account_id']);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

