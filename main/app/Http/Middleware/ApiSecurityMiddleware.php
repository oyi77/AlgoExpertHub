<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Security\SecurityManager;
use Symfony\Component\HttpFoundation\Response;

class ApiSecurityMiddleware
{
    protected SecurityManager $securityManager;

    public function __construct(SecurityManager $securityManager)
    {
        $this->securityManager = $securityManager;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check IP blacklist
        if ($this->securityManager->isIpBlacklisted($request->ip())) {
            return response()->json([
                'message' => 'Access denied.'
            ], 403);
        }

        // Validate API request
        if (!$this->securityManager->validateApiRequest($request)) {
            return response()->json([
                'message' => 'Invalid API credentials.'
            ], 401);
        }

        return $next($request);
    }
}
