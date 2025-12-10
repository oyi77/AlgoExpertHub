<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\Template;
use App\Models\User;
use App\Helpers\Helper\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Authentication
 * Password reset endpoints
 */
class PasswordResetController extends Controller
{
    /**
     * Request Password Reset
     * 
     * Send verification code to user's email
     * 
     * @param Request $request
     * @return JsonResponse
     * @bodyParam email string required User email address. Example: user@example.com
     * @response 200 {
     *   "success": true,
     *   "message": "Verification code sent to your email"
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Please Provide a valid Email"
     * }
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $general = Configuration::first();
        
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please Provide a valid Email'
            ], 404);
        }

        $code = random_int(100000, 999999);
        $user->email_verification_code = $code;
        $user->save();

        $template = Template::where('name', 'password_reset')->where('status', 1)->first();
        if ($template) {
            Helper::fireMail([
                'username' => $user->username,
                'email' => $user->email,
                'app_name' => Helper::config()->appname,
                'code' => $code
            ], $template);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your email'
        ]);
    }

    /**
     * Verify Reset Code
     * 
     * Verify the reset code sent to email
     * 
     * @param Request $request
     * @return JsonResponse
     * @bodyParam email string required User email address. Example: user@example.com
     * @bodyParam code string required Verification code. Example: 123456
     * @response 200 {
     *   "success": true,
     *   "message": "Code verified successfully"
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid Code"
     * }
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required',
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->email_verification_code != $request->code) {
            $user->email_verification_code = null;
            $user->save();

            return response()->json([
                'success' => false,
                'message' => 'Invalid Code'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code verified successfully'
        ]);
    }

    /**
     * Reset Password
     * 
     * Reset user password after code verification
     * 
     * @param Request $request
     * @return JsonResponse
     * @bodyParam email string required User email address. Example: user@example.com
     * @bodyParam code string required Verification code. Example: 123456
     * @bodyParam password string required New password (min 8 characters). Example: newpassword123
     * @bodyParam password_confirmation string required Password confirmation. Example: newpassword123
     * @response 200 {
     *   "success": true,
     *   "message": "Successfully Reset Your Password"
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid verification code"
     * }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->email_verification_code != $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 400);
        }

        $user->password = bcrypt($request->password);
        $user->email_verification_code = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Successfully Reset Your Password'
        ]);
    }
}
