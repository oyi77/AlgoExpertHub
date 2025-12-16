<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class RateLimiter
{
    protected string $driver;

    public function __construct()
    {
        $this->driver = config('cache.default', 'redis');
    }

    /**
     * Attempt to perform an action within rate limit
     */
    public function attempt(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = (int) Cache::get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        Cache::put($cacheKey, $attempts + 1, $decayMinutes * 60);
        return true;
    }

    /**
     * Get remaining attempts
     */
    public function remaining(string $key): int
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = (int) Cache::get($cacheKey, 0);
        $maxAttempts = $this->getMaxAttempts($key);

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Reset rate limit for a key
     */
    public function reset(string $key): void
    {
        $cacheKey = $this->getCacheKey($key);
        Cache::forget($cacheKey);
    }

    /**
     * Get usage metrics for a key
     */
    public function getUsageMetrics(string $key): array
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = (int) Cache::get($cacheKey, 0);
        $maxAttempts = $this->getMaxAttempts($key);
        $ttl = Cache::get($cacheKey . ':ttl');

        return [
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts,
            'remaining' => max(0, $maxAttempts - $attempts),
            'reset_at' => $ttl ? Carbon::now()->addSeconds($ttl) : null,
            'percentage_used' => $maxAttempts > 0 ? ($attempts / $maxAttempts) * 100 : 0
        ];
    }

    /**
     * Check if rate limit is exceeded
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = (int) Cache::get($cacheKey, 0);

        return $attempts >= $maxAttempts;
    }

    /**
     * Hit the rate limiter (increment counter)
     */
    public function hit(string $key, int $decayMinutes = 1): int
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = (int) Cache::get($cacheKey, 0) + 1;

        Cache::put($cacheKey, $attempts, $decayMinutes * 60);
        Cache::put($cacheKey . ':ttl', $decayMinutes * 60, $decayMinutes * 60);

        return $attempts;
    }

    /**
     * Get available rate limit for a key
     */
    public function availableIn(string $key): int
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = Cache::get($cacheKey . ':ttl', 0);

        return max(0, (int) $ttl);
    }

    /**
     * Clear all rate limits
     */
    public function clear(string $prefix = ''): void
    {
        $pattern = $this->getCacheKey($prefix . '*');
        
        if ($this->driver === 'redis') {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        } else {
            // For non-Redis drivers, we can't easily clear by pattern
            Cache::flush();
        }
    }

    /**
     * Get rate limit for API endpoint
     */
    public function forApi(string $endpoint, string $identifier): string
    {
        return "api:ratelimit:{$endpoint}:{$identifier}";
    }

    /**
     * Get rate limit for login attempts
     */
    public function forLogin(string $identifier): string
    {
        return "login:ratelimit:{$identifier}";
    }

    /**
     * Get rate limit for password reset
     */
    public function forPasswordReset(string $identifier): string
    {
        return "password:ratelimit:{$identifier}";
    }

    /**
     * Get cache key with prefix
     */
    protected function getCacheKey(string $key): string
    {
        return 'ratelimit:' . $key;
    }

    /**
     * Get max attempts for a key (default configuration)
     */
    protected function getMaxAttempts(string $key): int
    {
        // Default limits based on key type
        if (str_contains($key, 'api:')) {
            return config('ratelimit.api', 60);
        }

        if (str_contains($key, 'login:')) {
            return config('ratelimit.login', 5);
        }

        if (str_contains($key, 'password:')) {
            return config('ratelimit.password', 3);
        }

        return config('ratelimit.default', 60);
    }
}
