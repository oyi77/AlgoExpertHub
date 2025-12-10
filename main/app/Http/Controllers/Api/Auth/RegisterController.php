<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\UserRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Authentication
 * User registration endpoints
 */
class RegisterController extends Controller
{
    protected $register;

    public function __construct(UserRegistration $register)
    {
        $this->register = $register;
    }

    /**
     * User Registration
     * 
     * Register a new user account
     * 
     * @param RegisterRequest $request
     * @return JsonResponse
     * @bodyParam username string required Username. Example: john_doe
     * @bodyParam email string required Email address. Example: user@example.com
     * @bodyParam phone string required Phone number. Example: +1234567890
     * @bodyParam password string required Password (min 8 characters). Example: password123
     * @bodyParam password_confirmation string required Password confirmation. Example: password123
     * @bodyParam reffered_by string optional Referrer username. Example: referrer_user
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "username": "john_doe",
     *       "email": "user@example.com",
     *       "balance": "0.00"
     *     },
     *     "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
     *   },
     *   "message": "Successfully Registered"
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $isSuccess = $this->register->register($request);

        if ($isSuccess['type'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => $isSuccess['message']
            ], 422);
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
        ], 201);
    }
}
