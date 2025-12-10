<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfNotAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'admin')
    {
        if (!Auth::guard($guard)->check()) {
            // Check if this is an AJAX/JSON request
            $isAjax = $request->ajax() 
                || $request->expectsJson() 
                || $request->wantsJson()
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || $request->header('Accept') === 'application/json'
                || str_contains($request->header('Accept', ''), 'application/json');
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please log in.',
                    'redirect' => route('admin.login')
                ], 401);
            }
            
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
