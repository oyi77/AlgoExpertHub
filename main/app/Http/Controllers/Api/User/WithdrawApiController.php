<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Withdraw;
use App\Models\WithdrawGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Withdrawals
 *
 * Endpoints for managing withdrawal requests.
 */
class WithdrawApiController extends Controller
{
    /**
     * Get Withdrawals
     *
     * Retrieve user's withdrawal history.
     *
     * @queryParam status string Filter by status: pending, approved, rejected. Example: pending
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function index(Request $request)
    {
        $query = Withdraw::where('user_id', Auth::id());

        if ($request->has('status')) {
            $statusMap = [
                'pending' => 0,
                'approved' => 1,
                'rejected' => 2
            ];
            $status = $statusMap[$request->get('status')] ?? null;
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        $withdrawals = $query->with('gateway')->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Get Available Withdrawal Gateways
     *
     * Retrieve available withdrawal methods.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function getGateways()
    {
        $gateways = WithdrawGateway::where('status', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $gateways
        ]);
    }

    /**
     * Create Withdrawal Request
     *
     * Submit a new withdrawal request.
     *
     * @bodyParam gateway_id int required Withdrawal gateway ID. Example: 1
     * @bodyParam amount numeric required Withdrawal amount. Example: 100.00
     * @bodyParam account_details object required Account details for withdrawal.
     * @response 201 {
     *   "success": true,
     *   "message": "Withdrawal request submitted successfully",
     *   "data": {...}
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway_id' => 'required|exists:withdraw_gateways,id',
            'amount' => 'required|numeric|min:1',
            'account_details' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $gateway = WithdrawGateway::findOrFail($request->gateway_id);

        // Validate minimum/maximum amounts
        if ($request->amount < $gateway->min_amount) {
            return response()->json([
                'success' => false,
                'message' => "Minimum withdrawal amount is {$gateway->min_amount}"
            ], 400);
        }

        if ($request->amount > $gateway->max_amount) {
            return response()->json([
                'success' => false,
                'message' => "Maximum withdrawal amount is {$gateway->max_amount}"
            ], 400);
        }

        // Check user balance
        $charge = ($request->amount * $gateway->charge_percent / 100) + $gateway->charge_fixed;
        $totalAmount = $request->amount + $charge;

        if ($user->balance < $totalAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ], 400);
        }

        // Create withdrawal request
        $withdraw = Withdraw::create([
            'user_id' => $user->id,
            'gateway_id' => $gateway->id,
            'amount' => $request->amount,
            'charge' => $charge,
            'total_amount' => $totalAmount,
            'account_details' => $request->account_details,
            'status' => 0, // Pending
            'trx' => \Str::upper(\Str::random(16)),
        ]);

        // Deduct from user balance
        $user->balance -= $totalAmount;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request submitted successfully',
            'data' => $withdraw
        ], 201);
    }

    /**
     * Get Withdrawal Details
     *
     * Retrieve details of a specific withdrawal.
     *
     * @urlParam id int required Withdrawal ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function show($id)
    {
        $withdraw = Withdraw::where('user_id', Auth::id())
            ->with('gateway')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $withdraw
        ]);
    }
}
