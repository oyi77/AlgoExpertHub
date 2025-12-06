<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowHorizonIframe
{
    /**
     * Handle an incoming request.
     * Remove X-Frame-Options header to allow iframe embedding for Horizon
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Check if this is a Horizon route
        if ($request->is('horizon*') || $request->routeIs('horizon.*')) {
            // Remove X-Frame-Options header to allow iframe embedding
            $response->headers->remove('X-Frame-Options');
            
            // Set X-Frame-Options to SAMEORIGIN to allow embedding from same origin
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        }
        
        return $response;
    }
}
