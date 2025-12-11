<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  int  $ttl  Time to live in seconds
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, int $ttl = 300)
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Skip caching for authenticated requests with user-specific data
        if ($request->user() && !$this->isCacheableForUser($request)) {
            return $next($request);
        }

        $cacheKey = $this->generateCacheKey($request);
        
        // Check if response is cached
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            
            return response($cachedResponse['content'], $cachedResponse['status'])
                ->withHeaders($cachedResponse['headers'])
                ->header('X-Cache', 'HIT')
                ->header('X-Cache-Key', $cacheKey);
        }

        $response = $next($request);

        // Only cache successful responses
        if ($response->getStatusCode() === 200) {
            $this->cacheResponse($cacheKey, $response, $ttl);
            $response->header('X-Cache', 'MISS');
            $response->header('X-Cache-Key', $cacheKey);
        }

        return $response;
    }

    /**
     * Generate cache key for request
     */
    protected function generateCacheKey(Request $request): string
    {
        $key = 'api_response:' . md5(
            $request->getPathInfo() . 
            serialize($request->query()) . 
            ($request->user() ? $request->user()->id : 'guest')
        );

        return $key;
    }

    /**
     * Cache the response
     */
    protected function cacheResponse(string $key, $response, int $ttl): void
    {
        try {
            $cacheData = [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all()
            ];

            Cache::put($key, $cacheData, $ttl);
            
            Log::debug('Response cached', ['key' => $key, 'ttl' => $ttl]);
        } catch (\Exception $e) {
            Log::error('Failed to cache response', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determine if request is cacheable for authenticated users
     */
    protected function isCacheableForUser(Request $request): bool
    {
        $cacheableRoutes = [
            'api/plans',
            'api/markets',
            'api/currency-pairs',
            'api/time-frames',
            'api/signals/public'
        ];

        return in_array(trim($request->getPathInfo(), '/'), $cacheableRoutes);
    }
}