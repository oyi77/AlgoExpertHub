<?php

namespace App\Http\Middleware;

use App\Models\Configuration;
use App\Helpers\NotificationHelper;
use Closure;
use Illuminate\Http\Request;

class KycMiddleware
{
    
    public function handle(Request $request, Closure $next)
    {
        $general = Configuration::first();

        if ($general->is_allow_kyc) {
            if (auth()->user()->is_kyc_verified != 1) {
                return redirect()->route('user.kyc')->with('notify', NotificationHelper::error('Please Update Kyc Information', 'Error'));
            }
        }

        return $next($request);
    }
}
