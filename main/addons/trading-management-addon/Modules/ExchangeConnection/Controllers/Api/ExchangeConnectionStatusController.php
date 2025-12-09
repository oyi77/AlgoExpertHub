<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Api;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * ExchangeConnectionStatusController
 * 
 * API endpoints for real-time connection status checks
 */
class ExchangeConnectionStatusController extends Controller
{
    protected ExchangeConnectionService $service;

    public function __construct(ExchangeConnectionService $service)
    {
        $this->service = $service;
    }

    /**
     * Get connection status
     * 
     * GET /api/exchange-connections/{id}/status
     */
    public function status(ExchangeConnection $connection): JsonResponse
    {
        $healthStatus = $connection->getHealthStatus();
        $isStabilized = $this->service->isStabilized($connection);

        return response()->json([
            'success' => true,
            'data' => [
                'connection_id' => $connection->id,
                'name' => $connection->name,
                'status' => $connection->status,
                'is_active' => $connection->is_active,
                'health' => $healthStatus,
                'is_stabilized' => $isStabilized,
                'last_tested_at' => $connection->last_tested_at?->toIso8601String(),
                'last_error' => $connection->last_error,
                'can_fetch_data' => $connection->canFetchData(),
                'can_execute_trades' => $connection->canExecuteTrades(),
                'can_copy_trade' => $connection->canCopyTrade(),
            ],
        ]);
    }

    /**
     * Test connection (trigger health check)
     * 
     * POST /api/exchange-connections/{id}/test
     */
    public function test(ExchangeConnection $connection): JsonResponse
    {
        $result = $this->service->testConnection($connection);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => array_merge($result['data'] ?? [], [
                'connection_id' => $connection->id,
                'status' => $connection->fresh()->status,
                'is_active' => $connection->fresh()->is_active,
                'last_tested_at' => $connection->fresh()->last_tested_at?->toIso8601String(),
            ]),
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Verify connection is stabilized
     * 
     * GET /api/exchange-connections/{id}/stabilized
     */
    public function stabilized(ExchangeConnection $connection): JsonResponse
    {
        $isStabilized = $this->service->isStabilized($connection);

        return response()->json([
            'success' => true,
            'data' => [
                'connection_id' => $connection->id,
                'is_stabilized' => $isStabilized,
                'status' => $connection->status,
                'is_active' => $connection->is_active,
                'last_tested_at' => $connection->last_tested_at?->toIso8601String(),
            ],
        ]);
    }
}
