<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Withdraw;
use App\Models\WithdrawGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin APIs
 * Admin dashboard endpoints
 */
class DashboardController extends Controller
{
    /**
     * Get Dashboard Statistics
     * 
     * Get admin dashboard statistics and overview
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "totalDeposit": "10000.00",
     *     "pendingDeposit": "500.00",
     *     "totalWithdraw": "2000.00",
     *     "pendingWithdraw": "100.00",
     *     "totalUser": 150,
     *     "pendingUser": 5,
     *     "activeUser": 145,
     *     "emailUser": 140,
     *     "totalTicket": 25,
     *     "pendingTicket": 3,
     *     "totalOnlineGateway": 5,
     *     "totalOfflineGateway": 2,
     *     "totalWithdrawGateway": 3,
     *     "totalStaff": 10
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $data = [
            'totalDeposit' => Deposit::where('status', 1)->sum('amount'),
            'pendingDeposit' => Deposit::where('status', 0)->sum('amount'),
            'totalWithdraw' => Withdraw::where('status', 1)->sum('withdraw_amount'),
            'pendingWithdraw' => Withdraw::where('status', 0)->sum('withdraw_amount'),
            'totalUser' => User::count(),
            'pendingUser' => User::where('status', 0)->count(),
            'activeUser' => User::where('status', 1)->count(),
            'emailUser' => User::where('status', 1)->where('is_email_verified', 1)->count(),
            'totalTicket' => Ticket::count(),
            'pendingTicket' => Ticket::where('status', 2)->count(),
            'totalOnlineGateway' => Gateway::where('type', 1)->count(),
            'totalOfflineGateway' => Gateway::where('type', 0)->count(),
            'totalWithdrawGateway' => WithdrawGateway::where('status', 1)->count(),
            'totalStaff' => Admin::where('type', '!=', 'super')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
