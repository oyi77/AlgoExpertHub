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
            return view(Helper::themeView('user.trading.execution-log'), $data);
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

        return view(Helper::themeView('user.trading.execution-log'), $data);
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
     * Close an execution position
     */
    public function closePosition(Request $request, $id)
    {
        try {
            $ExecutionPosition = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class;
            
            if (!class_exists($ExecutionPosition)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Position monitoring module not available'
                ], 503);
            }

            // Get user's connection IDs
            $userConnectionIds = $this->getUserConnectionIds();
            
            // Find position that belongs to user
            $position = $ExecutionPosition::whereIn('connection_id', $userConnectionIds)
                ->where('id', $id)
                ->where('status', 'open')
                ->firstOrFail();

            // Get connection
            $connection = $position->connection;
            if (!$connection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection not found for this position'
                ], 404);
            }

            // Get adapter
            $adapter = $this->getAdapter($connection);
            if (!$adapter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to get adapter for connection'
                ], 400);
            }

            // Close position on exchange
            $closeResult = null;
            if (method_exists($adapter, 'closePosition')) {
                try {
                    $closeResult = $adapter->closePosition($position->order_id);
                } catch (\Exception $e) {
                    \Log::warning('Failed to close position on exchange', [
                        'position_id' => $position->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue to close locally even if exchange close fails
                }
            }

            // Update position status
            $position->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_reason' => 'manual_close',
            ]);

            // Update PnL if method exists
            if (method_exists($position, 'updatePnL')) {
                $position->updatePnL($position->current_price);
            }

            return response()->json([
                'success' => true,
                'message' => 'Position closed successfully',
                'data' => [
                    'position_id' => $position->id,
                    'pnl' => $position->pnl ?? 0,
                    'closed_at' => $position->closed_at,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Position not found or already closed'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('ExecutionLog: Close position error', [
                'position_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to close position: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual trade execution (same as admin)
     */
    /**
     * Manual trade execution (same as admin)
     */
    public function manualTrade(\App\Http\Requests\Trading\ManualTradeRequest $request, \App\Actions\Trading\ExecuteManualTradeAction $action)
    {
        try {
            $result = $action->execute($request->user(), $request->validated());
            return response()->json($result);
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

