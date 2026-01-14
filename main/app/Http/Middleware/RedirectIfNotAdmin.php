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
            $acceptHeader = $request->header('Accept', '');
            $isAjax = $request->ajax() 
                || $request->expectsJson() 
                || $request->wantsJson()
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || $request->header('Accept') === 'application/json'
                || strpos($acceptHeader, 'application/json') !== false;
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please log in.',
                    'redirect' => url('/admin/login')
                ], 401);
            }
            
            return redirect()->to('/admin/login')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        return $next($request);
    }
}
