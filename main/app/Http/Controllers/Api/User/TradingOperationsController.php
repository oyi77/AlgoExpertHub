<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\ApiTradingOperationsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TradingOperationsController extends Controller
{
    protected ApiTradingOperationsService $service;

    public function __construct(ApiTradingOperationsService $service)
    {
        $this->service = $service;
    }

    /**
     * Get execution logs
     */
    public function executionLogs(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $filters = $request->only(['status', 'connection_id']);
            $perPage = (int) $request->get('per_page', 20);
            $logs = $this->service->getExecutionLogs($userId, $filters, $perPage);

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
            $userId = auth()->id();

            // Verify connection belongs to user
            if (!$this->service->verifyConnection($validated['connection_id'], $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Execution connection not found'
                ], 404);
            }

            // Create execution log
            $logId = $this->service->createExecutionLog($userId, $validated);

            // Dispatch job to execute trade (if Execution Engine addon is active)
            if (\App\Support\AddonRegistry::active('trading-management-addon')) {
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
            $stats = $this->service->getStatistics($userId);

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

