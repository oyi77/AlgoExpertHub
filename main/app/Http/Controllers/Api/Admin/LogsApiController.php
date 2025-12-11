<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Withdraw;
use Illuminate\Http\Request;

/**
 * @group Admin - Logs & Reports
 *
 * Endpoints for viewing system logs and reports.
 */
class LogsApiController extends Controller
{
    /**
     * Transaction Logs
     *
     * Get transaction logs with optional user filter.
     *
     * @queryParam user_id int Filter by user ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function transactions(Request $request)
    {
        $query = Transaction::with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $transactions = $query->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Payment Reports
     *
     * Get payment reports.
     *
     * @queryParam user_id int Filter by user ID. Example: 1
     * @queryParam status string Filter by status. Example: approved
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function payments(Request $request)
    {
        $query = Payment::with('user', 'gateway');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Withdraw Reports
     *
     * Get withdrawal reports.
     *
     * @queryParam user_id int Filter by user ID. Example: 1
     * @queryParam status int Filter by status: 0=pending, 1=approved, 2=rejected. Example: 0
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function withdrawals(Request $request)
    {
        $query = Withdraw::with('user', 'gateway');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Commission Logs
     *
     * Get commission/referral logs.
     *
     * @queryParam user_id int Filter by user ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function commissions(Request $request)
    {
        $query = Transaction::where('type', '+')
            ->where('details', 'like', '%commission%')
            ->with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $commissions = $query->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $commissions
        ]);
    }

    /**
     * Trade Logs
     *
     * Get trading activity logs.
     *
     * @queryParam user_id int Filter by user ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function trades(Request $request)
    {
        // Check if trading management addon is available
        if (!class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Trading management addon not available'
            ], 503);
        }

        $query = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::with('connection');

        if ($request->has('user_id')) {
            $query->whereHas('connection', function($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        $trades = $query->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $trades
        ]);
    }
}
