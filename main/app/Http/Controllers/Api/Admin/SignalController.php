<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignalRequest;
use App\Models\Signal;
use App\Services\SignalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\Helper\Helper;

/**
 * @group Admin APIs
 * Signal management endpoints
 */
class SignalController extends Controller
{
    protected $signal;

    public function __construct(SignalService $signal)
    {
        $this->signal = $signal;
    }

    /**
     * List Signals
     * 
     * Get all signals with filters
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam type string Filter by type (draft, published). Example: published
     * @queryParam search string Search signals. Example: EUR/USD
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $signals = Signal::when($request->type, function ($q) use ($request) {
            $q->where('is_published', ($request->type === 'draft' ? 0 : 1));
        })
        ->whereHas('plans')
        ->whereHas('pair')
        ->whereHas('time')
        ->whereHas('market')
        ->search($request->search)
        ->latest()
        ->with('plans', 'pair', 'time', 'market')
        ->paginate(Helper::pagination());

        return response()->json([
            'success' => true,
            'data' => $signals
        ]);
    }

    /**
     * Create Signal
     * 
     * Create a new trading signal
     * 
     * @param SignalRequest $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam title string required Signal title. Example: EUR/USD Buy Signal
     * @bodyParam description string Signal description
     * @bodyParam currency_pair_id integer required Currency pair ID. Example: 1
     * @bodyParam time_frame_id integer required Timeframe ID. Example: 1
     * @bodyParam market_id integer required Market ID. Example: 1
     * @bodyParam open_price decimal required Entry price. Example: 1.1000
     * @bodyParam sl decimal required Stop loss. Example: 1.0950
     * @bodyParam tp decimal required Take profit. Example: 1.1100
     * @bodyParam direction string required Direction (buy, sell, long, short). Example: buy
     * @bodyParam plan_ids array required Array of plan IDs. Example: [1, 2]
     * @response 201 {
     *   "success": true,
     *   "message": "Signal created successfully"
     * }
     */
    public function store(SignalRequest $request): JsonResponse
    {
        $isSuccess = $this->signal->create($request);

        if ($isSuccess['type'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => $isSuccess['message']
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $isSuccess['message'] ?? 'Failed to create signal'
        ], 400);
    }

    /**
     * Get Signal
     * 
     * Get signal details
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Signal ID. Example: 1234567
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function show($id): JsonResponse
    {
        $signal = Signal::with('plans', 'pair', 'time', 'market')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $signal
        ]);
    }

    /**
     * Update Signal
     * 
     * Update signal information
     * 
     * @param SignalRequest $request
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Signal ID. Example: 1234567
     * @response 200 {
     *   "success": true,
     *   "message": "Signal updated successfully"
     * }
     */
    public function update(SignalRequest $request, $id): JsonResponse
    {
        $isSuccess = $this->signal->update($request, $id);

        if ($isSuccess['type'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => $isSuccess['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $isSuccess['message']
        ]);
    }

    /**
     * Delete Signal
     * 
     * Delete a signal
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Signal ID. Example: 1234567
     * @response 200 {
     *   "success": true,
     *   "message": "Signal deleted successfully"
     * }
     */
    public function destroy($id): JsonResponse
    {
        $isSuccess = $this->signal->destroy($id);

        if ($isSuccess['type'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => $isSuccess['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $isSuccess['message']
        ]);
    }

    /**
     * Publish Signal
     * 
     * Publish a draft signal
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Signal ID. Example: 1234567
     * @response 200 {
     *   "success": true,
     *   "message": "Signal published successfully"
     * }
     */
    public function publish($id): JsonResponse
    {
        $isSuccess = $this->signal->sent($id);

        if ($isSuccess['type'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => $isSuccess['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $isSuccess['message']
        ]);
    }

    /**
     * Assign Plans to Signal
     * 
     * Assign plans to a signal
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Signal ID. Example: 1234567
     * @bodyParam plan_ids array required Array of plan IDs. Example: [1, 2, 3]
     * @response 200 {
     *   "success": true,
     *   "message": "Plans assigned successfully"
     * }
     */
    public function assignPlans(Request $request, $id): JsonResponse
    {
        $request->validate([
            'plan_ids' => 'required|array',
            'plan_ids.*' => 'exists:plans,id'
        ]);

        $signal = Signal::findOrFail($id);
        $signal->plans()->sync($request->plan_ids);

        return response()->json([
            'success' => true,
            'message' => 'Plans assigned successfully'
        ]);
    }
}
