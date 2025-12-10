<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserRequest;
use App\Models\Payment;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\Withdraw;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\Helper\Helper;

/**
 * @group Admin APIs
 * User management endpoints
 */
class UserController extends Controller
{
    protected $userservice;

    public function __construct(AdminUserService $userservice)
    {
        $this->userservice = $userservice;
    }

    /**
     * List Users
     * 
     * Get all users with filters
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam search string Search by username, email, or phone. Example: john
     * @queryParam user_status string Filter by status (user_active, user_inactive). Example: user_active
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [...]
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $user = User::query();

        if ($request->search) {
            $user->where(function ($item) use ($request) {
                $item->where('username', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->user_status) {
            $status = $request->user_status === 'user_active' ? 1 : 0;
            $user->where('status', $status);
        }

        $users = $user->latest()->paginate(Helper::pagination());

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get User Details
     * 
     * Get detailed information about a user
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required User ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "user": {...},
     *     "totalRef": 5,
     *     "userCommission": "50.00",
     *     "withdrawTotal": "100.00",
     *     "totalDeposit": "500.00",
     *     "totalInvest": "99.00",
     *     "totalTicket": 3
     *   }
     * }
     */
    public function show($id): JsonResponse
    {
        $user = User::with('refferals')->findOrFail($id);

        $data = [
            'user' => $user,
            'totalRef' => $user->refferals->count(),
            'userCommission' => $user->commissions->sum('amount'),
            'withdrawTotal' => Withdraw::where('user_id', $user->id)->where('status', 1)->sum('withdraw_amount'),
            'totalDeposit' => $user->deposits()->where('status', 1)->sum('amount'),
            'totalInvest' => $user->payments()->where('status', 1)->sum('amount'),
            'totalTicket' => $user->tickets->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Update User
     * 
     * Update user information
     * 
     * @param AdminUserRequest $request
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required User ID. Example: 1
     * @bodyParam phone string optional Phone number. Example: +1234567890
     * @bodyParam username string optional Username. Example: john_doe
     * @bodyParam email string optional Email address. Example: user@example.com
     * @bodyParam status integer optional Status (0 or 1). Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "User updated successfully"
     * }
     */
    public function update(AdminUserRequest $request, $id): JsonResponse
    {
        // Merge user ID into request for validation
        $request->merge(['user' => $id]);
        $isSuccess = $this->userservice->update($request);

        if ($isSuccess['type'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => $isSuccess['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $isSuccess['message'] ?? 'Failed to update user'
        ], 400);
    }

    /**
     * Toggle User Status
     * 
     * Activate or deactivate a user
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required User ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "User status updated successfully"
     * }
     */
    public function toggleStatus($id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->status = $user->status ? 0 : 1;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully'
        ]);
    }

    /**
     * Update User Balance
     * 
     * Update user's wallet balance
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required User ID. Example: 1
     * @bodyParam balance decimal required New balance. Example: 100.00
     * @response 200 {
     *   "success": true,
     *   "message": "Balance updated successfully"
     * }
     */
    public function updateBalance(Request $request, $id): JsonResponse
    {
        $request->validate([
            'balance' => 'required|numeric|min:0'
        ]);

        $user = User::findOrFail($id);
        $user->balance = $request->balance;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Balance updated successfully'
        ]);
    }

    /**
     * Update KYC Status
     * 
     * Update user's KYC verification status
     * 
     * @param Request $request
     * @param int $id
     * @param string $status
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required User ID. Example: 1
     * @urlParam status string required KYC status (approved, rejected). Example: approved
     * @response 200 {
     *   "success": true,
     *   "message": "KYC status updated successfully"
     * }
     */
    public function updateKycStatus(Request $request, $id, $status): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->kyc_status = $status;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'KYC status updated successfully'
        ]);
    }

    /**
     * Send Email to User
     * 
     * Send email to a specific user
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required User ID. Example: 1
     * @bodyParam subject string required Email subject. Example: Important Notice
     * @bodyParam message string required Email message. Example: Your message here
     * @response 200 {
     *   "success": true,
     *   "message": "Email sent successfully"
     * }
     */
    public function sendMail(Request $request, $id): JsonResponse
    {
        $request->validate([
            'subject' => 'required',
            'message' => 'required',
        ]);

        $user = User::findOrFail($id);
        Helper::commonMail([
            'name' => $user->username,
            'subject' => $request->subject,
            'message' => $request->message,
            'email' => $user->email,
            'username' => $user->username,
            'app_name' => Helper::config()->appname,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email sent successfully'
        ]);
    }
}
