<?php

namespace Addons\TradingManagement\Modules\Execution\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionAnalytic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Trading Operations Controller
 * 
 * Handles executions, positions, and analytics views
 */
class TradingOperationsController extends Controller
{
    /**
     * Executions log
     */
    public function executions(Request $request)
    {
        $title = 'Execution Log';
        $query = ExecutionLog::with(['connection', 'signal']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('connection_id')) {
            $query->where('connection_id', $request->connection_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $executions = $query->orderBy('created_at', 'desc')->paginate(50);

        // Stats
        $stats = [
            'total' => ExecutionLog::count(),
            'success' => ExecutionLog::where('status', 'SUCCESS')->count(),
            'failed' => ExecutionLog::where('status', 'FAILED')->count(),
            'pending' => ExecutionLog::where('status', 'PENDING')->count(),
        ];

        return view('trading-management::backend.trading-management.operations.executions', compact('title', 'executions', 'stats'));
    }

    /**
     * Open positions
     */
    public function openPositions(Request $request)
    {
        $title = 'Open Positions';
        $query = ExecutionPosition::with(['connection', 'signal', 'preset'])
            ->where('status', 'open');

        // Filters
        if ($request->filled('connection_id')) {
            $query->where('connection_id', $request->connection_id);
        }

        if ($request->filled('symbol')) {
            $query->where('symbol', 'like', '%' . $request->symbol . '%');
        }

        $positions = $query->orderBy('created_at', 'desc')->paginate(50);

        // Stats
        $stats = [
            'total_open' => ExecutionPosition::where('status', 'open')->count(),
            'total_pnl' => ExecutionPosition::where('status', 'open')->sum('pnl'),
            'avg_pnl' => ExecutionPosition::where('status', 'open')->avg('pnl'),
        ];

        return view('trading-management::backend.trading-management.operations.positions-open', compact('title', 'positions', 'stats'));
    }

    /**
     * Get position updates (API endpoint for real-time updates)
     */
    public function getPositionUpdates(Request $request)
    {
        $positionIds = $request->input('position_ids', []);
        
        if (empty($positionIds)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $positions = ExecutionPosition::whereIn('id', $positionIds)
            ->where('status', 'open')
            ->get();

        $updates = $positions->map(function ($position) {
            return [
                'id' => $position->id,
                'current_price' => $position->current_price,
                'pnl' => $position->pnl,
                'pnl_percentage' => $position->pnl_percentage,
                'last_price_update_at' => $position->last_price_update_at ? $position->last_price_update_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $updates
        ]);
    }

    /**
     * Closed positions
     */
    public function closedPositions(Request $request)
    {
        $title = 'Closed Positions';
        $query = ExecutionPosition::with(['connection', 'signal', 'preset'])
            ->where('status', 'closed');

        // Filters
        if ($request->filled('connection_id')) {
            $query->where('connection_id', $request->connection_id);
        }

        if ($request->filled('symbol')) {
            $query->where('symbol', 'like', '%' . $request->symbol . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('closed_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('closed_at', '<=', $request->date_to);
        }

        $positions = $query->orderBy('closed_at', 'desc')->paginate(50);

        // Stats
        $stats = [
            'total_closed' => ExecutionPosition::where('status', 'closed')->count(),
            'total_profit' => ExecutionPosition::where('status', 'closed')->where('pnl', '>', 0)->sum('pnl'),
            'total_loss' => ExecutionPosition::where('status', 'closed')->where('pnl', '<', 0)->sum('pnl'),
            'win_rate' => $this->calculateWinRate(),
        ];

        return view('trading-management::backend.trading-management.operations.positions-closed', compact('title', 'positions', 'stats'));
    }

    /**
     * Analytics dashboard
     */
    public function analytics(Request $request)
    {
        // Get date range (default last 30 days)
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        // Performance metrics
        $metrics = [
            'total_trades' => ExecutionPosition::where('status', 'closed')
                ->whereBetween('closed_at', [$dateFrom, $dateTo])
                ->count(),
            
            'winning_trades' => ExecutionPosition::where('status', 'closed')
                ->where('pnl', '>', 0)
                ->whereBetween('closed_at', [$dateFrom, $dateTo])
                ->count(),
            
            'losing_trades' => ExecutionPosition::where('status', 'closed')
                ->where('pnl', '<', 0)
                ->whereBetween('closed_at', [$dateFrom, $dateTo])
                ->count(),
            
            'total_pnl' => ExecutionPosition::where('status', 'closed')
                ->whereBetween('closed_at', [$dateFrom, $dateTo])
                ->sum('pnl'),
            
            'avg_win' => ExecutionPosition::where('status', 'closed')
                ->where('pnl', '>', 0)
                ->whereBetween('closed_at', [$dateFrom, $dateTo])
                ->avg('pnl'),
            
            'avg_loss' => ExecutionPosition::where('status', 'closed')
                ->where('pnl', '<', 0)
                ->whereBetween('closed_at', [$dateFrom, $dateTo])
                ->avg('pnl'),
        ];

        // Calculate derived metrics
        $metrics['win_rate'] = $metrics['total_trades'] > 0 
            ? ($metrics['winning_trades'] / $metrics['total_trades']) * 100 
            : 0;

        $metrics['profit_factor'] = abs($metrics['avg_loss']) > 0 
            ? abs($metrics['avg_win'] / $metrics['avg_loss']) 
            : 0;

        // Daily PnL chart data
        $dailyPnl = ExecutionPosition::select(
                DB::raw('DATE(closed_at) as date'),
                DB::raw('SUM(pnl) as pnl'),
                DB::raw('COUNT(*) as trades')
            )
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top performing connections
        $topConnections = ExecutionPosition::select(
                'connection_id',
                DB::raw('COUNT(*) as total_trades'),
                DB::raw('SUM(pnl) as total_pnl')
            )
            ->with('connection')
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$dateFrom, $dateTo])
            ->groupBy('connection_id')
            ->orderBy('total_pnl', 'desc')
            ->limit(10)
            ->get();

        $title = 'Trading Analytics';

        return view('trading-management::backend.trading-management.operations.analytics', compact(
            'title',
            'metrics',
            'dailyPnl',
            'topConnections',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Manual trade execution
     */
    public function manualTrade(Request $request)
    {
        // Force JSON response for this endpoint (always AJAX)
        $isAjax = $request->ajax() 
            || $request->expectsJson() 
            || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest'
            || $request->header('Accept') === 'application/json'
            || str_contains($request->header('Accept', ''), 'application/json');
        
        // Merge JSON body into request if present (for fetch/axios requests)
        if ($request->isJson() && $request->json()->all()) {
            $request->merge($request->json()->all());
        }
        
        // Validate request - always return JSON for this endpoint
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Always return JSON for this endpoint
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::findOrFail($validated['connection_id']);
            
            if (!$connection->canExecuteTrades()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection is not active or trade execution is not enabled'
                ], 400);
            }

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

            // Get adapter and execute trade
            $adapter = $this->getAdapter($connection);

            if (!$adapter || !method_exists($adapter, 'placeOrder')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trade execution not supported for this connection type'
                ], 400);
            }

            // Create execution log
            $log = ExecutionLog::create([
                'connection_id' => $connection->id,
                'signal_id' => null, // Manual trade, no signal
                'symbol' => $validated['symbol'],
                'direction' => $direction,
                'quantity' => $validated['lot_size'],
                'entry_price' => $validated['entry_price'],
                'sl_price' => $validated['sl_price'],
                'tp_price' => $validated['tp_price'],
                'execution_type' => $orderType,
                'status' => 'pending',
            ]);

            try {
                // Execute trade via adapter
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

                if (!isset($result['success']) || !$result['success']) {
                    throw new \Exception($result['message'] ?? 'Trade execution failed');
                }

                $orderId = $result['orderId'] ?? $result['positionId'] ?? 'ORDER_' . time();
                $positionId = $result['positionId'] ?? null;
                
                // Determine entry price
                $entryPrice = null;
                if ($orderType === 'limit' && !empty($validated['entry_price'])) {
                    // For limit orders, use the provided entry price
                    $entryPrice = (float) $validated['entry_price'];
                } else {
                    // For market orders, try to get execution price from result or fetch position
                    // Check if result data contains openPrice
                    if (isset($result['data']['openPrice']) && $result['data']['openPrice'] > 0) {
                        $entryPrice = (float) $result['data']['openPrice'];
                    } elseif ($positionId && method_exists($adapter, 'fetchPositions')) {
                        // Fetch positions to get the openPrice for this position
                        try {
                            $positions = $adapter->fetchPositions();
                            foreach ($positions as $pos) {
                                if (($pos['id'] ?? null) == $positionId || 
                                    ($pos['symbol'] ?? null) === $validated['symbol']) {
                                    $entryPrice = (float) ($pos['openPrice'] ?? 0);
                                    break;
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Failed to fetch position for entry price', [
                                'position_id' => $positionId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // Fallback: try to get current price using fetchCurrentPrice or fetchPositions
                    if (!$entryPrice || $entryPrice <= 0) {
                        try {
                            // Try fetchCurrentPrice first (for CCXT adapters)
                            if (method_exists($adapter, 'fetchCurrentPrice')) {
                                $priceResult = $adapter->fetchCurrentPrice($validated['symbol']);
                                if (isset($priceResult['success']) && $priceResult['success']) {
                                    $priceData = $priceResult['data'] ?? [];
                                    // Use 'last' price, or 'bid' for sell, 'ask' for buy
                                    if (isset($priceData['last']) && $priceData['last'] > 0) {
                                        $entryPrice = (float) $priceData['last'];
                                    } elseif ($direction === 'buy' && isset($priceData['ask']) && $priceData['ask'] > 0) {
                                        $entryPrice = (float) $priceData['ask'];
                                    } elseif ($direction === 'sell' && isset($priceData['bid']) && $priceData['bid'] > 0) {
                                        $entryPrice = (float) $priceData['bid'];
                                    }
                                }
                            }
                            // If still no price, try fetching positions again (might have been created by now)
                            if ((!$entryPrice || $entryPrice <= 0) && method_exists($adapter, 'fetchPositions')) {
                                $positions = $adapter->fetchPositions();
                                // Find the most recent position for this symbol
                                foreach ($positions as $pos) {
                                    if (($pos['symbol'] ?? null) === $validated['symbol']) {
                                        $posOpenPrice = (float) ($pos['openPrice'] ?? 0);
                                        if ($posOpenPrice > 0) {
                                            $entryPrice = $posOpenPrice;
                                            break;
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Failed to get current price for entry', [
                                'symbol' => $validated['symbol'],
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                
                // If still no entry price, log warning but continue (will be updated by monitoring job)
                if (!$entryPrice || $entryPrice <= 0) {
                    \Log::warning('Could not determine entry price for manual trade', [
                        'order_id' => $orderId,
                        'position_id' => $positionId,
                        'symbol' => $validated['symbol'],
                        'order_type' => $orderType
                    ]);
                    $entryPrice = 0; // Will be updated by monitoring job
                }

                // Update execution log with entry price
                $log->update([
                    'status' => 'executed',
                    'order_id' => $orderId,
                    'entry_price' => $entryPrice,
                    'executed_at' => now(),
                ]);

                // Create position if we have a position ID or if it's a market order (which creates position immediately)
                if ($positionId || $orderType === 'market') {
                    // Check if signal_id column is nullable before setting it to null
                    $positionData = [
                        'connection_id' => $connection->id,
                        'execution_log_id' => $log->id,
                        'order_id' => $orderId,
                        'symbol' => $validated['symbol'],
                        'direction' => $direction,
                        'quantity' => $validated['lot_size'],
                        'entry_price' => $entryPrice,
                        'current_price' => $entryPrice, // Set current price to entry price initially
                        'sl_price' => $validated['sl_price'],
                        'tp_price' => $validated['tp_price'],
                        'status' => 'open',
                    ];
                    
                    // Only set signal_id to null if column is nullable
                    $prefix = \Illuminate\Support\Facades\Schema::getConnection()->getTablePrefix();
                    $tableName = $prefix . 'execution_positions';
                    try {
                        $columnInfo = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM `{$tableName}` WHERE Field = 'signal_id'");
                        if (!empty($columnInfo) && isset($columnInfo[0]->Null) && $columnInfo[0]->Null === 'YES') {
                            $positionData['signal_id'] = null; // Manual trade, no signal
                        }
                        // If NOT NULL, we need migration - but don't fail, let it use a default or skip
                    } catch (\Exception $e) {
                        \Log::warning('ExecutionPosition: Could not check signal_id column nullability', [
                            'error' => $e->getMessage()
                        ]);
                        // Try to set null anyway (migration might have run but check failed)
                        $positionData['signal_id'] = null;
                    }
                    
                    ExecutionPosition::create($positionData);
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
                $log->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Trade execution failed: ' . $e->getMessage()
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Execution failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Calculate win rate
     */
    protected function calculateWinRate()
    {
        $total = ExecutionPosition::where('status', 'closed')->count();
        $wins = ExecutionPosition::where('status', 'closed')->where('pnl', '>', 0)->count();

        return $total > 0 ? ($wins / $total) * 100 : 0;
    }

    /**
     * Get adapter for connection
     */
    protected function getAdapter($connection)
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
}
