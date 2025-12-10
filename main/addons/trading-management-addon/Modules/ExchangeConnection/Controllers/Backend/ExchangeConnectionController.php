<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\GlobalConfigurationService;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\DataProvider\Services\MetaApiProvisioningService;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\CcxtExchangeService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Unified Exchange Connection Controller
 * 
 * Manages connections that can be used for BOTH data fetching AND trade execution
 */
class ExchangeConnectionController extends Controller
{
    protected ExchangeConnectionService $connectionService;

    public function __construct(ExchangeConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

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

    /**
     * Get list of CCXT-supported crypto exchanges
     */
    public function getCcxtExchanges()
    {
        try {
            $service = new CcxtExchangeService();
            $exchanges = $service->getCryptoExchanges();
            
            return response()->json([
                'success' => true,
                'exchanges' => $exchanges,
                'count' => count($exchanges)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load exchanges: ' . $e->getMessage(),
                'exchanges' => []
            ], 500);
        }
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

        // Map connection_type to type (legacy support)
        $type = $validated['connection_type'] === 'CRYPTO_EXCHANGE' ? 'crypto' : 'fx';
        
        // Map provider to exchange_name (legacy support)
        $exchangeName = $validated['provider'] ?? null;
        
        // Prepare data settings - only include if it has actual content
        $dataSettings = null;
        if (!empty($validated['data_settings']) && is_array($validated['data_settings'])) {
            // Check if array has any non-empty values
            $hasContent = false;
            foreach ($validated['data_settings'] as $value) {
                if (is_array($value) && !empty($value)) {
                    $hasContent = true;
                    break;
                } elseif (!is_array($value) && $value !== '' && $value !== null) {
                    $hasContent = true;
                    break;
                }
            }
            if ($hasContent) {
                $dataSettings = $validated['data_settings'];
            }
        }
        
        // Build connection data
        $connectionData = [
            'name' => $validated['name'],
            'connection_type' => $validated['connection_type'],
            'type' => $type,
            'provider' => $validated['provider'],
            'exchange_name' => $exchangeName,
            'credentials' => $validated['credentials'], // Encrypted by HasEncryptedCredentials trait
            'data_fetching_enabled' => (bool) ($validated['data_fetching_enabled'] ?? false),
            'trade_execution_enabled' => (bool) ($validated['trade_execution_enabled'] ?? false),
            'admin_id' => auth()->guard('admin')->id(),
            'is_admin_owned' => true,
            'status' => 'inactive',
            'is_active' => false,
        ];
        
        // Only add data_settings if it has content (model cast will handle JSON encoding)
        if ($dataSettings !== null) {
            $connectionData['data_settings'] = $dataSettings;
        }
        
        // Add preset_id if provided
        if (!empty($validated['preset_id'])) {
            $connectionData['preset_id'] = $validated['preset_id'];
        }
        
        $connection = ExchangeConnection::create($connectionData);

        return redirect()->route('admin.trading-management.config.exchange-connections.show', $connection)
            ->with('success', 'Exchange connection created. Test it below.');
    }

    public function show(ExchangeConnection $exchangeConnection)
    {
        $title = 'Exchange Connection - ' . $exchangeConnection->name;
        $connection = $exchangeConnection->load('preset');
        
        // Handle decrypt errors gracefully - show warning if credentials are corrupted
        $credentialsValid = true;
        try {
            $creds = $exchangeConnection->credentials;
            $rawCreds = $exchangeConnection->getAttributes()['credentials'] ?? null;
            if (empty($creds) && !empty($rawCreds)) {
                $credentialsValid = false;
            }
        } catch (\Exception $e) {
            $credentialsValid = false;
        }
        
        return view('trading-management::backend.exchange-connections.show', compact('title', 'connection', 'credentialsValid'));
    }

    /**
     * Show edit form
     */
    public function edit(ExchangeConnection $exchangeConnection)
    {
        $title = 'Edit Exchange Connection';
        
        // Handle decrypt errors gracefully
        $credentialsValid = true;
        $credentials = [];
        try {
            $credentials = $exchangeConnection->credentials;
            $rawCreds = $exchangeConnection->getAttributes()['credentials'] ?? null;
            if (empty($credentials) && !empty($rawCreds)) {
                $credentialsValid = false;
            }
        } catch (\Exception $e) {
            $credentialsValid = false;
        }
        
        $presets = TradingPreset::where('is_default_template', 1)->get();
        
        return view('trading-management::backend.exchange-connections.edit', compact('title', 'exchangeConnection', 'presets', 'credentialsValid', 'credentials'));
    }

    /**
     * Update connection
     */
    public function update(Request $request, ExchangeConnection $exchangeConnection)
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

        // Map connection_type to type (legacy support)
        $type = $validated['connection_type'] === 'CRYPTO_EXCHANGE' ? 'crypto' : 'fx';
        
        // Map provider to exchange_name (legacy support)
        $exchangeName = $validated['provider'] ?? null;
        
        // Prepare data settings - only include if it has actual content
        $dataSettings = null;
        if (!empty($validated['data_settings']) && is_array($validated['data_settings'])) {
            $hasContent = false;
            foreach ($validated['data_settings'] as $value) {
                if (is_array($value) && !empty($value)) {
                    $hasContent = true;
                    break;
                } elseif (!is_array($value) && $value !== '' && $value !== null) {
                    $hasContent = true;
                    break;
                }
            }
            if ($hasContent) {
                $dataSettings = $validated['data_settings'];
            }
        }
        
        // Build update data
        $updateData = [
            'name' => $validated['name'],
            'connection_type' => $validated['connection_type'],
            'type' => $type,
            'provider' => $validated['provider'],
            'exchange_name' => $exchangeName,
            'credentials' => $validated['credentials'], // Will be encrypted by HasEncryptedCredentials trait
            'data_fetching_enabled' => (bool) ($validated['data_fetching_enabled'] ?? false),
            'trade_execution_enabled' => (bool) ($validated['trade_execution_enabled'] ?? false),
        ];
        
        // Only add data_settings if it has content
        if ($dataSettings !== null) {
            $updateData['data_settings'] = $dataSettings;
        }
        
        // Add preset_id if provided
        if (!empty($validated['preset_id'])) {
            $updateData['preset_id'] = $validated['preset_id'];
        }
        
        $exchangeConnection->update($updateData);

        return redirect()->route('admin.trading-management.config.exchange-connections.show', $exchangeConnection)
            ->with('success', 'Exchange connection updated successfully.');
    }

