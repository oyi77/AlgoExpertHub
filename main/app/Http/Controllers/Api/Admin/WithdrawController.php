<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdraw;
use App\Services\WithdrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\Helper\Helper;

/**
 * @group Admin APIs
 * Withdrawal management endpoints
 */
class WithdrawController extends Controller
{
    protected $withdrawService;

    public function __construct(WithdrawService $withdrawService)
    {
        $this->withdrawService = $withdrawService;
    }

    /**
     * List Withdrawals
     * 
     * Get all withdrawal requests
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam status integer Filter by status (0=pending, 1=approved, 2=rejected). Example: 0
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $withdrawals = Withdraw::when($request->status !== null, function($q) use ($request) {
            $q->where('status', $request->status);
        })
        ->with('user', 'withdrawGateway')
        ->latest()
        ->paginate(Helper::pagination());

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Get Withdrawal Details
     * 
     * Get withdrawal details
     * 
     * @param Withdraw $withdraw
     * @return JsonResponse
     * @authenticated
     * @urlParam withdraw integer required Withdrawal ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function show(Withdraw $withdraw): JsonResponse
    {
        $withdraw->load('user', 'withdrawGateway');

        return response()->json([
            'success' => true,
            'data' => $withdraw
        ]);
    }

    /**
     * Approve Withdrawal
     * 
     * Approve a withdrawal request
     * 
     * @param Withdraw $withdraw
     * @return JsonResponse
     * @authenticated
     * @urlParam withdraw integer required Withdrawal ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Withdrawal approved successfully"
     * }
     */
    public function approve(Withdraw $withdraw): JsonResponse
    {
        $isSuccess = $this->withdrawService->accept($withdraw);

        if ($isSuccess['type'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => $isSuccess['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $isSuccess['message'] ?? 'Failed to approve withdrawal'
        ], 400);
    }

    /**
     * Reject Withdrawal
     * 
     * Reject a withdrawal request
     * 
     * @param Withdraw $withdraw
     * @return JsonResponse
     * @authenticated
     * @urlParam withdraw integer required Withdrawal ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Withdrawal rejected successfully"
     * }
     */
    public function reject(Withdraw $withdraw): JsonResponse
    {
        $withdraw->status = 2;
        $withdraw->save();

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal rejected successfully'
        ]);
    }
}
