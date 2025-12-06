<?php

namespace Addons\AlgoExpertPlus\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowHorizonIframe
{
    /**
     * Handle an incoming request.
     * Remove X-Frame-Options header to allow iframe embedding
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Remove X-Frame-Options header to allow iframe embedding
        $response->headers->remove('X-Frame-Options');
        
        // Set X-Frame-Options to SAMEORIGIN to allow embedding from same origin
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        
        return $response;
    }
}
