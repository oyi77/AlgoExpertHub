<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CacheManager
{
    protected $hitCount = 0;
    protected $missCount = 0;
    protected $tags = [];

    /**
     * Remember a value in cache with tags
     */
    public function remember(string $key, int $ttl, callable $callback, array $tags = []): mixed
    {
        $fullKey = $this->buildKey($key);
        
        if (Cache::has($fullKey)) {
            $this->hitCount++;
            return Cache::get($fullKey);
        }

        $this->missCount++;
        $value = $callback();
        
        if (!empty($tags)) {
            Cache::tags($tags)->put($fullKey, $value, $ttl);
            $this->storeTags($key, $tags);
        } else {
            Cache::put($fullKey, $value, $ttl);
        }

        return $value;
    }

    /**
     * Set cache tags for the current instance
     */
    public function tags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateByTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();
            
            // Log cache invalidation
            Log::info('Cache invalidated by tags', ['tags' => $tags]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cache hit rate
     */
    public function getHitRate(): float
    {
        $total = $this->hitCount + $this->missCount;
        return $total > 0 ? ($this->hitCount / $total) * 100 : 0;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        return [
            'hits' => $this->hitCount,
            'misses' => $this->missCount,
            'hit_rate' => $this->getHitRate(),
            'total_requests' => $this->hitCount + $this->missCount
        ];
    }

    /**
     * Warm cache for frequently accessed data
     */
    public function warmCache(): void
    {
        $this->warmPlansCache();
        $this->warmSignalsCache();
        $this->warmConfigurationCache();
        $this->warmMarketsCache();
    }

    /**
     * Warm plans cache
     */
    protected function warmPlansCache(): void
    {
        $this->remember('plans.active', 3600, function () {
            return \App\Models\Plan::where('status', 1)
                ->select('id', 'name', 'price', 'plan_type', 'duration', 'status')
                ->get();
        }, ['plans']);

        Log::info('Plans cache warmed');
    }

    /**
     * Warm signals cache
     */
    protected function warmSignalsCache(): void
    {
        $this->remember('signals.recent', 1800, function () {
            return \App\Models\Signal::published()
                ->withDisplayData()
                ->recent(50)
                ->get();
        }, ['signals']);

        $this->remember('signals.published_count', 3600, function () {
            return \App\Models\Signal::where('is_published', 1)->count();
        }, ['signals']);

        Log::info('Signals cache warmed');
    }

    /**
     * Warm configuration cache
     */
    protected function warmConfigurationCache(): void
    {
        $this->remember('configuration.main', 7200, function () {
            return \App\Models\Configuration::first();
        }, ['configuration']);

        Log::info('Configuration cache warmed');
    }

    /**
     * Warm markets cache
     */
    protected function warmMarketsCache(): void
    {
        $this->remember('markets.active', 3600, function () {
            return \App\Models\Market::where('status', 1)->get();
        }, ['markets']);

        $this->remember('currency_pairs.active', 3600, function () {
            return \App\Models\CurrencyPair::where('status', 1)->get();
        }, ['currency_pairs']);

        $this->remember('time_frames.active', 3600, function () {
            return \App\Models\TimeFrame::where('status', 1)->get();
        }, ['time_frames']);

        Log::info('Markets cache warmed');
    }

    /**
     * Cache user-specific data
     */
    public function cacheUserData(int $userId): void
    {
        // Cache user's current subscription
        $this->remember("user.{$userId}.subscription", 1800, function () use ($userId) {
            return \App\Models\PlanSubscription::where('user_id', $userId)
                ->where('is_current', 1)
                ->with('plan:id,name,price,plan_type')
                ->first();
        }, ['user_subscriptions', "user_{$userId}"]);

        // Cache user's dashboard signals
        $this->remember("user.{$userId}.dashboard_signals", 900, function () use ($userId) {
            return \App\Models\DashboardSignal::where('user_id', $userId)
                ->with(['signal' => function ($query) {
                    $query->withDisplayData();
                }])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
        }, ['dashboard_signals', "user_{$userId}"]);
    }

    /**
     * Clear user-specific cache
     */
    public function clearUserCache(int $userId): void
    {
        $this->invalidateByTags(["user_{$userId}"]);
    }

    /**
     * Build cache key with prefix
     */
    protected function buildKey(string $key): string
    {
        return config('cache.prefix', 'laravel_cache') . ':' . $key;
    }

    /**
     * Store tags for a key
     */
    protected function storeTags(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $tagKey = "tag:{$tag}";
            $keys = Cache::get($tagKey, []);
            $keys[] = $key;
            Cache::put($tagKey, array_unique($keys), 86400); // Store for 24 hours
        }
    }

    /**
     * Get memory usage statistics
     */
    public function getMemoryStats(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection('cache');
                $info = $redis->info('memory');
                
                return [
                    'used_memory' => $info['used_memory'] ?? 0,
                    'used_memory_human' => $info['used_memory_human'] ?? '0B',
                    'used_memory_peak' => $info['used_memory_peak'] ?? 0,
                    'used_memory_peak_human' => $info['used_memory_peak_human'] ?? '0B',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get cache memory stats', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Clear all cache
     */
    public function clearAll(): bool
    {
        try {
            Cache::flush();
            Log::info('All cache cleared');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear all cache', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get cache size information
     */
    public function getCacheSize(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection('cache');
                $dbsize = $redis->dbsize();
                
                return [
                    'keys_count' => $dbsize,
                    'memory_stats' => $this->getMemoryStats()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get cache size', ['error' => $e->getMessage()]);
        }

        return ['keys_count' => 0, 'memory_stats' => []];
    }
}