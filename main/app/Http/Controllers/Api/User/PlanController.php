<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Services\UserPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User APIs
 * Plan and subscription management endpoints
 */
class PlanController extends Controller
{
    protected $planservice;

    public function __construct(UserPlanService $planservice)
    {
        $this->planservice = $planservice;
    }

    /**
     * List Plans
     * 
     * Get all available subscription plans
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
     *         "plan_name": "Premium Plan",
     *         "price": "99.00",
     *         "plan_type": "limited",
     *         "duration": 30,
     *         "status": 1
     *       }
     *     ]
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $plans = Plan::where('status', 1)->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Get Plan Details
     * 
     * Get details of a specific plan
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Plan ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "plan_name": "Premium Plan",
     *     "price": "99.00",
     *     "plan_type": "limited",
     *     "duration": 30,
     *     "status": 1
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Plan not found"
     * }
     */
    public function show($id): JsonResponse
    {
        $plan = Plan::where('status', 1)->find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    /**
     * Subscribe to Plan
     * 
     * Initiate subscription to a plan
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam plan_id integer required Plan ID. Example: 1
     * @bodyParam gateway_id integer required Gateway ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "redirect_url": "/user/gateway-details/1"
     *   },
     *   "message": "Redirect to gateway"
     * }
     */
    public function subscribe(Request $request): JsonResponse
    {
        $isSuccess = $this->planservice->subscribe($request);

        if ($isSuccess['type'] == 'error') {
            return response()->json([
                'success' => false,
                'message' => $isSuccess['message']
            ], 400);
        }

        if ($isSuccess['type'] == 'redirect') {
            return response()->json([
                'success' => true,
                'data' => [
                    'redirect_url' => $isSuccess['message']
                ],
                'message' => 'Redirect to gateway'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $isSuccess['message']
        ]);
    }

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
     *         "status": "active"
     *       }
     *     ]
     *   }
     * }
     */
    public function subscriptions(Request $request): JsonResponse
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
    public function currentSubscription(Request $request): JsonResponse
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
