<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserProfile;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group User APIs
 * User profile management endpoints
 */
class ProfileController extends Controller
{
    protected $profile;

    public function __construct(UserProfileService $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Get User Profile
     * 
     * Get authenticated user's profile information
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "username": "john_doe",
     *     "email": "user@example.com",
     *     "phone": "+1234567890",
     *     "balance": "100.00",
     *     "status": 1,
     *     "address": {
     *       "country": "US",
     *       "city": "New York",
     *       "zip": "10001",
     *       "state": "NY"
     *     },
     *     "telegram_id": null,
     *     "image": "path/to/image.jpg"
     *   }
     * }
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'balance' => $user->balance,
                'status' => $user->status,
                'address' => $user->address,
                'telegram_id' => $user->telegram_id,
                'image' => $user->image,
                'is_email_verified' => $user->is_email_verified,
                'kyc_status' => $user->kyc_status,
            ]
        ]);
    }

    /**
     * Update User Profile
     * 
     * Update authenticated user's profile
     * 
     * @param UserProfile $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam phone string required Phone number. Example: +1234567890
     * @bodyParam country string required Country. Example: US
     * @bodyParam city string required City. Example: New York
     * @bodyParam zip string required ZIP code. Example: 10001
     * @bodyParam state string required State. Example: NY
     * @bodyParam telegram_id string optional Telegram ID. Example: 123456789
     * @bodyParam image file optional Profile image
     * @response 200 {
     *   "success": true,
     *   "message": "Profile Updated Successfully"
     * }
     */
    public function update(UserProfile $request): JsonResponse
    {
        $isSuccess = $this->profile->update($request);

        if ($isSuccess['type'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => $isSuccess['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $isSuccess['message'] ?? 'Failed to update profile'
        ], 400);
    }

    /**
     * Change Password
     * 
     * Change authenticated user's password
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam current_password string required Current password. Example: oldpassword123
     * @bodyParam password string required New password (min 8 characters). Example: newpassword123
     * @bodyParam password_confirmation string required Password confirmation. Example: newpassword123
     * @response 200 {
     *   "success": true,
     *   "message": "Password changed successfully"
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Current password is incorrect"
     * }
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}
