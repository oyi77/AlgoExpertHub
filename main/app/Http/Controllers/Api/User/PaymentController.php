<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User APIs
 * Payment and deposit endpoints
 */
class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create Payment
     * 
     * Initiate a payment for plan subscription
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam plan_id integer required Plan ID. Example: 1
     * @bodyParam gateway_id integer required Gateway ID. Example: 1
     * @bodyParam amount decimal required Payment amount. Example: 99.00
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "redirect_url": "/user/gateway-details/1",
     *     "trx": "ABCD1234EFGH5678"
     *   },
     *   "message": "Redirect to gateway"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge(['plan_id' => $request->plan_id]);
        $isSuccess = $this->paymentService->payNow($request);

        if ($isSuccess['type'] == 'error') {
            return response()->json([
                'success' => false,
                'message' => $isSuccess['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'redirect_url' => $isSuccess['message'],
                'trx' => session('trx')
            ],
            'message' => 'Redirect to gateway'
        ]);
    }

    /**
     * List Payments
     * 
     * Get user's payment history
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam page integer Page number. Example: 1
     * @queryParam status integer Filter by status (0=pending, 1=approved, 2=rejected). Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "trx": "ABCD1234EFGH5678",
     *         "plan_id": 1,
     *         "amount": "99.00",
     *         "status": 1,
     *         "created_at": "2023-01-01T00:00:00.000000Z"
     *       }
     *     ]
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->when($request->status !== null, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->with('plan', 'gateway')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Get Payment Status
     * 
     * Get status of a specific payment
     * 
     * @param string $trx
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @urlParam trx string required Transaction ID. Example: ABCD1234EFGH5678
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "trx": "ABCD1234EFGH5678",
     *     "plan_id": 1,
     *     "amount": "99.00",
     *     "status": 1,
     *     "plan": {...}
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Payment not found"
     * }
     */
    public function show($trx, Request $request): JsonResponse
    {
        $payment = Payment::where('trx', $trx)
            ->where('user_id', $request->user()->id)
            ->with('plan', 'gateway')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Create Deposit
     * 
     * Initiate a wallet deposit
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam gateway_id integer required Gateway ID. Example: 1
     * @bodyParam amount decimal required Deposit amount. Example: 100.00
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "redirect_url": "/user/gateway-details/1",
     *     "trx": "ABCD1234EFGH5678"
     *   },
     *   "message": "Redirect to gateway"
     * }
     */
    public function deposit(Request $request): JsonResponse
    {
        $request->merge(['type' => 'deposit']);
        $isSuccess = $this->paymentService->payNow($request);

        if ($isSuccess['type'] == 'error') {
            return response()->json([
                'success' => false,
                'message' => $isSuccess['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'redirect_url' => $isSuccess['message'],
                'trx' => session('trx')
            ],
            'message' => 'Redirect to gateway'
        ]);
    }

    /**
     * List Deposits
     * 
     * Get user's deposit history
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam page integer Page number. Example: 1
     * @queryParam status integer Filter by status (0=pending, 1=approved, 2=rejected). Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "trx": "ABCD1234EFGH5678",
     *         "amount": "100.00",
     *         "status": 1,
     *         "created_at": "2023-01-01T00:00:00.000000Z"
     *       }
     *     ]
     *   }
     * }
     */
    public function deposits(Request $request): JsonResponse
    {
        $deposits = Deposit::where('user_id', $request->user()->id)
            ->where('type', 1)
            ->when($request->status !== null, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->with('gateway')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $deposits
        ]);
    }
}
