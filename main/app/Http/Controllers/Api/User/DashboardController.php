<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\UserDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User APIs
 * User dashboard endpoints
 */
class DashboardController extends Controller
{
    protected $dashboard;

    public function __construct(UserDashboardService $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * Get Dashboard Data
     * 
     * Get user dashboard with statistics, signals, and graphs
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "currentPlan": {
     *       "id": 1,
     *       "name": "Premium Plan",
     *       "price": "99.00"
     *     },
     *     "totalbalance": "100.00",
     *     "totalDeposit": "500.00",
     *     "totalWithdraw": "50.00",
     *     "totalPayments": "99.00",
     *     "totalSupportTickets": 5,
     *     "user": {...},
     *     "transactions": [...],
     *     "signals": {...},
     *     "months": ["January", "February", ...],
     *     "totalAmount": [0, 99, 0, ...],
     *     "withdrawTotalAmount": [0, 50, 0, ...],
     *     "depositTotalAmount": [0, 500, 0, ...],
     *     "signalGrapTotal": [0, 10, 5, ...]
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->dashboard->dashboard();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get User Statistics
     * 
     * Get user statistics summary
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "totalbalance": "100.00",
     *     "totalDeposit": "500.00",
     *     "totalWithdraw": "50.00",
     *     "totalPayments": "99.00",
     *     "totalSupportTickets": 5
     *   }
     * }
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'totalbalance' => $user->balance,
                'totalDeposit' => $user->deposits()->where('status', 1)->sum('amount'),
                'totalWithdraw' => $user->withdraws()->where('status', 1)->sum('withdraw_amount'),
                'totalPayments' => $user->payments()->where('status', 1)->sum('amount'),
                'totalSupportTickets' => $user->tickets()->count(),
            ]
        ]);
    }
}
