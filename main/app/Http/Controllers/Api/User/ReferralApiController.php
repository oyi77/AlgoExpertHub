<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Referrals
 *
 * Endpoints for referral system.
 */
class ReferralApiController extends Controller
{
    /**
     * Get Referral Info
     */
    public function index()
    {
        $user = Auth::user();
        
        $data = [
            'referral_link' => route('user.register', ['reffer' => $user->username ?? $user->id]),
            'total_referred' => User::where('ref_id', $user->id)->count(),
            'total_earnings' => $user->transactions()->where('details', 'like', '%referral%')->sum('amount'),
            'referred_users' => User::where('ref_id', $user->id)->select('id', 'name', 'email', 'created_at')->latest()->get(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get Referral Stats
     */
    public function stats()
    {
        $user = Auth::user();
        
        $stats = [
            'total_referrals' => User::where('ref_id', $user->id)->count(),
            'active_referrals' => User::where('ref_id', $user->id)->where('status', 1)->count(),
            'total_commission' => $user->transactions()->where('details', 'like', '%commission%')->sum('amount'),
            'this_month_commission' => $user->transactions()
                ->where('details', 'like', '%commission%')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }
}
