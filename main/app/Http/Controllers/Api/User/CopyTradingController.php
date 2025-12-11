<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CopyTradingController extends Controller
{
    /**
     * Get copy trading settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            // Check if trading-management-addon is active
            if (!\App\Support\AddonRegistry::active('trading-management-addon')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Copy trading module is not available'
                ], 404);
            }

            // Get settings from cache or database
            $settings = \Illuminate\Support\Facades\Cache::get('smart_risk_settings_' . $userId, [
                'enabled' => false,
                'min_provider_score' => 70,
                'slippage_buffer_enabled' => false,
                'dynamic_lot_enabled' => false,
            ]);

            // Get follower count if table exists
            $followerCount = 0;
            if (\Schema::hasTable('copy_trading_subscriptions')) {
                $followerCount = \DB::table('copy_trading_subscriptions')
                    ->where('trader_id', $userId)
                    ->where('is_active', true)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                    'follower_count' => $followerCount
                ]
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
            $currentSettings = \Illuminate\Support\Facades\Cache::get('smart_risk_settings_' . $userId, [
                'enabled' => false,
                'min_provider_score' => 70,
                'slippage_buffer_enabled' => false,
                'dynamic_lot_enabled' => false,
            ]);

            $settings = array_merge($currentSettings, $validated);
            \Illuminate\Support\Facades\Cache::put('smart_risk_settings_' . $userId, $settings, now()->addYear());

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
            if (!\Schema::hasTable('trader_profiles')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trader profiles table does not exist'
                ], 404);
            }

            $traders = \DB::table('trader_profiles')
                ->where('visibility', 'PUBLIC')
                ->where('is_verified', true)
                ->join('users', 'trader_profiles.user_id', '=', 'users.id')
                ->select('trader_profiles.*', 'users.username', 'users.email')
                ->orderBy('total_profit_percent', 'desc')
                ->paginate($request->get('per_page', 20));

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
            if (!\Schema::hasTable('trader_profiles')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trader profiles table does not exist'
                ], 404);
            }

            $trader = \DB::table('trader_profiles')
                ->where('user_id', $id)
                ->where('visibility', 'PUBLIC')
                ->join('users', 'trader_profiles.user_id', '=', 'users.id')
                ->select('trader_profiles.*', 'users.username', 'users.email')
                ->first();

            if (!$trader) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trader not found'
                ], 404);
            }

            // Check if current user is following
            $isFollowing = false;
            if (\Schema::hasTable('copy_trading_subscriptions')) {
                $isFollowing = \DB::table('copy_trading_subscriptions')
                    ->where('trader_id', $id)
                    ->where('follower_id', auth()->id())
                    ->where('is_active', true)
                    ->exists();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'trader' => $trader,
                    'is_following' => $isFollowing
                ]
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
            if (!\Schema::hasTable('copy_trading_subscriptions')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Copy trading subscriptions table does not exist'
                ], 404);
            }

            $subscriptions = \DB::table('copy_trading_subscriptions')
                ->where('follower_id', auth()->id())
                ->join('users', 'copy_trading_subscriptions.trader_id', '=', 'users.id')
                ->select('copy_trading_subscriptions.*', 'users.username as trader_username')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

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
            if (!\Schema::hasTable('copy_trading_executions')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Copy trading executions table does not exist'
                ], 404);
            }

            $executions = \DB::table('copy_trading_executions')
                ->where('follower_id', auth()->id())
                ->join('users', 'copy_trading_executions.trader_id', '=', 'users.id')
                ->select('copy_trading_executions.*', 'users.username as trader_username')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

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

