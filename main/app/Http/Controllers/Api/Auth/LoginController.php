<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLoginRequest;
use App\Services\UserLogin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @group Authentication
 * User authentication endpoints
 */
class LoginController extends Controller
{
    protected $login;

    public function __construct(UserLogin $login)
    {
        $this->login = $login;
    }

    /**
     * User Login
     * 
     * Authenticate user and return access token
     * 
     * @param UserLoginRequest $request
     * @return JsonResponse
     * @bodyParam email string required User email address. Example: user@example.com
     * @bodyParam password string required User password. Example: password123
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "username": "john_doe",
     *       "email": "user@example.com",
     *       "balance": "100.00"
     *     },
     *     "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
     *   },
     *   "message": "Successfully logged in"
     * }
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid credentials"
     * }
     */
    public function login(UserLoginRequest $request): JsonResponse
    {
        $isSuccess = $this->login->login($request);

        if ($isSuccess['type'] == 'error') {
            return response()->json([
                'success' => false,
                'message' => $isSuccess['message']
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'balance' => $user->balance,
                    'status' => $user->status,
                ],
                'token' => $token
            ],
            'message' => $isSuccess['message']
        ]);
    }

    /**
     * User Logout
     * 
     * Revoke current access token
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Successfully logged out"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Refresh Token
     * 
     * Revoke current token and issue new one
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
     *   },
     *   "message": "Token refreshed successfully"
     * }
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token
            ],
            'message' => 'Token refreshed successfully'
        ]);
    }
}
