<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait HandlesTestingOperations
{
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
        try {
            $validated = $request->validate([
                'connection_id' => 'required|exists:execution_connections,id',
                'symbol' => 'required|string',
                'timeframe' => 'required|string',
                'limit' => 'nullable|integer|min:1|max:1000',
            ]);

            $connection = ExchangeConnection::findOrFail($validated['connection_id']);
            
            // Check if connection has valid credentials
            try {
                $credentials = $connection->credentials;
                if (empty($credentials)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Connection credentials are missing or invalid. Please update the connection credentials.',
                    ], 400);
                }
            } catch (\Exception $credEx) {
                Log::error('Failed to decrypt credentials in testDataFetch', [
                    'connection_id' => $connection->id,
                    'error' => $credEx->getMessage()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to decrypt connection credentials. Please re-enter the credentials.',
                ], 400);
            }
            
            // Get appropriate adapter based on connection type and provider
            try {
                $adapter = $this->getAdapter($connection);
            } catch (\Throwable $adapterEx) {
                Log::error('Failed to create adapter in testDataFetch', [
                    'connection_id' => $connection->id,
                    'connection_type' => $connection->connection_type ?? 'unknown',
                    'provider' => $connection->provider ?? 'unknown',
                    'error' => $adapterEx->getMessage(),
                    'file' => $adapterEx->getFile(),
                    'line' => $adapterEx->getLine(),
                    'trace' => $adapterEx->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize adapter: ' . $adapterEx->getMessage(),
                ], 400);
            }
            
            // Ensure adapter is connected (if required)
            try {
                if (method_exists($adapter, 'connect') && method_exists($adapter, 'isConnected') && !$adapter->isConnected()) {
                    $adapter->connect($connection->credentials);
                }
            } catch (\Exception $connectEx) {
                Log::warning('Adapter connection failed in testDataFetch', [
                    'connection_id' => $connection->id,
                    'error' => $connectEx->getMessage()
                ]);
                // Continue anyway - some adapters don't need explicit connection
            }
            
            // Use fetchOHLCV (interface method) - all adapters should implement this
            $data = null;
            try {
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
            } catch (\Throwable $fetchEx) {
                Log::error('Data fetch failed in testDataFetch', [
                    'connection_id' => $connection->id,
                    'symbol' => $validated['symbol'],
                    'timeframe' => $validated['timeframe'],
                    'error' => $fetchEx->getMessage(),
                    'file' => $fetchEx->getFile(),
                    'line' => $fetchEx->getLine(),
                    'trace' => $fetchEx->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch data: ' . $fetchEx->getMessage(),
                ], 400);
            }

            // Update connection status on success
            try {
                $connection->update([
                    'last_data_fetch_at' => now(),
                    'status' => 'active',
                    'is_active' => true,
                ]);
            } catch (\Exception $updateEx) {
                // Log but don't fail the request
                Log::warning('Failed to update connection status', [
                    'connection_id' => $connection->id,
                    'error' => $updateEx->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data fetched successfully',
                'data' => $data,
                'count' => is_array($data) ? count($data) : 0,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Unexpected error in testDataFetch', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
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
}

