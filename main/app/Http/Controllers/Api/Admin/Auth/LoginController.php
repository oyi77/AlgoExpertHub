<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Services\AdminLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Admin APIs
 * Admin authentication endpoints
 */
class LoginController extends Controller
{
    protected $login;

    public function __construct(AdminLoginService $login)
    {
        $this->login = $login;
    }

    /**
     * Admin Login
     * 
     * Authenticate admin and return access token
     * 
     * @param AdminLoginRequest $request
     * @return JsonResponse
     * @bodyParam email string required Admin email or username. Example: admin@example.com
     * @bodyParam password string required Admin password. Example: password123
     * @bodyParam remember boolean optional Remember me. Example: false
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "admin": {
     *       "id": 1,
     *       "username": "admin",
     *       "email": "admin@example.com",
     *       "type": "super"
     *     },
     *     "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
     *   },
     *   "message": "Login Successful"
     * }
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid Credentials"
     * }
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        [$data, $remember] = $this->login->validateData($request);

        if (auth()->guard('admin')->attempt($data, $remember)) {
            $admin = Auth::guard('admin')->user();
            $token = $admin->createToken('admin-api-token', ['admin'])->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'username' => $admin->username,
                        'email' => $admin->email,
                        'type' => $admin->type,
                    ],
                    'token' => $token
                ],
                'message' => 'Login Successful'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid Credentials'
        ], 401);
    }

    /**
     * Admin Logout
     * 
     * Revoke current access token
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Logout Successful"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        auth()->guard('admin')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logout Successful'
        ]);
    }
}
