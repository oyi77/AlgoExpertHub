<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use App\Services\GlobalConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait HandlesStreamingOperations
{
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

            // Validate credentials before proceeding
            $credentials = $exchangeConnection->credentials;
            if (empty($credentials['api_token'])) {
                // Try to get from global settings
                $globalSettings = GlobalConfigurationService::get('metaapi_global_settings', []);
                if (empty($globalSettings['api_token'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'API token is not configured. Please edit this connection and enter your MetaApi API token, or configure it in Global Settings.',
                    ], 400);
                }
            }
            
            if (empty($credentials['account_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account ID is missing. Please edit this connection and enter your MetaApi Account ID.',
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
            Log::error('Failed to test position stream', [
                'connection_id' => $exchangeConnection->id,
                'error' => $e->getMessage(),
            ]);
            
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

            // Validate credentials before proceeding
            $credentials = $exchangeConnection->credentials;
            if (empty($credentials['api_token'])) {
                $globalSettings = GlobalConfigurationService::get('metaapi_global_settings', []);
                if (empty($globalSettings['api_token'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'API token is not configured. Please edit this connection and enter your MetaApi API token, or configure it in Global Settings.',
                    ], 400);
                }
            }
            
            if (empty($credentials['account_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account ID is missing. Please edit this connection and enter your MetaApi Account ID.',
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
            Log::error('Failed to test order stream', [
                'connection_id' => $exchangeConnection->id,
                'error' => $e->getMessage(),
            ]);
            
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
     * Stream market data (SSE)
     */
    public function streamMarketData(ExchangeConnection $exchangeConnection, Request $request)
    {
        try {
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

            // Use Laravel response for SSE
            $response = response()->stream(function () use ($adapter, $symbol, $timeframe) {
                // Send initial connection message
                echo "data: " . json_encode(['type' => 'connected', 'message' => 'Market data stream connected', 'symbol' => $symbol, 'timeframe' => $timeframe]) . "\n\n";
                flush();

                $updateCount = 0;
                $lastData = null;

                try {
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
                                
                                // Always send on first iteration or if data changed
                                if ($updateCount === 0 || json_encode($data) !== json_encode($lastData)) {
                                    $dataCount = is_array($data) ? count($data) : 0;
                                    
                                    echo "data: " . json_encode([
                                        'type' => $dataCount === 0 ? 'empty' : 'update',
                                        'symbol' => $symbol,
                                        'timeframe' => $timeframe,
                                        'data' => $data,
                                        'count' => $dataCount,
                                        'message' => $dataCount === 0 
                                            ? 'No market data available for this symbol/timeframe' 
                                            : "Fetched {$dataCount} candle(s)",
                                        'timestamp' => now()->toIso8601String(),
                                    ]) . "\n\n";
                                    flush();
                                    $lastData = $data;
                                }
                            } else {
                                // Send error if method doesn't exist
                                if ($updateCount === 0) {
                                    echo "data: " . json_encode([
                                        'type' => 'error',
                                        'message' => 'fetchOHLCV method not available for this adapter',
                                        'timestamp' => now()->toIso8601String(),
                                    ]) . "\n\n";
                                    flush();
                                }
                            }
                        } catch (\Throwable $e) {
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
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);

            set_time_limit(0);
            ignore_user_abort(false);

            return $response;
        } catch (\Exception $e) {
            Log::error('Stream market data error', [
                'connection_id' => $exchangeConnection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to stream market data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream positions (SSE)
     */
    public function streamPositions(ExchangeConnection $exchangeConnection)
    {
        try {
            if ($exchangeConnection->provider !== 'metaapi') {
                return response('Position streaming only available for MetaApi connections', 400);
            }

            $response = response()->stream(function () use ($exchangeConnection) {
                // Disable output buffering
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }

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
                                
                                // Always send update (even if empty) on first iteration or if data changed
                                if ($updateCount === 0 || json_encode($data) !== json_encode($lastData)) {
                                    $positionCount = is_array($data) ? count($data) : 0;
                                    
                                    echo "data: " . json_encode([
                                        'type' => $positionCount === 0 ? 'empty' : 'update',
                                        'positions' => $data,
                                        'count' => $positionCount,
                                        'message' => $positionCount === 0 
                                            ? 'No pending positions found. This shows open positions only.' 
                                            : "Found {$positionCount} pending position(s)",
                                        'timestamp' => now()->toIso8601String(),
                                    ]) . "\n\n";
                                    flush();
                                    $lastData = $data;
                                }
                            } else {
                                // Send error if method doesn't exist
                                if ($updateCount === 0) {
                                    echo "data: " . json_encode([
                                        'type' => 'error',
                                        'message' => 'fetchPositions method not available for this adapter',
                                        'timestamp' => now()->toIso8601String(),
                                    ]) . "\n\n";
                                    flush();
                                }
                            }
                        } catch (\Throwable $e) {
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
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);

            set_time_limit(0);
            ignore_user_abort(false);

            return $response;
        } catch (\Exception $e) {
            Log::error('Stream positions error', [
                'connection_id' => $exchangeConnection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to stream positions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream orders (SSE)
     */
    public function streamOrders(ExchangeConnection $exchangeConnection)
    {
        try {
            if ($exchangeConnection->provider !== 'metaapi') {
                return response('Order streaming only available for MetaApi connections', 400);
            }

            $response = response()->stream(function () use ($exchangeConnection) {
                // Disable output buffering
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }

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
                            // Use fetchOrderHistory for order history stream (not pending orders)
                            if (method_exists($adapter, 'fetchOrderHistory')) {
                                $data = $adapter->fetchOrderHistory(100); // Get last 100 orders
                                
                                // Always send update (even if empty) on first iteration or if data changed
                                if ($updateCount === 0 || json_encode($data) !== json_encode($lastData)) {
                                    $orderCount = is_array($data) ? count($data) : 0;
                                    
                                    echo "data: " . json_encode([
                                        'type' => $orderCount === 0 ? 'empty' : 'update',
                                        'orders' => $data,
                                        'count' => $orderCount,
                                        'message' => $orderCount === 0 
                                            ? 'No order history found. This shows filled, cancelled, and expired orders.' 
                                            : "Found {$orderCount} order(s) in history",
                                        'timestamp' => now()->toIso8601String(),
                                    ]) . "\n\n";
                                    flush();
                                    $lastData = $data;
                                }
                            } elseif (method_exists($adapter, 'fetchOrders')) {
                                // Fallback to fetchOrders if fetchOrderHistory doesn't exist
                                $data = $adapter->fetchOrders();
                                
                                // Always send update (even if empty) on first iteration or if data changed
                                if ($updateCount === 0 || json_encode($data) !== json_encode($lastData)) {
                                    $orderCount = is_array($data) ? count($data) : 0;
                                    
                                    echo "data: " . json_encode([
                                        'type' => $orderCount === 0 ? 'empty' : 'update',
                                        'orders' => $data,
                                        'count' => $orderCount,
                                        'message' => $orderCount === 0 
                                            ? 'No orders found.' 
                                            : "Found {$orderCount} order(s)",
                                        'timestamp' => now()->toIso8601String(),
                                    ]) . "\n\n";
                                    flush();
                                    $lastData = $data;
                                }
                            } else {
                                // Send error if method doesn't exist
                                if ($updateCount === 0) {
                                    echo "data: " . json_encode([
                                        'type' => 'error',
                                        'message' => 'fetchOrderHistory or fetchOrders method not available for this adapter',
                                        'timestamp' => now()->toIso8601String(),
                                    ]) . "\n\n";
                                    flush();
                                }
                            }
                        } catch (\Throwable $e) {
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'message' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                                'timestamp' => now()->toIso8601String(),
                            ]) . "\n\n";
                            flush();
                            
                            // Log error for debugging
                            Log::error('Stream orders: Error fetching orders', [
                                'connection_id' => $exchangeConnection->id,
                                'error' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                            ]);
                        }

                        $updateCount++;
                        sleep(3); // Update every 3 seconds
                    }
                } catch (\Exception $e) {
                    echo "data: " . json_encode(['type' => 'error', 'message' => $e->getMessage()]) . "\n\n";
                    flush();
                }
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);

            set_time_limit(0);
            ignore_user_abort(false);

            return $response;
        } catch (\Exception $e) {
            Log::error('Stream orders error', [
                'connection_id' => $exchangeConnection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to stream orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream balance (SSE)
     */
    public function streamBalance(ExchangeConnection $exchangeConnection)
    {
        try {
            $response = response()->stream(function () use ($exchangeConnection) {
                // Disable output buffering
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }

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
                            
                            // Always send on first iteration or if data changed
                            if ($data && ($updateCount === 0 || json_encode($data) !== json_encode($lastData))) {
                                echo "data: " . json_encode([
                                    'type' => 'update',
                                    'balance' => $data,
                                    'message' => 'Balance updated',
                                    'timestamp' => now()->toIso8601String(),
                                ]) . "\n\n";
                                flush();
                                $lastData = $data;
                            } elseif (!$data && $updateCount === 0) {
                                // Send error if no data on first iteration
                                echo "data: " . json_encode([
                                    'type' => 'error',
                                    'message' => 'Unable to fetch balance data',
                                    'timestamp' => now()->toIso8601String(),
                                ]) . "\n\n";
                                flush();
                            }
                        } catch (\Throwable $e) {
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
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);

            set_time_limit(0);
            ignore_user_abort(false);

            return $response;
        } catch (\Exception $e) {
            Log::error('Stream balance error', [
                'connection_id' => $exchangeConnection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to stream balance: ' . $e->getMessage()
            ], 500);
        }
    }
}

