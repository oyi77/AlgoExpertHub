<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AlertManager
{
    protected SystemMonitor $systemMonitor;
    protected array $config;

    public function __construct(SystemMonitor $systemMonitor)
    {
        $this->systemMonitor = $systemMonitor;
        $this->config = config('monitoring', []);
    }

    /**
     * Get all active alerts
     */
    public function getActiveAlerts(): array
    {
        return Cache::remember('monitoring:alerts', 5, function () {
            return $this->checkThresholds();
        });
    }

    /**
     * Check all thresholds and generate alerts
     */
    protected function checkThresholds(): array
    {
        $alerts = [];
        $metrics = $this->systemMonitor->collectMetrics();

        // CPU Load Alert
        $cpuLoad1m = $metrics['resource_utilization']['cpu_load_1m'] ?? 0;
        $cpuThreshold = $this->config['cpu_load_threshold'] ?? 4.0;
        if ($cpuLoad1m > $cpuThreshold) {
            $alerts[] = [
                'type' => 'cpu',
                'severity' => $cpuLoad1m > ($cpuThreshold * 1.5) ? 'critical' : 'warning',
                'message' => sprintf('CPU load (1m) exceeds threshold: %.2f > %.2f', $cpuLoad1m, $cpuThreshold),
                'value' => $cpuLoad1m,
                'threshold' => $cpuThreshold,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Memory Usage Alert
        $memoryUsage = $metrics['resource_utilization']['memory_usage_mb'] ?? 0;
        $memoryThreshold = $this->config['memory_threshold'] ?? 512;
        $memoryPercent = ($memoryUsage / $memoryThreshold) * 100;
        if ($memoryPercent > 85) {
            $alerts[] = [
                'type' => 'memory',
                'severity' => $memoryPercent > 95 ? 'critical' : 'warning',
                'message' => sprintf('Memory usage exceeds 85%%: %.2f MB (%.1f%%)', $memoryUsage, $memoryPercent),
                'value' => $memoryUsage,
                'threshold' => $memoryThreshold,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Failed Jobs Alert
        $failedJobs = $metrics['queue_health']['failed_jobs'] ?? 0;
        $failedJobsThreshold = $this->config['failed_jobs_threshold'] ?? 100;
        if ($failedJobs > $failedJobsThreshold) {
            $alerts[] = [
                'type' => 'failed_jobs',
                'severity' => $failedJobs > ($failedJobsThreshold * 2) ? 'critical' : 'warning',
                'message' => sprintf('High number of failed jobs: %d > %d', $failedJobs, $failedJobsThreshold),
                'value' => $failedJobs,
                'threshold' => $failedJobsThreshold,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Cache Hit Rate Alert
        $cacheHitRate = $metrics['cache_performance']['hit_rate'] ?? 0;
        if ($cacheHitRate < 60) {
            $alerts[] = [
                'type' => 'cache_hit_rate',
                'severity' => $cacheHitRate < 40 ? 'critical' : 'warning',
                'message' => sprintf('Low cache hit rate: %.1f%% (suggest running cache warm command)', $cacheHitRate),
                'value' => $cacheHitRate,
                'threshold' => 60,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Slow Queries Alert
        $slowQueries = $metrics['database_performance']['slow_queries'] ?? 0;
        if ($slowQueries > 10) {
            $alerts[] = [
                'type' => 'slow_queries',
                'severity' => $slowQueries > 50 ? 'critical' : 'warning',
                'message' => sprintf('High number of slow queries detected: %d', $slowQueries),
                'value' => $slowQueries,
                'threshold' => 10,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Error Rate Alert
        $errorRate = $metrics['error_rates']['error_rate'] ?? 0;
        $errorRateThreshold = $this->config['error_rate_threshold'] ?? 5;
        if ($errorRate > $errorRateThreshold) {
            $alerts[] = [
                'type' => 'error_rate',
                'severity' => 'critical',
                'message' => sprintf('Error rate exceeds threshold: %.2f%% > %.2f%%', $errorRate, $errorRateThreshold),
                'value' => $errorRate,
                'threshold' => $errorRateThreshold,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Store alerts in cache for 1 hour
        Cache::put('monitoring:alerts', $alerts, 3600);

        return $alerts;
    }

    /**
     * Clear all alerts
     */
    public function clearAlerts(): void
    {
        Cache::forget('monitoring:alerts');
    }
}

