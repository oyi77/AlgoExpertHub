<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\LoginSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FAQRCode\Google2FA;

/**
 * @group Two-Factor Authentication
 *
 * Endpoints for managing 2FA settings.
 */
class TwoFactorApiController extends Controller
{
    /**
     * Get 2FA Status
     *
     * Check if 2FA is enabled for the user.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "enabled": false,
     *     "secret": null
     *   }
     * }
     */
    public function status()
    {
        $user = Auth::user();
        $loginSecurity = LoginSecurity::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $loginSecurity ? $loginSecurity->google2fa_enable : false,
                'secret' => $loginSecurity && !$loginSecurity->google2fa_enable ? $loginSecurity->google2fa_secret : null
            ]
        ]);
    }

    /**
     * Generate 2FA Secret
     *
     * Generate a new 2FA secret and QR code.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "secret": "...",
     *     "qr_code_url": "..."
     *   }
     * }
     */
    public function generateSecret()
    {
        $user = Auth::user();
        $google2fa = new Google2FA();

        $loginSecurity = LoginSecurity::firstOrNew(['user_id' => $user->id]);
        $loginSecurity->user_id = $user->id;
        $loginSecurity->google2fa_enable = false;
        $loginSecurity->google2fa_secret = $google2fa->generateSecretKey();
        $loginSecurity->save();

        $qrCodeUrl = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $loginSecurity->google2fa_secret
        );

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $loginSecurity->google2fa_secret,
                'qr_code_url' => $qrCodeUrl
            ]
        ]);
    }

    /**
     * Enable 2FA
     *
     * Enable 2FA after verifying the code.
     *
     * @bodyParam code string required 6-digit verification code. Example: 123456
     * @response 200 {
     *   "success": true,
     *   "message": "2FA enabled successfully"
     * }
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA();
        $loginSecurity = LoginSecurity::where('user_id', $user->id)->first();

        if (!$loginSecurity || !$loginSecurity->google2fa_secret) {
            return response()->json([
                'success' => false,
                'message' => 'Please generate a secret first'
            ], 400);
        }

        $valid = $google2fa->verifyKey($loginSecurity->google2fa_secret, $request->code);

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 400);
        }

        $loginSecurity->google2fa_enable = true;
        $loginSecurity->save();

        return response()->json([
            'success' => true,
            'message' => '2FA enabled successfully'
        ]);
    }

    /**
     * Disable 2FA
     *
     * Disable 2FA after verifying the code.
     *
     * @bodyParam code string required 6-digit verification code. Example: 123456
     * @response 200 {
     *   "success": true,
     *   "message": "2FA disabled successfully"
     * }
     */
    public function disable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA();
        $loginSecurity = LoginSecurity::where('user_id', $user->id)->first();

        if (!$loginSecurity || !$loginSecurity->google2fa_enable) {
            return response()->json([
                'success' => false,
                'message' => '2FA is not enabled'
            ], 400);
        }

        $valid = $google2fa->verifyKey($loginSecurity->google2fa_secret, $request->code);

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 400);
        }

        $loginSecurity->google2fa_enable = false;
        $loginSecurity->save();

        return response()->json([
            'success' => true,
            'message' => '2FA disabled successfully'
        ]);
    }

    /**
     * Verify 2FA Code
     *
     * Verify a 2FA code (used during login).
     *
     * @bodyParam code string required 6-digit verification code. Example: 123456
     * @response 200 {
     *   "success": true,
     *   "message": "Code verified successfully"
     * }
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA();
        $loginSecurity = LoginSecurity::where('user_id', $user->id)->first();

        if (!$loginSecurity || !$loginSecurity->google2fa_enable) {
            return response()->json([
                'success' => false,
                'message' => '2FA is not enabled'
            ], 400);
        }

        $valid = $google2fa->verifyKey($loginSecurity->google2fa_secret, $request->code);

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code verified successfully'
        ]);
    }
}
