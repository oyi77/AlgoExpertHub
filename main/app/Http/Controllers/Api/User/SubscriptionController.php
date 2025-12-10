<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\PlanSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User APIs
 * Subscription management endpoints
 */
class SubscriptionController extends Controller
{
    /**
     * List Subscriptions
     * 
     * Get user's subscription history
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "plan_id": 1,
     *         "start_date": "2023-01-01",
     *         "end_date": "2023-01-31",
     *         "is_current": 1,
     *         "status": "active",
     *         "plan": {...}
     *       }
     *     ]
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $subscriptions = PlanSubscription::where('user_id', $request->user()->id)
            ->with('plan')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    /**
     * Get Current Subscription
     * 
     * Get user's current active subscription
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "plan_id": 1,
     *     "start_date": "2023-01-01",
     *     "end_date": "2023-01-31",
     *     "is_current": 1,
     *     "status": "active",
     *     "plan": {
     *       "id": 1,
     *       "plan_name": "Premium Plan",
     *       "price": "99.00"
     *     }
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "No active subscription found"
     * }
     */
    public function current(Request $request): JsonResponse
    {
        $subscription = PlanSubscription::where('user_id', $request->user()->id)
            ->where('is_current', 1)
            ->with('plan')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription
        ]);
    }
}
