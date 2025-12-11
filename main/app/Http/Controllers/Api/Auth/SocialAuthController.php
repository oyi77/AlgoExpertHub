<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

/**
 * @group Authentication
 */
class SocialAuthController extends Controller
{
    /**
     * Get Social Redirect URL
     *
     * Returns the URL the frontend should redirect the user to for authentication.
     *
     * @urlParam provider string required The social provider (google, facebook). Example: google
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "url": "https://accounts.google.com/o/oauth2/auth?..."
     *   }
     * }
     */
    public function redirect($provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return response()->json(['success' => false, 'message' => 'Invalid provider'], 422);
        }

        try {
            $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
            return response()->json([
                'success' => true,
                'data' => ['url' => $url]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Social Callback
     *
     * Exchange the code from the provider for an authentication token.
     *
     * @urlParam provider string required The social provider (google, facebook). Example: google
     * @bodyParam code string required The authorization code from the provider.
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "token": "1|xxxxxxxx",
     *     "user": { ... }
     *   }
     * }
     */
    public function callback($provider, Request $request)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return response()->json(['success' => false, 'message' => 'Invalid provider'], 422);
        }

        try {
            // Note: In stateless mode, the frontend might send the 'code' in the body or query.
            // Socialite usually looks at request parameters. 
            // Ensure the frontend passes the entire query string or body parameters matching what Socialite expects.
            
            $socialUser = Socialite::driver($provider)->stateless()->user();
            
            $user = User::where('email', $socialUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'username' => $socialUser->name ?? explode('@', $socialUser->email)[0],
                    'email' => $socialUser->email,
                    'google_id' => $provider === 'google' ? $socialUser->id : null,
                    'facebook_id' => $provider === 'facebook' ? $socialUser->id : null, 
                    'password' => Hash::make(Str::random(16)),
                    'status' => 1,
                    'ev' => 1, // Email Verified
                ]);
            } else {
                 // Update ID if missing
                 if ($provider === 'google' && !$user->google_id) {
                     $user->google_id = $socialUser->id;
                     $user->save();
                 }
                 if ($provider === 'facebook' && !$user->facebook_id) {
                     $user->facebook_id = $socialUser->id;
                     $user->save();
                 }
            }

            Auth::login($user);
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'balance' => $user->balance,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 401);
        }
    }
}
