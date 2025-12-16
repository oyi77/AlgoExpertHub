<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Security\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    protected RateLimiter $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->rateLimiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        $this->rateLimiter->hit($key, $decayMinutes);

        $response = $next($request);

        return $this->addHeaders($response, $key, $maxAttempts);
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();
        $ip = $request->ip();
        $route = $request->route();
        $routeName = $route ? $route->getName() : $request->path();
        $routeIdentifier = $routeName ?? $request->path();

        if ($user) {
            return $this->rateLimiter->forApi($routeIdentifier, "user:{$user->id}");
        }

        return $this->rateLimiter->forApi($routeIdentifier, "ip:{$ip}");
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->rateLimiter->availableIn($key);

        return response()->json([
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
            'limit' => $maxAttempts
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addHeaders(Response $response, string $key, int $maxAttempts): Response
    {
        $remaining = $this->rateLimiter->remaining($key);
        $retryAfter = $this->rateLimiter->availableIn($key);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remaining),
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp
        ]);
    }
}
