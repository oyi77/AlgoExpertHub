<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TradingOperationsController extends Controller
{
    /**
     * Get execution logs
     */
    public function executionLogs(Request $request): JsonResponse
    {
        try {
            $query = \DB::table('execution_logs')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('connection_id')) {
                $query->where('connection_id', $request->connection_id);
            }

            $logs = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch execution logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute manual trade
     */
    public function manualTrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signal_id' => 'nullable|exists:signals,id',
            'connection_id' => 'required|exists:execution_connections,id',
            'symbol' => 'required|string',
            'direction' => 'required|in:buy,sell,long,short',
            'amount' => 'required|numeric|min:0.0001',
            'price' => 'nullable|numeric',
            'stop_loss' => 'nullable|numeric',
            'take_profit' => 'nullable|numeric',
        ]);

        try {
            // Verify connection belongs to user
            $connection = \DB::table('execution_connections')
                ->where('id', $validated['connection_id'])
                ->where('user_id', auth()->id())
                ->first();

            if (!$connection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Execution connection not found'
                ], 404);
            }

            // Create execution log
            $logId = \DB::table('execution_logs')->insertGetId([
                'user_id' => auth()->id(),
                'connection_id' => $validated['connection_id'],
                'signal_id' => $validated['signal_id'] ?? null,
                'symbol' => $validated['symbol'],
                'direction' => $validated['direction'],
                'amount' => $validated['amount'],
                'price' => $validated['price'],
                'stop_loss' => $validated['stop_loss'],
                'take_profit' => $validated['take_profit'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Dispatch job to execute trade (if Execution Engine addon is active)
            if (\App\Support\AddonRegistry::active('trading-execution-engine-addon')) {
                // Dispatch execution job
                \Log::info('Manual trade execution requested', ['log_id' => $logId]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Trade execution initiated',
                'data' => ['log_id' => $logId]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute manual trade: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trading statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();

            $stats = [
                'total_trades' => \DB::table('execution_logs')->where('user_id', $userId)->count(),
                'open_trades' => \DB::table('execution_logs')->where('user_id', $userId)->where('status', 'open')->count(),
                'closed_trades' => \DB::table('execution_logs')->where('user_id', $userId)->where('status', 'closed')->count(),
                'win_rate' => 0,
                'total_profit' => 0,
            ];

            // Calculate win rate and profit if execution_positions table exists
            if (\Schema::hasTable('execution_positions')) {
                $positions = \DB::table('execution_positions')
                    ->where('user_id', $userId)
                    ->where('status', 'closed')
                    ->get();

                $winCount = $positions->where('profit', '>', 0)->count();
                $stats['win_rate'] = $positions->count() > 0 
                    ? round(($winCount / $positions->count()) * 100, 2) 
                    : 0;
                $stats['total_profit'] = $positions->sum('profit');
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}