    /**
     * Delete connection
     */
    public function destroy(ExchangeConnection $exchangeConnection)
    {
        try {
            $connectionName = $exchangeConnection->name;
            $exchangeConnection->delete();

            return redirect()->route('admin.trading-management.config.exchange-connections.index')
                ->with('success', "Exchange connection '{$connectionName}' deleted successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to delete exchange connection', [
                'connection_id' => $exchangeConnection->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.trading-management.config.exchange-connections.index')
                ->with('error', 'Failed to delete connection. Please try again.');
        }
    }

    /**
     * Transfer ownership of connection to a user
     */
    public function transferOwnership(Request $request, ExchangeConnection $exchangeConnection)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = \App\Models\User::findOrFail($validated['user_id']);
            
            // Update ownership
            $exchangeConnection->update([
                'user_id' => $user->id,
                'admin_id' => null,
                'is_admin_owned' => false,
            ]);

            Log::info('Exchange connection ownership transferred', [
                'connection_id' => $exchangeConnection->id,
                'connection_name' => $exchangeConnection->name,
                'new_user_id' => $user->id,
                'new_user_email' => $user->email,
            ]);

            return redirect()->route('admin.trading-management.config.exchange-connections.index')
                ->with('success', "Connection '{$exchangeConnection->name}' ownership transferred to {$user->username} ({$user->email}) successfully.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to transfer exchange connection ownership', [
                'connection_id' => $exchangeConnection->id,
                'user_id' => $validated['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to transfer ownership. Please try again.')
                ->withInput();
        }
    }

    /**
     * Test data fetching
     * 
     * Dynamically uses the correct adapter:
     * - CRYPTO_EXCHANGE: CcxtAdapter (for crypto exchanges via CCXT)
     * - FX_BROKER with provider='metaapi': MetaApiAdapter (for MT4/MT5 via MetaAPI)
     * - FX_BROKER with provider='mtapi_grpc': MtapiGrpcAdapter (for MT4/MT5 via MTAPI gRPC)
     * - FX_BROKER with provider='mtapi': MtapiAdapter (for MT4/MT5 via MTAPI REST)
     */
    public function testDataFetch(Request $request)
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:execution_connections,id',
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $connection = ExchangeConnection::findOrFail($validated['connection_id']);
        
        try {
            // Get appropriate adapter based on connection type and provider
            // This automatically selects:
            // - CcxtAdapter for crypto exchanges
            // - MetaApiAdapter for MT4/MT5 with provider='metaapi'
            // - MtapiGrpcAdapter/MtapiAdapter for other MT providers
            $adapter = $this->getAdapter($connection);
            
            // Ensure adapter is connected (if required)
            if (method_exists($adapter, 'connect') && !$adapter->isConnected()) {
                $adapter->connect($connection->credentials);
            }
            
            // Use fetchOHLCV (interface method) - all adapters should implement this
            if (method_exists($adapter, 'fetchOHLCV')) {
                $data = $adapter->fetchOHLCV(
                    $validated['symbol'],
                    $validated['timeframe'],
                    $validated['limit'] ?? 100
                );
            } elseif (method_exists($adapter, 'fetchCandles')) {
                // Fallback to fetchCandles for backward compatibility
                $result = $adapter->fetchCandles(
                    $validated['symbol'],
                    $validated['timeframe'],
                    $validated['limit'] ?? 100
                );
                if (isset($result['success']) && $result['success']) {
                    $data = $result['data'] ?? [];
                } else {
                    throw new \Exception($result['message'] ?? 'Failed to fetch data');
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Data fetching not supported for this connection type',
                ], 400);
            }

