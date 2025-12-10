<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\Helper\Helper;

/**
 * @group Admin APIs
 * Plan management endpoints
 */
class PlanController extends Controller
{
    protected $plan;

    public function __construct(PlanService $plan)
    {
        $this->plan = $plan;
    }

    /**
     * List Plans
     * 
     * Get all plans
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam search string Search plans. Example: premium
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $plans = Plan::search($request->search)->orderBy('id', 'ASC')->paginate(Helper::pagination());

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Create Plan
     * 
     * Create a new subscription plan
     * 
     * @param PlanRequest $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam plan_name string required Plan name. Example: Premium Plan
     * @bodyParam price decimal required Plan price. Example: 99.00
     * @bodyParam plan_type string required Plan type (limited, lifetime). Example: limited
     * @bodyParam duration integer required Duration in days (for limited plans). Example: 30
     * @response 201 {
     *   "success": true,
     *   "message": "Plan Created Successfully"
     * }
     */
    public function store(PlanRequest $request): JsonResponse
    {
        $this->plan->createPlan($request);

        return response()->json([
            'success' => true,
            'message' => 'Plan Created Successfully'
        ], 201);
    }

    /**
     * Get Plan
     * 
     * Get plan details
     * 
     * @param Plan $plan
     * @return JsonResponse
     * @authenticated
     * @urlParam plan integer required Plan ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function show(Plan $plan): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    /**
     * Update Plan
     * 
     * Update plan information
     * 
     * @param PlanRequest $request
     * @param Plan $plan
     * @return JsonResponse
     * @authenticated
     * @urlParam plan integer required Plan ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Plan Updated Successfully"
     * }
     */
    public function update(PlanRequest $request, Plan $plan): JsonResponse
    {
        $request->merge(['id' => $plan->id]);
        $isSuccess = $this->plan->updatePlan($request);

        if ($isSuccess['type'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => $isSuccess['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Plan Updated Successfully'
        ]);
    }

    /**
     * Delete Plan
     * 
     * Delete a plan
     * 
     * @param Plan $plan
     * @return JsonResponse
     * @authenticated
     * @urlParam plan integer required Plan ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Plan deleted successfully"
     * }
     */
    public function destroy(Plan $plan): JsonResponse
    {
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully'
        ]);
    }

    /**
     * Toggle Plan Status
     * 
     * Activate or deactivate a plan
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Plan ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Plan status updated successfully"
     * }
     */
    public function toggleStatus($id): JsonResponse
    {
        $isSuccess = $this->plan->changeStatus($id);

        if ($isSuccess['type'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => $isSuccess['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $isSuccess['message'] ?? 'Failed to update status'
        ], 400);
    }
}
