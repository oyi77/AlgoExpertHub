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
        $validated = $request->validate([
            'connection_id' => 'required|exists:exchange_connections,id',
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
            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::findOrFail($validated['connection_id']);
            
            if (!$connection->canExecuteTrades()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection is not active or trade execution is not enabled'
                ], 400);
            }

            // Create execution log
            $log = ExecutionLog::create([
                'connection_id' => $connection->id,
                'signal_id' => null, // Manual trade, no signal
                'symbol' => $validated['symbol'],
                'direction' => strtolower($validated['direction']), // 'buy' or 'sell'
                'quantity' => $validated['lot_size'],
                'entry_price' => $validated['entry_price'],
                'sl_price' => $validated['sl_price'],
                'tp_price' => $validated['tp_price'],
                'execution_type' => $validated['order_type'] ?? 'market',
                'status' => 'pending',
            ]);

            $orderId = 'MANUAL_' . time() . '_' . rand(1000, 9999);
            
            $log->update([
                'status' => 'executed',
                'order_id' => $orderId,
                'executed_at' => now(),
            ]);

            // Create position
            ExecutionPosition::create([
                'signal_id' => null,
                'connection_id' => $connection->id,
                'execution_log_id' => $log->id,
                'order_id' => $orderId,
                'symbol' => $validated['symbol'],
                'direction' => strtolower($validated['direction']),
                'quantity' => $validated['lot_size'],
                'entry_price' => $validated['entry_price'] ?? 0,
                'current_price' => $validated['entry_price'] ?? 0,
                'sl_price' => $validated['sl_price'],
                'tp_price' => $validated['tp_price'],
                'status' => 'open',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trade executed successfully',
                'data' => [
                    'order_id' => $orderId,
                    'symbol' => $validated['symbol'],
                    'direction' => $validated['direction'],
                    'lot_size' => $validated['lot_size'],
                    'entry_price' => $validated['entry_price'] ?? 'Market',
                    'status' => 'SUCCESS',
                ]
            ]);
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
}