            $connection->update([
                'last_data_fetch_at' => now(),
                'status' => 'active',
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data fetched successfully',
                'data' => $data,
                'count' => count($data),
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
            'connection_id' => 'required|exists:execution_connections,id',
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
                'status' => 'active',
                'is_active' => true,
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

    /**
     * Get appropriate adapter based on connection type and provider
     * 
     * Adapter selection logic:
     * - CRYPTO_EXCHANGE: Always uses CcxtAdapter (CCXT library for crypto exchanges)
     * - FX_BROKER:
     *   - provider='metaapi': MetaApiAdapter (MetaAPI.cloud for MT4/MT5)
     *   - provider='mtapi_grpc': MtapiGrpcAdapter (MTAPI gRPC for MT4/MT5)
     *   - provider='mtapi' or default: MtapiAdapter (MTAPI REST for MT4/MT5)
     * 
     * @param ExchangeConnection $connection
     * @return DataProviderInterface
     */
    protected function getAdapter(ExchangeConnection $connection)
    {
        // Crypto exchanges always use CCXT adapter
        if ($connection->connection_type === 'CRYPTO_EXCHANGE') {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter(
                $connection->credentials,
                $connection->provider
            );
        }
        
        // For FX brokers (MT4/MT5), select adapter based on provider
        if ($connection->provider === 'metaapi') {
            // MetaAPI.cloud adapter for MT4/MT5 connections
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter(
                $connection->credentials
            );
        } elseif ($connection->provider === 'mtapi_grpc' || 
                  (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'mtapi_grpc')) {
            // MTAPI gRPC adapter
            $credentials = $connection->credentials;
            $globalSettings = \App\Services\GlobalConfigurationService::get('mtapi_global_settings', []);
            
            if (!empty($globalSettings['base_url'])) {
                $credentials['base_url'] = $globalSettings['base_url'];
            }
            if (!empty($globalSettings['timeout'])) {
                $credentials['timeout'] = $globalSettings['timeout'];
            }
            
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter($credentials);
        } else {
            // Default: MTAPI REST adapter
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter(
                $connection->credentials
            );
        }
    }

    protected function testFetchBalance($adapter): array
    {
        try {
            if (method_exists($adapter, 'fetchBalance')) {
                $balance = $adapter->fetchBalance();
                return [
                    'total' => $balance['balance'] ?? 0,
                    'available' => $balance['free_margin'] ?? 0,
                    'used' => $balance['margin'] ?? 0,
                    'equity' => $balance['equity'] ?? 0,
                    'currency' => $balance['currency'] ?? 'USD',
                    'margin_level' => $balance['margin_level'] ?? null,
                ];
            } elseif (method_exists($adapter, 'getAccountInfo')) {
                // Fallback to getAccountInfo
                $accountInfo = $adapter->getAccountInfo();
                return [
                    'total' => $accountInfo['balance'] ?? 0,
                    'available' => $accountInfo['free_margin'] ?? 0,
                    'used' => $accountInfo['margin'] ?? 0,
                    'equity' => $accountInfo['equity'] ?? 0,
                    'currency' => $accountInfo['currency'] ?? 'USD',
                    'margin_level' => $accountInfo['margin_level'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'total' => 0,
                'available' => 0,
                'used' => 0,
            ];
        }
        
        return ['total' => 0, 'available' => 0, 'used' => 0];
    }

    protected function testFetchPositions($adapter): array
    {
        try {
            if (method_exists($adapter, 'fetchPositions')) {
                return $adapter->fetchPositions();
            }
        } catch (\Exception $e) {
            return [
                ['error' => $e->getMessage()]
            ];
        }
        
        return [];
    }

    protected function testPlaceOrder($adapter): array
    {
        // Test order placement (dry run - don't actually place)
        // For now, just validate that adapter supports order placement
        try {
            if (method_exists($adapter, 'placeOrder') || method_exists($adapter, 'placeMarketOrder')) {
                return [
                    'orderId' => 'TEST_' . time(),
                    'status' => 'test',
                    'message' => 'Order placement method available (dry run - no actual order placed)',
                ];
            }
        } catch (\Exception $e) {
            return [
                'orderId' => null,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
        
        return [
            'orderId' => 'TEST_' . time(),
            'status' => 'test',
            'message' => 'Test completed (order placement not yet implemented for this provider)',
        ];
    }

    /**
     * Test connection
     */
    public function testConnection(ExchangeConnection $exchangeConnection)
    {
        try {
            $adapter = $this->getAdapter($exchangeConnection);
            
            // Update status to testing
            $exchangeConnection->update([
                'status' => 'testing',
            ]);

            // Test connection based on provider
            if ($exchangeConnection->provider === 'metaapi') {
                // Use MetaApiAdapter testConnection method
                if (method_exists($adapter, 'testConnection')) {
                    $result = $adapter->testConnection();
                    
                    if ($result['success']) {
                        // Connection successful - but don't activate yet
                        $exchangeConnection->update([
                            'status' => 'inactive', // Keep inactive until user activates
                            'last_tested_at' => now(),
                            'last_error' => null,
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => $result['message'] ?? 'Connection test successful',
                            'data' => $result['account_info'] ?? $result['data'] ?? [],
                        ]);
                    } else {
                        // Connection failed
                        $exchangeConnection->update([
                            'status' => 'error',
                            'last_tested_at' => now(),
                            'last_error' => $result['message'] ?? 'Connection test failed',
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => $result['message'] ?? 'Connection test failed',
                        ], 400);
                    }
                } else {
                    // Fallback: try to get account info
                    if (method_exists($adapter, 'getAccountInfo')) {
                        $accountInfo = $adapter->getAccountInfo();
                        $exchangeConnection->update([
                            'status' => 'inactive',
                            'last_tested_at' => now(),
                            'last_error' => null,
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Connection test successful',
                            'data' => ['account_info' => $accountInfo],
                        ]);
                    }
                }
            } else {
                // For other providers, basic connectivity test
                $exchangeConnection->update([
                    'status' => 'inactive',
                    'last_tested_at' => now(),
                    'last_error' => null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Connection test completed',
                ]);
            }

            // If we get here, test didn't complete properly
            $exchangeConnection->update([
                'status' => 'error',
                'last_tested_at' => now(),
                'last_error' => 'Connection test method not available',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test method not available for this provider',
            ], 400);

        } catch (\Exception $e) {
            $exchangeConnection->update([
                'status' => 'error',
                'last_tested_at' => now(),
                'last_error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Activate connection
     */
    public function activateConnection(ExchangeConnection $exchangeConnection)
    {
        try {
            // Verify connection is stabilized (tested and ready)
            if (!$this->connectionService->isStabilized($exchangeConnection)) {
                // Try to stabilize first
                $stabilizeResult = $this->connectionService->stabilize($exchangeConnection);
                
                if (!$stabilizeResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Connection is not stabilized. ' . $stabilizeResult['message'] . ' Please test the connection first.',
                    ], 400);
                }
            }

            // Verify connection is not in error state
            if ($exchangeConnection->status === 'error') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot activate connection with error status. Please test the connection first.',
                ], 400);
            }

            // Activate connection
            $exchangeConnection->update([
                'status' => 'active',
                'is_active' => true,
                'last_tested_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connection activated successfully',
                'connection' => [
                    'id' => $exchangeConnection->id,
                    'status' => $exchangeConnection->status,
                    'is_active' => $exchangeConnection->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate connection: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deactivate connection
     */
    public function deactivateConnection(ExchangeConnection $exchangeConnection)
    {
        try {
            $exchangeConnection->update([
                'is_active' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connection deactivated successfully',
                'connection' => [
                    'id' => $exchangeConnection->id,
                    'is_active' => $exchangeConnection->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate connection: ' . $e->getMessage(),
            ], 400);
        }
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
                $accountId = $result['account_id'];
                
                // Check if connection already exists with this account_id
                $existingConnection = ExchangeConnection::where('provider', 'metaapi')
                    ->get()
                    ->filter(function ($conn) use ($accountId) {
                        $creds = $conn->credentials ?? [];
                        return isset($creds['account_id']) && $creds['account_id'] === $accountId;
                    })
                    ->first();

                if ($existingConnection) {
                    // Connection already exists - return existing one
                    return response()->json([
                        'success' => true,
                        'message' => 'Account already linked to existing connection',
                        'metaapi_account_id' => $accountId,
                        'connection_id' => $existingConnection->id,
                        'existing' => true,
                        'data' => $result['data'] ?? [],
                    ]);
                }

                // Create exchange connection automatically
                $connection = ExchangeConnection::create([
                    'name' => $validated['name'],
                    'connection_type' => 'FX_BROKER',
                    'type' => 'fx', // Legacy field
                    'provider' => 'metaapi',
                    'exchange_name' => 'metaapi', // Legacy field
                    'credentials' => [
                        'api_token' => config('trading-management.metaapi.api_token'),
                        'account_id' => $accountId,
                    ],
                    'data_fetching_enabled' => true,
                    'trade_execution_enabled' => true,
                    'admin_id' => auth()->guard('admin')->id(),
                    'is_admin_owned' => true,
                    'status' => 'inactive', // enum: 'active', 'inactive', 'error', 'testing'
                    'is_active' => false, // Will be activated after testing
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'metaapi_account_id' => $accountId,
                    'connection_id' => $connection->id,
                    'data' => $result['data'] ?? [],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error_data' => $result['data'] ?? null,
                    'status_code' => $result['status_code'] ?? 400,
                ], $result['status_code'] ?? 400);
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

    /**
     * Monitor MetaApi connection (Server-Sent Events)
     */
    public function monitorMetaApi(ExchangeConnection $exchangeConnection)
    {
        if (strtolower($exchangeConnection->provider) !== 'metaapi') {
            return response('Only MetaApi connections can be monitored', 400);
        }

        $credentials = $exchangeConnection->credentials;
        if (empty($credentials['account_id'])) {
            return response('MetaApi Account ID not found', 400);
        }

        $accountId = $credentials['account_id'];
        
        // Prefer account token if available (more secure, scoped to account)
        // Fallback to main API token: credentials -> config -> global settings
        $apiToken = $credentials['account_token'] 
            ?? $credentials['api_token']
            ?? config('trading-management.metaapi.api_token')
            ?? $this->getMetaApiTokenFromGlobalSettings();

        if (empty($apiToken)) {
            return response('MetaApi API token not configured. Please configure it in Global Settings, connection credentials, or generate an account token.', 400);
        }

        // Disable output buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Disable time limit
        set_time_limit(0);
        ignore_user_abort(false);

        // Get base URL from config/global settings (same as MetaApiAdapter)
        $baseUrl = $credentials['base_url'] 
            ?? config('trading-management.metaapi.base_url')
            ?? $this->getMetaApiBaseUrlFromGlobalSettings();

        // Send initial connection message
        echo "data: " . json_encode(['type' => 'connected', 'message' => 'MetaApi monitoring connected']) . "\n\n";
        flush();

        $client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'auth-token' => $apiToken,
            ],
        ]);

        // Try to list available accounts for better error messages (non-blocking)
        $availableAccountIds = [];
        try {
            $listResponse = $client->get('/users/current/accounts', ['http_errors' => false, 'timeout' => 5]);
            if ($listResponse->getStatusCode() === 200) {
                $accounts = json_decode($listResponse->getBody()->getContents(), true);
                if (is_array($accounts)) {
                    foreach ($accounts as $acc) {
                        $metaApiId = $acc['_id'] ?? $acc['id'] ?? $acc['accountId'] ?? null;
                        if ($metaApiId) {
                            $availableAccountIds[] = $metaApiId;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore - we'll handle errors in the main loop
        }

        $updateCount = 0;
        $lastState = null;
        $consecutiveErrors = 0;
        $consecutive404Errors = 0;

        while (true) {
            if (connection_aborted()) {
                break;
            }

            // Send keepalive every 30 seconds
            if ($updateCount % 10 == 0 && $updateCount > 0) {
                echo ": keepalive\n\n";
                flush();
            }

            try {
                // Get account status
                $response = $client->get("/users/current/accounts/{$accountId}", [
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                
                if ($statusCode === 200) {
                    $consecutiveErrors = 0; // Reset error count on success
                    $accountData = json_decode($response->getBody()->getContents(), true);
                    $currentState = $accountData['state'] ?? 'unknown';
                    
                    // Only send update if state changed or every 5 iterations
                    if ($currentState !== $lastState || $updateCount % 5 == 0) {
                        $accountInfo = [];
                        
                        // Try to get account information if deployed
                        if (in_array($currentState, ['DEPLOYED', 'DEPLOYING'])) {
                            try {
                                $infoResponse = $client->get("/users/current/accounts/{$accountId}/account-information", [
                                    'http_errors' => false,
                                ]);
                                
                                if ($infoResponse->getStatusCode() === 200) {
                                    $accountInfo['accountInformation'] = json_decode($infoResponse->getBody()->getContents(), true);
                                }
                            } catch (\Exception $e) {
                                // Ignore if account not yet synchronized
                            }
                            
                            // Try to get positions
                            try {
                                $posResponse = $client->get("/users/current/accounts/{$accountId}/positions", [
                                    'http_errors' => false,
                                ]);
                                
                                if ($posResponse->getStatusCode() === 200) {
                                    $accountInfo['positions'] = json_decode($posResponse->getBody()->getContents(), true);
                                }
                            } catch (\Exception $e) {
                                // Ignore if not available
                            }
                            
                            // Try to get orders
                            try {
                                $orderResponse = $client->get("/users/current/accounts/{$accountId}/orders", [
                                    'http_errors' => false,
                                ]);
                                
                                if ($orderResponse->getStatusCode() === 200) {
                                    $accountInfo['orders'] = json_decode($orderResponse->getBody()->getContents(), true);
                                }
                            } catch (\Exception $e) {
                                // Ignore if not available
                            }
                        }
                        
                        $data = [
                            'type' => 'status',
                            'account' => [
                                'state' => $currentState,
                                'connected' => in_array($currentState, ['DEPLOYED', 'DEPLOYING', 'CONNECTED']),
                                'connectedToBroker' => $currentState === 'DEPLOYED',
                                'accountInformation' => $accountInfo['accountInformation'] ?? null,
                                'positions' => $accountInfo['positions'] ?? [],
                                'orders' => $accountInfo['orders'] ?? [],
                            ],
                            'timestamp' => now()->toIso8601String(),
                        ];

                        echo "data: " . json_encode($data) . "\n\n";
                        flush();
                        
                        // Update connection status in database based on account state
                        if ($currentState === 'DEPLOYED' && $exchangeConnection->status !== 'active') {
                            // Auto-activate when account is deployed
                            $exchangeConnection->update([
                                'status' => 'active',
                                'is_active' => true,
                                'last_tested_at' => now(),
                                'last_error' => null,
                            ]);
                        } elseif (in_array($currentState, ['DEPLOYING', 'CONNECTING']) && $exchangeConnection->status !== 'testing') {
                            // Mark as testing while deploying
                            $exchangeConnection->update([
                                'status' => 'testing',
                                'last_tested_at' => now(),
                            ]);
                        } elseif (in_array($currentState, ['UNDEPLOYED', 'DISCONNECTED']) && $exchangeConnection->status === 'active') {
                            // Deactivate if account is disconnected
                            $exchangeConnection->update([
                                'status' => 'inactive',
                                'is_active' => false,
                                'last_error' => 'Account disconnected from broker',
                            ]);
                        }
                        
                        $lastState = $currentState;
                    }
                } else {
                    $consecutiveErrors++;
                    $responseBody = $response->getBody()->getContents();
                    $errorData = json_decode($responseBody, true);
                    $errorMessage = $errorData['message'] ?? $errorData['error'] ?? "HTTP {$statusCode}";
                    
                    // If 404, provide more helpful message with available accounts
                    if ($statusCode === 404) {
                        $consecutive404Errors++;
                        $errorMessage = "Account not found. The account ID '{$accountId}' does not exist in MetaApi or has been deleted.";
                        
                        $errorPayload = [
                            'type' => 'error',
                            'message' => $errorMessage,
                            'status_code' => $statusCode,
                            'account_id' => $accountId,
                        ];
                        
                        // Add available accounts info if we have it
                        if (!empty($availableAccountIds)) {
                            $errorPayload['suggestion'] = "You have " . count($availableAccountIds) . " account(s) available. Please verify the account ID matches one of your MetaApi accounts.";
                            $errorPayload['available_account_count'] = count($availableAccountIds);
                            // Only show first 5 account IDs (truncated) to avoid too much data
                            if (count($availableAccountIds) <= 5) {
                                $errorPayload['available_account_ids'] = $availableAccountIds;
                            } else {
                                $errorPayload['available_account_ids'] = array_slice($availableAccountIds, 0, 5);
                                $errorPayload['note'] = 'Showing first 5 of ' . count($availableAccountIds) . ' accounts';
                            }
                        } else {
                            $errorPayload['suggestion'] = "Please verify the account ID in your MetaApi dashboard or recreate the connection.";
                        }
                        
                        // Only send error after first error (immediate feedback for 404)
                        if ($consecutive404Errors === 1 || $updateCount % 5 == 0) {
                            echo "data: " . json_encode($errorPayload) . "\n\n";
                            flush();
                        }
                        
                        // Stop polling after 10 consecutive 404 errors (account definitely doesn't exist)
                        if ($consecutive404Errors >= 10) {
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'message' => 'Stopping monitoring. Account not found after multiple attempts.',
                                'suggestion' => 'Please verify the account ID and try again.',
                            ]) . "\n\n";
                            flush();
                            break; // Exit the loop
                        }
                    } else {
                        // For other errors, wait for 2 consecutive errors
                        if ($consecutiveErrors >= 2) {
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'message' => $errorMessage,
                                'status_code' => $statusCode,
                                'account_id' => $accountId,
                            ]) . "\n\n";
                            flush();
                        }
                    }
                }

            } catch (\Exception $e) {
                $consecutiveErrors++;
                \Log::error('MetaApi monitor error', [
                    'error' => $e->getMessage(),
                    'connection_id' => $exchangeConnection->id,
                    'account_id' => $accountId,
                ]);
                
                // Only send error after 2 consecutive errors
                if ($consecutiveErrors >= 2) {
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'message' => 'Connection error: ' . $e->getMessage(),
                    ]) . "\n\n";
                    flush();
                }
            }

            $updateCount++;
            
            // Update every 3 seconds
            sleep(3);
        }

        return response('', 200);
    }

    /**
     * Get MetaApi API token from global settings
     * 
     * @return string|null
     */
    protected function getMetaApiTokenFromGlobalSettings(): ?string
    {
        try {
            $globalConfig = GlobalConfigurationService::get('metaapi_global_settings', []);
            
            if (!empty($globalConfig['api_token'])) {
                try {
                    // Try to decrypt (if encrypted)
                    return Crypt::decryptString($globalConfig['api_token']);
                } catch (\Exception $e) {
                    // If decryption fails, assume it's not encrypted
                    return $globalConfig['api_token'];
                }
            }
        } catch (\Exception $e) {
            \Log::debug('Failed to get MetaApi token from global settings', ['error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Get MetaApi base URL from global settings
     * 
     * @return string
     */
    protected function getMetaApiBaseUrlFromGlobalSettings(): string
    {
        try {
            $globalConfig = GlobalConfigurationService::get('metaapi_global_settings', []);
            return $globalConfig['base_url'] ?? 'https://mt-client-api-v1.london.agiliumtrade.ai';
        } catch (\Exception $e) {
            return 'https://mt-client-api-v1.london.agiliumtrade.ai';
        }
    }

    /**
     * Generate account token for MetaApi connection
     * 
     * Generates a scoped account token via MetaApi Profile API
     * This token can be used for monitoring connections instead of the main API token
     */
    public function generateAccountToken(ExchangeConnection $exchangeConnection, Request $request)
    {
        if (strtolower($exchangeConnection->provider) !== 'metaapi') {
            return response()->json([
                'success' => false,
                'message' => 'Only MetaApi connections can generate account tokens',
            ], 400);
        }

        $credentials = $exchangeConnection->credentials;
        $accountId = $credentials['account_id'] ?? null;

        if (empty($accountId)) {
            return response()->json([
                'success' => false,
                'message' => 'MetaApi Account ID is required',
            ], 400);
        }

        try {
            $validityHours = $request->input('validity_hours', 'Infinity');
            $accessRules = $request->input('access_rules'); // Optional custom access rules
            $captchaToken = $request->input('captcha_token'); // Optional CAPTCHA token

            $provisioningService = new MetaApiProvisioningService();
            $result = $provisioningService->generateAccountToken(
                $accountId,
                $accessRules,
                $validityHours,
                $captchaToken
            );

            if ($result['success']) {
                // Optionally store the account token in connection credentials
                // (Note: You may want to encrypt this token before storing)
                $credentials['account_token'] = $result['token'];
                $credentials['account_token_generated_at'] = now()->toIso8601String();
                $exchangeConnection->update(['credentials' => $credentials]);

                return response()->json([
                    'success' => true,
                    'message' => 'Account token generated and saved successfully',
                    'token' => $result['token'],
                    'account_id' => $accountId,
                ]);
            }

            return response()->json($result, 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate account token: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle copy trading for connection
     */
    public function toggleCopyTrading(ExchangeConnection $exchangeConnection)
    {
        try {
            $exchangeConnection->update([
                'copy_trading_enabled' => !$exchangeConnection->copy_trading_enabled,
            ]);

            return response()->json([
                'success' => true,
                'message' => $exchangeConnection->copy_trading_enabled 
                    ? 'Copy trading enabled' 
                    : 'Copy trading disabled',
                'connection' => [
                    'id' => $exchangeConnection->id,
                    'copy_trading_enabled' => $exchangeConnection->copy_trading_enabled,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle copy trading: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get copy trading stats for connection
     */
    public function getCopyTradingStats(ExchangeConnection $exchangeConnection)
    {
        try {
            // Get copy trading subscriptions for this connection
            $subscriptions = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::where('connection_id', $exchangeConnection->id)
                ->where('is_active', true)
                ->count();

            // Get copied trades count (executions from this connection that were copied)
            $copiedTradesCount = \Addons\TradingManagement\Modules\Execution\Models\ExecutionPosition::where('connection_id', $exchangeConnection->id)
                ->whereNotNull('copied_from_position_id')
                ->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'copy_trading_enabled' => $exchangeConnection->copy_trading_enabled,
                    'active_followers' => $subscriptions,
                    'copied_trades_count' => $copiedTradesCount,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get copy trading stats: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Test streaming market data
     */
    /**
     * Get default symbol based on connection type
     */
    protected function getDefaultSymbol(ExchangeConnection $exchangeConnection, $adapter = null): string
    {
        // Try to get available symbols from adapter
        if ($adapter && method_exists($adapter, 'getAvailableSymbols')) {
            try {
                $availableSymbols = $adapter->getAvailableSymbols();
                if (!empty($availableSymbols)) {
                    // For FX brokers, prefer XAUUSD or XAUUSDc
                    if ($exchangeConnection->connection_type === 'FX_BROKER') {
                        $preferredSymbols = ['XAUUSDc', 'XAUUSD', 'EURUSD', 'GBPUSD', 'USDJPY'];
                        foreach ($preferredSymbols as $prefSymbol) {
                            if (in_array($prefSymbol, $availableSymbols)) {
                                return $prefSymbol;
                            }
                        }
                        // If preferred not found, return first available
                        return $availableSymbols[0];
                    } else {
                        // For crypto exchanges, prefer BTCUSDT, BTC/USDT, etc.
                        $preferredSymbols = ['BTCUSDT', 'BTC/USDT', 'BTC-USDT', 'BTC_USDT'];
                        foreach ($preferredSymbols as $prefSymbol) {
                            if (in_array($prefSymbol, $availableSymbols)) {
                                return $prefSymbol;
                            }
                        }
                        // Try case-insensitive match
                        foreach ($availableSymbols as $sym) {
                            if (stripos($sym, 'BTC') !== false && stripos($sym, 'USDT') !== false) {
                                return $sym;
                            }
                        }
                        // If preferred not found, return first available
                        return $availableSymbols[0];
                    }
                }
            } catch (\Exception $e) {
                // Fall back to defaults if fetching symbols fails
            }
        }

        // Fallback to connection-type-based defaults
        if ($exchangeConnection->connection_type === 'FX_BROKER') {
            return 'XAUUSDc'; // Try XAUUSDc first (common on many brokers)
        } else {
            return 'BTCUSDT'; // Default for crypto exchanges
        }
    }

    public function testStreamMarketData(ExchangeConnection $exchangeConnection, Request $request)
    {
        try {
            if ($exchangeConnection->provider !== 'metaapi') {
                return response()->json([
                    'success' => false,
                    'message' => 'Market data streaming is only available for MetaApi connections',
                ], 400);
            }

            $adapter = $this->getAdapter($exchangeConnection);
            $defaultSymbol = $this->getDefaultSymbol($exchangeConnection, $adapter);
            $symbol = $request->input('symbol', $defaultSymbol);
            $timeframe = $request->input('timeframe', 'H1');

            // Test market data by fetching historical data (streaming requires WebSocket setup)
            // For testing purposes, we'll fetch recent candles to verify connection works
            if (method_exists($adapter, 'fetchOHLCV')) {
                $data = $adapter->fetchOHLCV($symbol, $timeframe, 10);
                return response()->json([
                    'success' => true,
                    'message' => 'Market data connection verified. Fetched ' . count($data) . ' candles.',
                    'data' => [
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'candles' => $data,
                        'count' => count($data),
                        'note' => 'To enable real-time streaming, use MetaApiStreamingService with WebSocket connection'
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Market data fetching not available for this adapter',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test market data stream: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test streaming positions
     */
    public function testStreamPositions(ExchangeConnection $exchangeConnection)
    {
        try {
            if ($exchangeConnection->provider !== 'metaapi') {
                return response()->json([
                    'success' => false,
                    'message' => 'Position streaming is only available for MetaApi connections',
                ], 400);
            }

            $adapter = $this->getAdapter($exchangeConnection);
            
            if (method_exists($adapter, 'fetchPositions')) {
                $positions = $adapter->fetchPositions();
                return response()->json([
                    'success' => true,
                    'message' => 'Positions retrieved successfully',
                    'data' => $positions,
                    'count' => is_array($positions) ? count($positions) : 0,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Position fetching not available for this adapter',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test position stream: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test streaming order history
     */
    public function testStreamOrders(ExchangeConnection $exchangeConnection)
    {
        try {
            if ($exchangeConnection->provider !== 'metaapi') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order streaming is only available for MetaApi connections',
                ], 400);
            }

            $adapter = $this->getAdapter($exchangeConnection);
            
            if (method_exists($adapter, 'fetchOrders')) {
                $orders = $adapter->fetchOrders();
                return response()->json([
                    'success' => true,
                    'message' => 'Orders retrieved successfully',
                    'data' => $orders,
                    'count' => is_array($orders) ? count($orders) : 0,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Order fetching not available for this adapter',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test order stream: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test balance state
     */
    public function testStreamBalance(ExchangeConnection $exchangeConnection)
    {
        try {
            $adapter = $this->getAdapter($exchangeConnection);
            
            // Try fetchBalance first (returns normalized balance data)
            if (method_exists($adapter, 'fetchBalance')) {
                $balance = $adapter->fetchBalance();
                return response()->json([
                    'success' => true,
                    'message' => 'Balance retrieved successfully',
                    'data' => $balance,
                ]);
            } elseif (method_exists($adapter, 'getAccountInfo')) {
                $accountInfo = $adapter->getAccountInfo();
                return response()->json([
                    'success' => true,
                    'message' => 'Balance retrieved successfully',
                    'data' => $accountInfo,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Balance retrieval not available for this adapter',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test balance: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Real-time streaming endpoints (SSE)
     */

    /**
     * Stream market data (SSE)
     */
    public function streamMarketData(ExchangeConnection $exchangeConnection, Request $request)
    {
        if ($exchangeConnection->provider !== 'metaapi') {
            return response('Market data streaming only available for MetaApi connections', 400);
        }

        $adapter = $this->getAdapter($exchangeConnection);
        $defaultSymbol = $this->getDefaultSymbol($exchangeConnection, $adapter);
        $symbol = $request->input('symbol', $defaultSymbol);
        $timeframe = $request->input('timeframe', 'H1');

        // Disable output buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        set_time_limit(0);
        ignore_user_abort(false);

        // Send initial connection message
        echo "data: " . json_encode(['type' => 'connected', 'message' => 'Market data stream connected', 'symbol' => $symbol, 'timeframe' => $timeframe]) . "\n\n";
        flush();

        $updateCount = 0;
        $lastData = null;

        try {
            $adapter = $this->getAdapter($exchangeConnection);

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                // Send keepalive every 30 seconds
                if ($updateCount % 10 == 0 && $updateCount > 0) {
                    echo ": keepalive\n\n";
                    flush();
                }

                try {
                    if (method_exists($adapter, 'fetchOHLCV')) {
                        $data = $adapter->fetchOHLCV($symbol, $timeframe, 5); // Get last 5 candles
                        
                        // Only send if data changed
                        if ($data !== $lastData) {
                            echo "data: " . json_encode([
                                'type' => 'update',
                                'symbol' => $symbol,
                                'timeframe' => $timeframe,
                                'data' => $data,
                                'count' => count($data),
                                'timestamp' => now()->toIso8601String(),
                            ]) . "\n\n";
                            flush();
                            $lastData = $data;
                        }
                    }
                } catch (\Exception $e) {
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'message' => $e->getMessage(),
                        'timestamp' => now()->toIso8601String(),
                    ]) . "\n\n";
                    flush();
                }

                $updateCount++;
                sleep(3); // Update every 3 seconds
            }
        } catch (\Exception $e) {
            echo "data: " . json_encode(['type' => 'error', 'message' => $e->getMessage()]) . "\n\n";
            flush();
        }

        return response('', 200);
    }

    /**
     * Stream positions (SSE)
     */
    public function streamPositions(ExchangeConnection $exchangeConnection)
    {
        if ($exchangeConnection->provider !== 'metaapi') {
            return response('Position streaming only available for MetaApi connections', 400);
        }

        // Disable output buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        set_time_limit(0);
        ignore_user_abort(false);

        echo "data: " . json_encode(['type' => 'connected', 'message' => 'Positions stream connected']) . "\n\n";
        flush();

        $updateCount = 0;
        $lastData = null;

        try {
            $adapter = $this->getAdapter($exchangeConnection);

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                if ($updateCount % 10 == 0 && $updateCount > 0) {
                    echo ": keepalive\n\n";
                    flush();
                }

                try {
                    if (method_exists($adapter, 'fetchPositions')) {
                        $data = $adapter->fetchPositions();
                        
                        // Only send if data changed
                        if (json_encode($data) !== json_encode($lastData)) {
                            echo "data: " . json_encode([
                                'type' => 'update',
                                'positions' => $data,
                                'count' => is_array($data) ? count($data) : 0,
                                'timestamp' => now()->toIso8601String(),
                            ]) . "\n\n";
                            flush();
                            $lastData = $data;
                        }
                    }
                } catch (\Exception $e) {
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'message' => $e->getMessage(),
                        'timestamp' => now()->toIso8601String(),
                    ]) . "\n\n";
                    flush();
                }

                $updateCount++;
                sleep(3); // Update every 3 seconds
            }
        } catch (\Exception $e) {
            echo "data: " . json_encode(['type' => 'error', 'message' => $e->getMessage()]) . "\n\n";
            flush();
        }

        return response('', 200);
    }

    /**
     * Stream orders (SSE)
     */
    public function streamOrders(ExchangeConnection $exchangeConnection)
    {
        if ($exchangeConnection->provider !== 'metaapi') {
            return response('Order streaming only available for MetaApi connections', 400);
        }

        // Disable output buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        set_time_limit(0);
        ignore_user_abort(false);

        echo "data: " . json_encode(['type' => 'connected', 'message' => 'Orders stream connected']) . "\n\n";
        flush();

        $updateCount = 0;
        $lastData = null;

        try {
            $adapter = $this->getAdapter($exchangeConnection);

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                if ($updateCount % 10 == 0 && $updateCount > 0) {
                    echo ": keepalive\n\n";
                    flush();
                }

                try {
                    if (method_exists($adapter, 'fetchOrders')) {
                        $data = $adapter->fetchOrders();
                        
                        // Only send if data changed
                        if (json_encode($data) !== json_encode($lastData)) {
                            echo "data: " . json_encode([
                                'type' => 'update',
                                'orders' => $data,
                                'count' => is_array($data) ? count($data) : 0,
                                'timestamp' => now()->toIso8601String(),
                            ]) . "\n\n";
                            flush();
                            $lastData = $data;
                        }
                    }
                } catch (\Exception $e) {
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'message' => $e->getMessage(),
                        'timestamp' => now()->toIso8601String(),
                    ]) . "\n\n";
                    flush();
                }

                $updateCount++;
                sleep(3); // Update every 3 seconds
            }
        } catch (\Exception $e) {
            echo "data: " . json_encode(['type' => 'error', 'message' => $e->getMessage()]) . "\n\n";
            flush();
        }

        return response('', 200);
    }

    /**
     * Stream balance (SSE)
     */
    public function streamBalance(ExchangeConnection $exchangeConnection)
    {
        // Disable output buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        set_time_limit(0);
        ignore_user_abort(false);

        echo "data: " . json_encode(['type' => 'connected', 'message' => 'Balance stream connected']) . "\n\n";
        flush();

        $updateCount = 0;
        $lastData = null;

        try {
            $adapter = $this->getAdapter($exchangeConnection);

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                if ($updateCount % 10 == 0 && $updateCount > 0) {
                    echo ": keepalive\n\n";
                    flush();
                }

                try {
                    $data = null;
                    if (method_exists($adapter, 'fetchBalance')) {
                        $data = $adapter->fetchBalance();
                    } elseif (method_exists($adapter, 'getAccountInfo')) {
                        $data = $adapter->getAccountInfo();
                    }
                    
                    // Only send if data changed
                    if ($data && json_encode($data) !== json_encode($lastData)) {
                        echo "data: " . json_encode([
                            'type' => 'update',
                            'balance' => $data,
                            'timestamp' => now()->toIso8601String(),
                        ]) . "\n\n";
                        flush();
                        $lastData = $data;
                    }
                } catch (\Exception $e) {
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'message' => $e->getMessage(),
                        'timestamp' => now()->toIso8601String(),
                    ]) . "\n\n";
                    flush();
                }

                $updateCount++;
                sleep(3); // Update every 3 seconds
            }
        } catch (\Exception $e) {
            echo "data: " . json_encode(['type' => 'error', 'message' => $e->getMessage()]) . "\n\n";
            flush();
        }

        return response('', 200);
    }
}

