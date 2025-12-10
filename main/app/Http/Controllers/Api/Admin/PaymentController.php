<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\Helper\Helper;
use Carbon\Carbon;

/**
 * @group Admin APIs
 * Payment management endpoints
 */
class PaymentController extends Controller
{
    /**
     * List Payments
     * 
     * Get all payments with filters
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam type string Filter by type (online, offline). Example: online
     * @queryParam status integer Filter by status (0=pending, 1=approved, 2=rejected). Example: 1
     * @queryParam dates string Date range (format: Y-m-d - Y-m-d). Example: 2023-01-01 - 2023-01-31
     * @queryParam search string Search payments. Example: ABCD1234
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $dates = [];
        if ($request->dates) {
            $dates = array_map(function($q) {
                return Carbon::parse($q);
            }, explode('-', $request->dates));
        }

        $type = $request->type === 'online' ? 1 : 0;

        $payment = Payment::query();

        if ($type) {
            $payment->where('type', 1);
        } else {
            $payment->where('type', 0);
        }

        $payments = $payment->when($request->dates, function($q) use ($dates) {
            $q->whereBetween('created_at', $dates);
        })
        ->when($request->status !== null, function($q) use ($request) {
            $q->where('status', $request->status);
        })
        ->search($request->search)
        ->latest()
        ->with('plan', 'gateway', 'user')
        ->paginate(Helper::pagination());

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Get Payment Details
     * 
     * Get payment details
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Payment ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function show($id): JsonResponse
    {
        $payment = Payment::with('plan', 'gateway', 'user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Approve Payment
     * 
     * Approve a pending payment
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Payment ID. Example: 1
     * @bodyParam trx string required Transaction ID. Example: ABCD1234EFGH5678
     * @response 200 {
     *   "success": true,
     *   "message": "Payment approved successfully"
     * }
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $payment->status = 1;
        $payment->save();

        // Create subscription logic here (from PaymentController::accept)

        return response()->json([
            'success' => true,
            'message' => 'Payment approved successfully'
        ]);
    }

    /**
     * Reject Payment
     * 
     * Reject a pending payment
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Payment ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Payment rejected successfully"
     * }
     */
    public function reject($id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $payment->status = 2;
        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment rejected successfully'
        ]);
    }

    /**
     * List Deposits
     * 
     * Get all deposits
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam status integer Filter by status. Example: 1
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function deposits(Request $request): JsonResponse
    {
        $deposits = Deposit::where('type', 1)
            ->when($request->status !== null, function($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->with('gateway', 'user')
            ->latest()
            ->paginate(Helper::pagination());

        return response()->json([
            'success' => true,
            'data' => $deposits
        ]);
    }

    /**
     * Approve Deposit
     * 
     * Approve a pending deposit
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Deposit ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Deposit approved successfully"
     * }
     */
    public function approveDeposit($id): JsonResponse
    {
        $deposit = Deposit::findOrFail($id);
        $deposit->status = 1;
        $deposit->save();

        $user = $deposit->user;
        $user->balance += $deposit->amount;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Deposit approved successfully'
        ]);
    }

    /**
     * Reject Deposit
     * 
     * Reject a pending deposit
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Deposit ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Deposit rejected successfully"
     * }
     */
    public function rejectDeposit($id): JsonResponse
    {
        $deposit = Deposit::findOrFail($id);
        $deposit->status = 2;
        $deposit->save();

        return response()->json([
            'success' => true,
            'message' => 'Deposit rejected successfully'
        ]);
    }
}
