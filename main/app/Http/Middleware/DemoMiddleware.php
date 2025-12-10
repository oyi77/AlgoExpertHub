<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DemoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $mode = env('DEMO') ?? false;
        
        // If demo mode is not enabled, skip all checks
        if (!$mode) {
            return $next($request);
        }
        
        // Only block non-GET requests in demo mode
        if (request()->method() == 'GET') {
            return $next($request);
        }
        
        // Check route name and path for API-like endpoints (manual-trade, etc.)
        $routeName = $request->route()?->getName() ?? '';
        $path = $request->path();
        $isApiRoute = str_contains($routeName, 'manual-trade') 
            || str_contains($routeName, 'api.')
            || str_contains($path, '/api/')
            || str_contains($path, 'manual-trade');
        
        // Check if this is an AJAX/JSON request by checking headers explicitly
        $isAjax = $request->ajax() 
            || $request->expectsJson() 
            || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest'
            || $request->header('Accept') === 'application/json'
            || str_contains($request->header('Accept', ''), 'application/json');
        
        // For API routes or AJAX requests, return JSON instead of redirecting
        if ($isAjax || $isApiRoute) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed this action in demo mode.'
            ], 403);
        }
        
        return redirect()->back()->with('error','You are not allowed this action in demo');
    }
}
