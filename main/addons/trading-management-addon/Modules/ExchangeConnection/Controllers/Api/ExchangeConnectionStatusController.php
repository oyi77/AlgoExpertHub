<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Api;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Trading Management
 * Exchange connection status and management endpoints
 */
class ExchangeConnectionStatusController extends Controller
{
    protected ExchangeConnectionService $service;

    public function __construct(ExchangeConnectionService $service)
    {
        $this->service = $service;
    }

    /**
     * Get Connection Status
     * 
     * Get real-time status and health information for an exchange connection
     * 
     * @param ExchangeConnection $connection
     * @return JsonResponse
     * @authenticated
     * @urlParam connection integer required Exchange connection ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "connection_id": 1,
     *     "name": "Binance Main",
     *     "status": "connected",
     *     "is_active": true,
     *     "health": "healthy",
     *     "is_stabilized": true,
     *     "last_tested_at": "2023-01-01T00:00:00.000000Z",
     *     "last_error": null,
     *     "can_fetch_data": true,
     *     "can_execute_trades": true,
     *     "can_copy_trade": true
     *   }
     * }
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
     * Test Connection
     * 
     * Trigger a health check test for an exchange connection
     * 
     * @param ExchangeConnection $connection
     * @return JsonResponse
     * @authenticated
     * @urlParam connection integer required Exchange connection ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Connection test successful",
     *   "data": {
     *     "connection_id": 1,
     *     "status": "connected",
     *     "is_active": true,
     *     "last_tested_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Connection test failed"
     * }
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
     * Check Connection Stabilized
     * 
     * Verify if connection is stabilized and ready for trading
     * 
     * @param ExchangeConnection $connection
     * @return JsonResponse
     * @authenticated
     * @urlParam connection integer required Exchange connection ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "connection_id": 1,
     *     "is_stabilized": true,
     *     "status": "connected",
     *     "is_active": true,
     *     "last_tested_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
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
