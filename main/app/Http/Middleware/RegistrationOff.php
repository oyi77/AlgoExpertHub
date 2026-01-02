<?php

namespace App\Http\Middleware;

use App\Models\Configuration;
use App\Helpers\NotificationHelper;
use Closure;
use Illuminate\Http\Request;

class RegistrationOff
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $general = Configuration::first();


        if (!$general->reg_enabled) {
            return back()->with('notify', NotificationHelper::error('System Registration Is off', 'Error'));
        }


        return $next($request);
    }
}
