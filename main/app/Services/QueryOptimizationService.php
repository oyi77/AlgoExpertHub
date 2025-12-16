<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QueryOptimizationService
{
    /**
     * Enable query logging and monitoring
     */
    public function enableQueryMonitoring(): void
    {
        DB::listen(function ($query) {
            $this->logSlowQuery($query);
            $this->detectNPlusOneQueries($query);
        });
    }

    /**
     * Log slow queries for analysis
     */
    protected function logSlowQuery($query): void
    {
        $threshold = config('database.slow_query_threshold', 1000); // 1 second default
        
        if ($query->time > $threshold) {
            Log::warning('Slow Query Detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
                'connection' => $query->connectionName,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
        }
    }

    /**
     * Detect potential N+1 query patterns
     */
    protected function detectNPlusOneQueries($query): void
    {
        $cacheKey = 'query_pattern_' . md5($query->sql);
        $count = Cache::get($cacheKey, 0);
        
        // If same query pattern executed more than 10 times in 1 minute, flag as potential N+1
        if ($count > 10) {
            Log::warning('Potential N+1 Query Pattern Detected', [
                'sql' => $query->sql,
                'count' => $count,
                'time' => $query->time . 'ms'
            ]);
        }
        
        Cache::put($cacheKey, $count + 1, 60); // Cache for 1 minute
    }

    /**
     * Get query statistics
     */
    public function getQueryStats(): array
    {
        $queries = DB::getQueryLog();
        
        $totalQueries = count($queries);
        $totalTime = array_sum(array_column($queries, 'time'));
        $slowQueries = array_filter($queries, function ($query) {
            return $query['time'] > config('database.slow_query_threshold', 1000);
        });
        
        return [
            'total_queries' => $totalQueries,
            'total_time' => $totalTime,
            'average_time' => $totalQueries > 0 ? $totalTime / $totalQueries : 0,
            'slow_queries' => count($slowQueries),
            'queries' => $queries
        ];
    }

    /**
     * Optimize Signal queries with proper eager loading
     */
    public function getOptimizedSignals($filters = [])
    {
        $query = \App\Models\Signal::query()
            ->with([
                'pair:id,name',
                'time:id,name', 
                'market:id,name',
                'plans:id,name,status',
                'takeProfits:id,signal_id,tp_level,tp_price,is_closed'
            ]);

        // Apply filters efficiently
        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        if (isset($filters['market_id'])) {
            $query->where('market_id', $filters['market_id']);
        }

        if (isset($filters['currency_pair_id'])) {
            $query->where('currency_pair_id', $filters['currency_pair_id']);
        }

        if (isset($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (isset($filters['auto_created'])) {
            $query->where('auto_created', $filters['auto_created']);
        }

        // Order by published date for better performance with index
        $query->orderBy('published_date', 'desc');

        return $query;
    }

    /**
     * Optimize User subscription queries
     */
    public function getOptimizedUserSubscriptions($userId)
    {
        return \App\Models\PlanSubscription::query()
            ->where('user_id', $userId)
            ->with([
                'plan:id,name,price,plan_type,duration,status',
                'user:id,username,email'
            ])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get active subscriptions with optimized query
     */
    public function getActiveSubscriptions()
    {
        return \App\Models\PlanSubscription::query()
            ->where('is_current', 1)
            ->where('end_date', '>', now())
            ->with([
                'user:id,username,email,status',
                'plan:id,name,price,status'
            ]);
    }

    /**
     * Optimize dashboard signals query
     */
    public function getOptimizedDashboardSignals($userId, $limit = 20)
    {
        return \App\Models\DashboardSignal::query()
            ->where('user_id', $userId)
            ->with([
                'signal' => function ($query) {
                    $query->select('id', 'title', 'currency_pair_id', 'time_frame_id', 'market_id', 
                                  'open_price', 'sl', 'tp', 'direction', 'published_date')
                          ->with(['pair:id,name', 'time:id,name', 'market:id,name']);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    /**
     * Clear query cache
     */
    public function clearQueryCache(): void
    {
        $keys = Cache::get('query_cache_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('query_cache_keys');
    }

    /**
     * Get database performance metrics
     */
    public function getDatabaseMetrics(): array
    {
        try {
            // Get MySQL performance metrics
            $processlist = DB::select('SHOW PROCESSLIST');
            $status = DB::select('SHOW STATUS LIKE "Threads_connected"');
            $slowQueries = DB::select('SHOW STATUS LIKE "Slow_queries"');
            
            return [
                'active_connections' => count($processlist),
                'threads_connected' => $status[0]->Value ?? 0,
                'slow_queries' => $slowQueries[0]->Value ?? 0,
                'processlist' => $processlist
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get database metrics', ['error' => $e->getMessage()]);
            return [];
        }
    }
}