<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExecutionLogController extends Controller
{
    /**
     * Display Trading Operations page (replicated from admin - all tabs)
     */
    public function index(Request $request)
    {
        $data['title'] = __('Trading Operations');
        
        // Check if addon is enabled
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon');

        if (!$data['tradingManagementEnabled']) {
            return view(Helper::themeView('user.trading.execution-log', $data);
        }

        try {
            // Get user's connection IDs for filtering
            $userConnectionIds = $this->getUserConnectionIds();
            
            // Calculate stats (same as admin, but user-scoped)
            $ExecutionPosition = class_exists(\Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class)
                ? \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class
                : null;
            
            $ExecutionLog = class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class)
                ? \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class
                : null;
            
            $ExecutionConnection = class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)
                ? \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class
                : null;

            // Stats (user-scoped)
            $stats = [
                'active_connections' => 0,
                'open_positions' => 0,
                'today_executions' => 0,
                'today_pnl' => 0,
            ];

            if ($ExecutionConnection && !empty($userConnectionIds)) {
                $stats['active_connections'] = $ExecutionConnection::whereIn('id', $userConnectionIds)
                    ->where('is_active', 1)
                    ->count();
            }

            if ($ExecutionPosition && !empty($userConnectionIds)) {
                $stats['open_positions'] = $ExecutionPosition::whereIn('connection_id', $userConnectionIds)
                    ->where('status', 'open')
                    ->count();
                
                $stats['today_pnl'] = $ExecutionPosition::whereIn('connection_id', $userConnectionIds)
                    ->where('status', 'closed')
                    ->whereDate('closed_at', today())
                    ->sum('pnl') ?? 0;
            }

            if ($ExecutionLog && !empty($userConnectionIds)) {
                $stats['today_executions'] = $ExecutionLog::whereIn('connection_id', $userConnectionIds)
                    ->whereDate('created_at', today())
                    ->count();
            }

            $data['stats'] = $stats;

            // Get active connections for manual trade tab
            $data['activeConnections'] = collect();
            if ($ExecutionConnection && !empty($userConnectionIds)) {
                try {
                    $hasTradeExecutionColumn = \Illuminate\Support\Facades\Schema::hasColumn('execution_connections', 'trade_execution_enabled');
                    
                    $query = $ExecutionConnection::whereIn('id', $userConnectionIds)
                        ->where('is_active', 1);
                    
                    if ($hasTradeExecutionColumn) {
                        $query->where('trade_execution_enabled', true);
                    }
                    
                    $data['activeConnections'] = $query->get();
                } catch (\Exception $e) {
                    \Log::warning('ExecutionLog: Error loading active connections', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Recent executions for Execution Log tab
            $data['recentExecutions'] = collect();
            if ($ExecutionLog && !empty($userConnectionIds)) {
                try {
                    $data['recentExecutions'] = $ExecutionLog::whereIn('connection_id', $userConnectionIds)
                        ->with('connection')
                        ->orderBy('created_at', 'desc')
                        ->limit(20)
                        ->get();
                } catch (\Exception $e) {
                    \Log::warning('ExecutionLog: Error loading recent executions', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Open positions for Open Positions tab
            $data['openPositions'] = collect();
            if ($ExecutionPosition && !empty($userConnectionIds)) {
                try {
                    $data['openPositions'] = $ExecutionPosition::whereIn('connection_id', $userConnectionIds)
                        ->where('status', 'open')
                        ->with('connection')
                        ->orderBy('created_at', 'desc')
                        ->limit(20)
                        ->get();
                } catch (\Exception $e) {
                    \Log::warning('ExecutionLog: Error loading open positions', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Closed positions for Closed Positions tab
            $data['closedPositions'] = collect();
            if ($ExecutionPosition && !empty($userConnectionIds)) {
                try {
                    $data['closedPositions'] = $ExecutionPosition::whereIn('connection_id', $userConnectionIds)
                        ->where('status', 'closed')
                        ->with('connection')
                        ->orderBy('closed_at', 'desc')
                        ->limit(20)
                        ->get();
                } catch (\Exception $e) {
                    \Log::warning('ExecutionLog: Error loading closed positions', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('ExecutionLog: Error loading data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $data['stats'] = [
                'active_connections' => 0,
                'open_positions' => 0,
                'today_executions' => 0,
                'today_pnl' => 0,
            ];
            $data['activeConnections'] = collect();
            $data['recentExecutions'] = collect();
            $data['openPositions'] = collect();
            $data['closedPositions'] = collect();
        }

        return view(Helper::themeView('user.trading.execution-log', $data);
    }

    /**
     * Get user's connection IDs (ExecutionConnection)
     */
    protected function getUserConnectionIds(): array
    {
        $userConnectionIds = [];
        
        if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
            try {
                $userConnectionIds = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('user_id', Auth::id())
                    ->where('is_admin_owned', false)
                    ->pluck('id')
                    ->toArray();
            } catch (\Exception $e) {
                \Log::warning('ExecutionLog: Error loading user execution connections', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $userConnectionIds;
    }

    /**
     * Manual trade execution (same as admin)
     */
    public function manualTrade(Request $request)
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:execution_connections,id',
            'symbol' => 'required|string',
            'direction' => 'required|in:BUY,SELL,LONG,SHORT',
            'lot_size' => 'required|numeric|min:0.01',
            'order_type' => 'required|in:market,limit',
            'entry_price' => 'nullable|numeric',
            'sl_price' => 'nullable|numeric',
            'tp_price' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        try {
            // Verify connection belongs to user
            $connection = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('id', $validated['connection_id'])
                ->where('user_id', Auth::id())
                ->where('is_admin_owned', false)
                ->firstOrFail();
            
            // Check if connection can execute trades (same as admin)
            if (method_exists($connection, 'canExecuteTrades')) {
                if (!$connection->canExecuteTrades()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Connection is not active or trade execution is not enabled'
                    ], 400);
                }
            } else {
                // Fallback for legacy ExecutionConnection
                if (!$connection->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Connection is not active'
                    ], 400);
                }
            }

            // Use the same logic as admin TradingOperationsController::manualTrade()
            // Map direction: BUY/LONG -> buy, SELL/SHORT -> sell
            $direction = strtolower($validated['direction']);
            if (in_array($direction, ['long', 'short'])) {
                $direction = $direction === 'long' ? 'buy' : 'sell';
            }

            // Validate limit order requirements
            $orderType = $validated['order_type'] ?? 'market';
            if ($orderType === 'limit' && empty($validated['entry_price'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entry price is required for limit orders'
                ], 400);
            }

            // Create execution log
            $ExecutionLog = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class;
            
            // Check if signal_id column allows null (for manual trades)
            $logData = [
                'connection_id' => $connection->id,
                'symbol' => $validated['symbol'],
                'direction' => $direction,
                'quantity' => $validated['lot_size'],
                'entry_price' => $validated['entry_price'],
                'sl_price' => $validated['sl_price'],
                'tp_price' => $validated['tp_price'],
                'execution_type' => $orderType,
                'status' => 'pending', // Use lowercase as per enum definition
            ];
            
            // Check if signal_id column allows null
            try {
                // Get table prefix and use prefixed table name
                $prefix = Schema::getConnection()->getTablePrefix();
                $tableName = $prefix . 'execution_logs';
                $columnInfo = DB::select("SHOW COLUMNS FROM `{$tableName}` WHERE Field = 'signal_id'");
                if (!empty($columnInfo) && isset($columnInfo[0]->Null) && $columnInfo[0]->Null === 'YES') {
                    $logData['signal_id'] = null; // Manual trade, no signal
                } else {
                    // Column is NOT NULL - we need migration
                    \Log::warning('ExecutionLog: signal_id column is NOT NULL, cannot create manual trade');
                    return response()->json([
                        'success' => false,
                        'message' => 'Manual trade execution requires database migration. Please run: php artisan migrate --path=addons/trading-management-addon/database/migrations/2025_12_10_100001_make_signal_id_nullable_in_execution_logs.php'
                    ], 400);
                }
            } catch (\Exception $e) {
                \Log::error('ExecutionLog: Error checking signal_id column: ' . $e->getMessage());
                // Try to set null anyway (migration might have run but check failed)
                $logData['signal_id'] = null;
            }
            
            $log = $ExecutionLog::create($logData);

            // Get adapter and execute trade (same as admin)
            $adapter = $this->getAdapter($connection);

            if (!$adapter || !method_exists($adapter, 'placeOrder')) {
                // Update log with failure
                $log->update([
                    'status' => 'failed',
                    'error_message' => 'Trade execution not supported for this connection type'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Trade execution not supported for this connection type'
                ], 400);
            }

            // For MetaAPI connections, check if account is connected before executing
            if ($adapter instanceof \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter) {
                try {
                    // Try to get account info to verify connection
                    if (method_exists($adapter, 'getAccountInfo')) {
                        $accountInfo = $adapter->getAccountInfo();
                        if (empty($accountInfo)) {
                            throw new \Exception('MetaAPI account is not connected. Please ensure the account is deployed and connected to broker in MetaAPI dashboard.');
                        }
                    }
                } catch (\Exception $e) {
                    // If account info check fails, log but continue (might be connection issue)
                    \Log::warning('ExecutionLog: Could not verify MetaAPI account connection', [
                        'connection_id' => $connection->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            try {
                // Log adapter type for debugging
                $adapterClass = get_class($adapter);
                \Log::info('ExecutionLog: Using adapter', [
                    'adapter' => $adapterClass,
                    'connection_id' => $connection->id,
                    'connection_type' => $connection->connection_type ?? $connection->type ?? 'unknown',
                    'provider' => $connection->provider ?? $connection->exchange_name ?? 'unknown',
                    'symbol' => $validated['symbol'],
                    'direction' => $direction,
                    'order_type' => $orderType,
                ]);

                // Execute trade via adapter
                $result = null;
                $executionSuccess = false;
                $orderId = null;
                $positionId = null;
                $responseData = null;
                
                try {
                    $result = $adapter->placeOrder(
                        $validated['symbol'],
                        $direction,
                        $validated['lot_size'],
                        $orderType,
                        $validated['entry_price'] ?? null,
                        $validated['sl_price'] ?? null,
                        $validated['tp_price'] ?? null,
                        $validated['notes'] ?? null
                    );

                    // Log full response for debugging
                    \Log::info('ExecutionLog: Trade execution response', [
                        'log_id' => $log->id,
                        'result' => $result,
                        'has_success' => isset($result['success']),
                        'success_value' => $result['success'] ?? null,
                        'result_type' => gettype($result),
                        'is_array' => is_array($result),
                        'result_keys' => is_array($result) ? array_keys($result) : [],
                    ]);

                    // If adapter returns success=false explicitly, throw exception
                    if (isset($result['success']) && $result['success'] === false) {
                        throw new \Exception($result['message'] ?? 'Trade execution failed');
                    }

                    // CRITICAL: If we got here without exception, trade execution was successful
                    // Even if result doesn't have success=true, if no exception = success
                    // This is important because MetaAPI might return success but without explicit success flag
                    $executionSuccess = true;
                    $responseData = $result;
                    
                    \Log::info('ExecutionLog: Trade execution marked as successful', [
                        'log_id' => $log->id,
                        'executionSuccess' => $executionSuccess,
                    ]);

                    // Extract order ID and position ID from response
                    // MetaAPI returns: numericTicket (order ID), or positionId, or both
                    $orderId = $result['orderId'] 
                        ?? $result['numericTicket'] 
                        ?? $result['positionId'] 
                        ?? ($result['data']['numericTicket'] ?? $result['data']['orderId'] ?? $result['data']['positionId'] ?? null);
                    
                    $positionId = $result['positionId'] 
                        ?? $result['data']['positionId'] 
                        ?? null;

                    // For market orders, if we don't have positionId, try to fetch from MetaAPI
                    if ($orderType === 'market' && !$positionId && $adapter instanceof \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter) {
                        try {
                            // Wait a bit for position to be created
                            sleep(1);
                            
                            // Fetch positions from MetaAPI to find our new position
                            $positions = $adapter->fetchPositions();
                            foreach ($positions as $pos) {
                                if ($pos['symbol'] === $validated['symbol'] && 
                                    strtolower($pos['type'] ?? '') === strtolower($direction === 'buy' ? 'POSITION_TYPE_BUY' : 'POSITION_TYPE_SELL')) {
                                    $positionId = $pos['id'] ?? null;
                                    if ($positionId) {
                                        \Log::info('ExecutionLog: Found position from MetaAPI', [
                                            'log_id' => $log->id,
                                            'position_id' => $positionId,
                                            'symbol' => $validated['symbol'],
                                        ]);
                                        break;
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning('ExecutionLog: Could not fetch positions from MetaAPI', [
                                'log_id' => $log->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                } catch (\Exception $e) {
                    // If exception occurs, check if trade might have succeeded anyway
                    // Sometimes MetaAPI returns error but trade actually executed
                    $errorMessage = $e->getMessage();
                    
                    // For certain errors, trade might still have executed
                    // We'll mark as failed but allow manual review
                    \Log::error('ExecutionLog: Trade execution exception', [
                        'log_id' => $log->id,
                        'error' => $errorMessage,
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    throw $e; // Re-throw to be caught by outer catch
                }

                // CRITICAL: If execution was successful, ALWAYS update status first
                // This ensures status is updated even if position creation fails
                if ($executionSuccess) {
                    // Update status FIRST - this is the most important step
                    try {
                        $updateData = [
                            'status' => 'executed',
                            'executed_at' => now(),
                        ];
                        
                        if ($orderId) {
                            $updateData['order_id'] = (string)$orderId;
                        }
                        
                        if ($responseData) {
                            $updateData['response_data'] = $responseData['data'] ?? $responseData;
                        }
                        
                        $log->update($updateData);

                        \Log::info('ExecutionLog: Status updated to executed', [
                            'log_id' => $log->id,
                            'order_id' => $orderId,
                            'status' => 'executed',
                        ]);
                    } catch (\Exception $e) {
                        // Log error but don't throw - we want to continue
                        \Log::error('ExecutionLog: CRITICAL - Failed to update status to executed', [
                            'log_id' => $log->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }

                    // For market orders, always try to create position
                    // For limit orders, only if we have positionId
                    $shouldCreatePosition = false;
                    if ($orderType === 'market') {
                        $shouldCreatePosition = true;
                    } elseif ($positionId) {
                        $shouldCreatePosition = true;
                    }

                    if ($shouldCreatePosition) {
                        try {
                            $ExecutionPosition = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class;
                            
                            // Get actual entry price from response if available
                            $actualEntryPrice = $result['data']['openPrice'] 
                                ?? $result['data']['price'] 
                                ?? $result['data']['entryPrice']
                                ?? $result['data']['currentPrice']
                                ?? $validated['entry_price'] 
                                ?? 0;

                            // Check if position already exists (avoid duplicates)
                            $existingPosition = $ExecutionPosition::where('execution_log_id', $log->id)
                                ->where('connection_id', $connection->id)
                                ->where('symbol', $validated['symbol'])
                                ->where('status', 'open')
                                ->first();

                            if (!$existingPosition) {
                                // Check if signal_id column is nullable before setting it to null
                                $positionData = [
                                    'connection_id' => $connection->id,
                                    'execution_log_id' => $log->id,
                                    'order_id' => $orderId ? (string)$orderId : null,
                                    'symbol' => $validated['symbol'],
                                    'direction' => $direction,
                                    'quantity' => $validated['lot_size'],
                                    'entry_price' => $actualEntryPrice > 0 ? $actualEntryPrice : 0,
                                    'current_price' => $actualEntryPrice > 0 ? $actualEntryPrice : 0,
                                    'sl_price' => $validated['sl_price'],
                                    'tp_price' => $validated['tp_price'],
                                    'status' => 'open',
                                ];
                                
                                // Only set signal_id to null if column is nullable
                                $prefix = Schema::getConnection()->getTablePrefix();
                                $tableName = $prefix . 'execution_positions';
                                try {
                                    $columnInfo = DB::select("SHOW COLUMNS FROM `{$tableName}` WHERE Field = 'signal_id'");
                                    if (!empty($columnInfo) && isset($columnInfo[0]->Null) && $columnInfo[0]->Null === 'YES') {
                                        $positionData['signal_id'] = null; // Manual trade, no signal
                                    }
                                    // If NOT NULL, skip signal_id (will use default or fail gracefully)
                                } catch (\Exception $e) {
                                    \Log::warning('ExecutionPosition: Could not check signal_id column nullability', [
                                        'error' => $e->getMessage()
                                    ]);
                                    // Try to set null anyway (migration might have run but check failed)
                                    $positionData['signal_id'] = null;
                                }
                                
                                $ExecutionPosition::create($positionData);

                                \Log::info('ExecutionLog: Position created successfully', [
                                    'log_id' => $log->id,
                                    'position_id' => $positionId,
                                    'order_id' => $orderId,
                                    'symbol' => $validated['symbol'],
                                ]);
                            } else {
                                \Log::info('ExecutionLog: Position already exists', [
                                    'log_id' => $log->id,
                                    'existing_position_id' => $existingPosition->id,
                                ]);
                            }
                        } catch (\Exception $e) {
                            // Log but don't fail - position might be created later by monitoring job
                            \Log::warning('ExecutionLog: Failed to create position', [
                                'log_id' => $log->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                        }
                    }
                }

                // Update connection last used timestamp
                $connection->update(['last_trade_execution_at' => now()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Trade executed successfully',
                    'data' => [
                        'order_id' => $orderId,
                        'position_id' => $positionId,
                        'symbol' => $validated['symbol'],
                        'direction' => strtoupper($direction),
                        'lot_size' => $validated['lot_size'],
                        'entry_price' => $validated['entry_price'] ?? 'Market',
                        'order_type' => $orderType,
                        'status' => 'SUCCESS',
                    ]
                ]);

            } catch (\Exception $e) {
                // Update log with failure
                $errorMessage = $e->getMessage();
                
                // Provide more helpful error messages for common MetaAPI errors
                if (strpos($errorMessage, 'not connected to broker') !== false || 
                    strpos($errorMessage, 'does not match the account region') !== false) {
                    $errorMessage .= '. Please ensure: 1) Account is deployed in MetaAPI dashboard, 2) Account is connected to broker, 3) Correct API URL is configured for your account region. Check https://app.metaapi.cloud/api-access/api-urls for valid URLs.';
                }
                
                // Check if trade might have succeeded despite exception
                // Sometimes MetaAPI returns error but trade actually executed
                // We'll mark as failed but log for manual review
                $log->update([
                    'status' => 'failed',
                    'error_message' => $errorMessage,
                ]);

                \Log::error('ExecutionLog: Manual trade execution error', [
                    'log_id' => $log->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'connection_id' => $connection->id,
                    'connection_type' => $connection->connection_type ?? $connection->type ?? 'unknown',
                    'provider' => $connection->provider ?? $connection->exchange_name ?? 'unknown',
                    'adapter' => $adapterClass ?? 'unknown',
                    'symbol' => $validated['symbol'] ?? null,
                    'direction' => $direction ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Trade execution failed: ' . $errorMessage
                ], 400);
            }

        } catch (\Exception $e) {
            \Log::error('ExecutionLog: Manual trade error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Trade execution failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get appropriate adapter based on connection type and provider
     * (Same logic as admin TradingOperationsController)
     * Handles both ExecutionConnection (legacy) and ExchangeConnection (new)
     */
    protected function getAdapter($connection)
    {
        // Determine connection type and provider
        // For ExecutionConnection (legacy): use type field and exchange_name
        // For ExchangeConnection (new): use connection_type and provider fields
        
        $connectionType = $connection->connection_type ?? null;
        $provider = $connection->provider ?? null;
        $type = $connection->type ?? null; // legacy: 'crypto' or 'fx'
        $exchangeName = $connection->exchange_name ?? null; // legacy
        
        // If connection_type exists (ExchangeConnection), use it
        if ($connectionType === 'CRYPTO_EXCHANGE') {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter(
                $connection->credentials,
                $provider ?? $exchangeName ?? 'binance'
            );
        }
        
        // For legacy ExecutionConnection, determine from type field
        if ($type === 'crypto' || (!$connectionType && $type === 'crypto')) {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter(
                $connection->credentials,
                $exchangeName ?? $provider ?? 'binance'
            );
        }
        
        // FX/Broker connections (MT4/MT5)
        // Check provider type
        if ($provider === 'mtapi_grpc' || 
            (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'mtapi_grpc')) {
            $credentials = $connection->credentials;
            $globalSettings = \App\Services\GlobalConfigurationService::get('mtapi_global_settings', []);
            
            if (!empty($globalSettings['base_url'])) {
                $credentials['base_url'] = $globalSettings['base_url'];
            }
            if (!empty($globalSettings['timeout'])) {
                $credentials['timeout'] = $globalSettings['timeout'];
            }
            
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter($credentials);
        } elseif ($provider === 'metaapi' || 
                  (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'metaapi')) {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter(
                $connection->credentials
            );
        } else {
            // Default: MTAPI REST adapter for FX brokers
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter(
                $connection->credentials
            );
        }
    }
}

