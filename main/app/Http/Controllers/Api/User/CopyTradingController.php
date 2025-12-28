<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\CopyTradingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CopyTradingController extends Controller
{
    protected CopyTradingService $service;

    public function __construct(CopyTradingService $service)
    {
        $this->service = $service;
    }

    /**
     * Get copy trading settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            // Check if trading-management-addon is active
            if (!\App\Support\AddonRegistry::active('trading-management-addon')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Copy trading module is not available'
                ], 404);
            }

            $userId = auth()->id();
            $data = $this->service->getSettings($userId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update copy trading settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'min_provider_score' => 'nullable|numeric|min:0|max:100',
            'slippage_buffer_enabled' => 'nullable|boolean',
            'dynamic_lot_enabled' => 'nullable|boolean',
        ]);

        try {
            $userId = auth()->id();
            $settings = $this->service->updateSettings($userId, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Browse traders
     */
    public function getTraders(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 20);
            $traders = $this->service->getTraders($perPage);

            return response()->json([
                'success' => true,
                'data' => $traders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch traders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trader profile
     */
    public function getTrader($id): JsonResponse
    {
        try {
            $traderId = (int) $id;
            $followerId = auth()->id();
            $data = $this->service->getTraderProfile($traderId, $followerId);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trader not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trader: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get copy trading subscriptions
     */
    public function getSubscriptions(Request $request): JsonResponse
    {
        try {
            $followerId = auth()->id();
            $perPage = (int) $request->get('per_page', 20);
            $subscriptions = $this->service->getSubscriptions($followerId, $perPage);

            return response()->json([
                'success' => true,
                'data' => $subscriptions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscriptions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get copy trading history
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $followerId = auth()->id();
            $perPage = (int) $request->get('per_page', 20);
            $executions = $this->service->getHistory($followerId, $perPage);

            return response()->json([
                'success' => true,
                'data' => $executions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch history: ' . $e->getMessage()
            ], 500);
        }
    }
}

