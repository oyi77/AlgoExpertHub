<?php

namespace App\Services\Monitoring;

use App\Services\Analytics\MetricsCollector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SystemMonitor
{
    protected MetricsCollector $metricsCollector;
    protected array $config;

    public function __construct(MetricsCollector $metricsCollector)
    {
        $this->metricsCollector = $metricsCollector;
        $this->config = config('monitoring', []);
    }

    /**
     * Collect system metrics
     */
    public function collectMetrics(): array
    {
        $metrics = [
            'timestamp' => now(),
            'response_times' => $this->getResponseTimeMetrics(),
            'error_rates' => $this->getErrorRateMetrics(),
            'resource_utilization' => $this->getResourceUtilization(),
            'database_performance' => $this->getDatabasePerformance(),
            'cache_performance' => $this->getCachePerformance(),
            'queue_health' => $this->getQueueHealth()
        ];

        // Store metrics
        foreach ($metrics as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (is_numeric($subValue)) {
                        $this->metricsCollector->gauge("system.{$key}.{$subKey}", (float)$subValue);
                    }
                }
            } elseif (is_numeric($value)) {
                $this->metricsCollector->gauge("system.{$key}", (float)$value);
            }
        }

        return $metrics;
    }

    /**
     * Get response time metrics
     */
    protected function getResponseTimeMetrics(): array
    {
        $cacheKey = 'monitoring:response_times';
        $times = Cache::get($cacheKey, []);

        if (empty($times)) {
            return [
                'avg' => 0,
                'p50' => 0,
                'p95' => 0,
                'p99' => 0,
                'max' => 0
            ];
        }

        sort($times);
        $count = count($times);

        return [
            'avg' => array_sum($times) / $count,
            'p50' => $this->percentile($times, 50),
            'p95' => $this->percentile($times, 95),
            'p99' => $this->percentile($times, 99),
            'max' => max($times)
        ];
    }

    /**
     * Record response time
     */
    public function recordResponseTime(float $milliseconds): void
    {
        $cacheKey = 'monitoring:response_times';
        $times = Cache::get($cacheKey, []);
        $times[] = $milliseconds;

        // Keep last 1000 measurements
        if (count($times) > 1000) {
            array_shift($times);
        }

        Cache::put($cacheKey, $times, 3600);
    }

    /**
     * Get error rate metrics
     */
    protected function getErrorRateMetrics(): array
    {
        $totalRequests = Cache::get('monitoring:total_requests', 0);
        $errorRequests = Cache::get('monitoring:error_requests', 0);

        return [
            'total_requests' => $totalRequests,
            'error_requests' => $errorRequests,
            'error_rate' => $totalRequests > 0 ? ($errorRequests / $totalRequests) * 100 : 0
        ];
    }

    /**
     * Record request
     */
    public function recordRequest(bool $isError = false): void
    {
        Cache::increment('monitoring:total_requests');
        if ($isError) {
            Cache::increment('monitoring:error_requests');
        }
    }

    /**
     * Get resource utilization
     */
    protected function getResourceUtilization(): array
    {
        $load = sys_getloadavg();
        
        return [
            'cpu_load_1m' => $load[0] ?? 0,
            'cpu_load_5m' => $load[1] ?? 0,
            'cpu_load_15m' => $load[2] ?? 0,
            'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024,
            'memory_peak_mb' => memory_get_peak_usage(true) / 1024 / 1024
        ];
    }

    /**
     * Get database performance
     */
    protected function getDatabasePerformance(): array
    {
        $slowQueries = Cache::get('monitoring:slow_queries', 0);
        $totalQueries = Cache::get('monitoring:total_queries', 0);

        return [
            'total_queries' => $totalQueries,
            'slow_queries' => $slowQueries,
            'slow_query_rate' => $totalQueries > 0 ? ($slowQueries / $totalQueries) * 100 : 0,
            'active_connections' => $this->getActiveConnections()
        ];
    }

    /**
     * Get active database connections
     */
    protected function getActiveConnections(): int
    {
        try {
            return DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Record query execution
     */
    public function recordQuery(float $executionTime): void
    {
        Cache::increment('monitoring:total_queries');

        // Slow query threshold: 100ms
        if ($executionTime > 100) {
            Cache::increment('monitoring:slow_queries');
            
            Log::warning('Slow query detected', [
                'execution_time' => $executionTime
            ]);
        }
    }

    /**
     * Get cache performance
     */
    protected function getCachePerformance(): array
    {
        $hits = Cache::get('monitoring:cache_hits', 0);
        $misses = Cache::get('monitoring:cache_misses', 0);
        $total = $hits + $misses;

        return [
            'hits' => $hits,
            'misses' => $misses,
            'hit_rate' => $total > 0 ? ($hits / $total) * 100 : 0
        ];
    }

    /**
     * Record cache access
     */
    public function recordCacheAccess(bool $isHit): void
    {
        if ($isHit) {
            Cache::increment('monitoring:cache_hits');
        } else {
            Cache::increment('monitoring:cache_misses');
        }
    }

    /**
     * Get queue health
     */
    protected function getQueueHealth(): array
    {
        return [
            'pending_jobs' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'processing_jobs' => $this->getProcessingJobsCount()
        ];
    }

    /**
     * Get pending jobs count
     */
    protected function getPendingJobsCount(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get failed jobs count
     */
    protected function getFailedJobsCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get processing jobs count
     */
    protected function getProcessingJobsCount(): int
    {
        try {
            return DB::table('jobs')
                ->where('reserved_at', '!=', null)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check alert thresholds
     */
    public function checkAlerts(): array
    {
        $alerts = [];
        $metrics = $this->collectMetrics();

        // Check response time
        if (($metrics['response_times']['p95'] ?? 0) > 200) {
            $alerts[] = [
                'type' => 'response_time',
                'severity' => 'warning',
                'message' => 'P95 response time exceeds 200ms',
                'value' => $metrics['response_times']['p95']
            ];
        }

        // Check error rate
        if (($metrics['error_rates']['error_rate'] ?? 0) > 5) {
            $alerts[] = [
                'type' => 'error_rate',
                'severity' => 'critical',
                'message' => 'Error rate exceeds 5%',
                'value' => $metrics['error_rates']['error_rate']
            ];
        }

        // Check CPU load
        if (($metrics['resource_utilization']['cpu_load_1m'] ?? 0) > 4) {
            $alerts[] = [
                'type' => 'cpu_load',
                'severity' => 'warning',
                'message' => 'High CPU load detected',
                'value' => $metrics['resource_utilization']['cpu_load_1m']
            ];
        }

        // Check failed jobs
        if (($metrics['queue_health']['failed_jobs'] ?? 0) > 100) {
            $alerts[] = [
                'type' => 'failed_jobs',
                'severity' => 'warning',
                'message' => 'High number of failed jobs',
                'value' => $metrics['queue_health']['failed_jobs']
            ];
        }

        return $alerts;
    }

    /**
     * Calculate percentile
     */
    protected function percentile(array $values, int $percentile): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }

        $index = ($percentile / 100) * ($count - 1);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower === $upper) {
            return $values[(int)$index];
        }

        $fraction = $index - $lower;
        return $values[(int)$lower] + ($fraction * ($values[(int)$upper] - $values[(int)$lower]));
    }

    /**
     * Reset metrics
     */
    public function resetMetrics(): void
    {
        $keys = [
            'monitoring:response_times',
            'monitoring:total_requests',
            'monitoring:error_requests',
            'monitoring:slow_queries',
            'monitoring:total_queries',
            'monitoring:cache_hits',
            'monitoring:cache_misses'
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
