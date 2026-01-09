<?php

namespace App\Services\Monitoring;

use App\Services\Monitoring\SystemMonitor;
use App\Services\Monitoring\WorkerManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AlertManager Service
 * 
 * Monitors metrics against configured thresholds and generates alerts.
 */
class AlertManager
{
    protected SystemMonitor $systemMonitor;
    protected WorkerManager $workerManager;

    public function __construct(SystemMonitor $systemMonitor, WorkerManager $workerManager)
    {
        $this->systemMonitor = $systemMonitor;
        $this->workerManager = $workerManager;
    }

    /**
     * Get active alerts
     * 
     * @return array
     */
    public function getActiveAlerts(): array
    {
        // Check cache first
        $cached = Cache::get('monitoring:alerts');
        if ($cached !== null) {
            return $cached;
        }

        // Generate new alerts
        $alerts = $this->checkThresholds();
        
        // Cache alerts for 1 hour
        Cache::put('monitoring:alerts', $alerts, config('monitoring.alert_cache_ttl', 3600));
        
        return $alerts;
    }

    /**
     * Check all thresholds and generate alerts
     * 
     * @return array
     */
    protected function checkThresholds(): array
    {
        $alerts = [];
        $metrics = $this->systemMonitor->collectMetrics();
        $workers = $this->workerManager->getAllWorkers();
        
        // Check CPU thresholds
        $cpuLoad = $metrics['system']['cpu_load_1m'] ?? 0;
        if ($cpuLoad >= config('monitoring.thresholds.cpu_critical', 4.0)) {
            $alerts[] = $this->createAlert(
                'cpu',
                'critical',
                "CPU load exceeds critical threshold",
                $cpuLoad,
                config('monitoring.thresholds.cpu_critical', 4.0)
            );
        } elseif ($cpuLoad >= config('monitoring.thresholds.cpu_warning', 2.5)) {
            $alerts[] = $this->createAlert(
                'cpu',
                'warning',
                "CPU load exceeds warning threshold",
                $cpuLoad,
                config('monitoring.thresholds.cpu_warning', 2.5)
            );
        }

        // Check memory thresholds
        $memoryPercent = $metrics['system']['memory_usage_percent'] ?? 0;
        if ($memoryPercent >= config('monitoring.thresholds.memory_critical', 90)) {
            $alerts[] = $this->createAlert(
                'memory',
                'critical',
                "Memory usage exceeds critical threshold",
                $memoryPercent,
                config('monitoring.thresholds.memory_critical', 90)
            );
        } elseif ($memoryPercent >= config('monitoring.thresholds.memory_warning', 85)) {
            $alerts[] = $this->createAlert(
                'memory',
                'warning',
                "Memory usage exceeds warning threshold",
                $memoryPercent,
                config('monitoring.thresholds.memory_warning', 85)
            );
        }

        // Check disk thresholds
        $diskPercent = $metrics['system']['disk_usage_percent'] ?? 0;
        if ($diskPercent >= config('monitoring.thresholds.disk_critical', 90)) {
            $alerts[] = $this->createAlert(
                'disk',
                'critical',
                "Disk usage exceeds critical threshold",
                $diskPercent,
                config('monitoring.thresholds.disk_critical', 90)
            );
        } elseif ($diskPercent >= config('monitoring.thresholds.disk_warning', 80)) {
            $alerts[] = $this->createAlert(
                'disk',
                'warning',
                "Disk usage exceeds warning threshold",
                $diskPercent,
                config('monitoring.thresholds.disk_warning', 80)
            );
        }

        // Check slow queries
        $slowQueries = $metrics['database']['slow_queries'] ?? 0;
        if ($slowQueries >= config('monitoring.thresholds.slow_queries_critical', 50)) {
            $alerts[] = $this->createAlert(
                'slow_queries',
                'critical',
                "High number of slow database queries",
                $slowQueries,
                config('monitoring.thresholds.slow_queries_critical', 50)
            );
        } elseif ($slowQueries >= config('monitoring.thresholds.slow_queries_warning', 20)) {
            $alerts[] = $this->createAlert(
                'slow_queries',
                'warning',
                "Elevated number of slow database queries",
                $slowQueries,
                config('monitoring.thresholds.slow_queries_warning', 20)
            );
        }

        // Check failed jobs
        $failedJobs = $workers['queue']['failed_jobs'] ?? 0;
        if ($failedJobs >= config('monitoring.thresholds.failed_jobs_critical', 200)) {
            $alerts[] = $this->createAlert(
                'failed_jobs',
                'critical',
                "High number of failed jobs",
                $failedJobs,
                config('monitoring.thresholds.failed_jobs_critical', 200)
            );
        } elseif ($failedJobs >= config('monitoring.thresholds.failed_jobs_warning', 100)) {
            $alerts[] = $this->createAlert(
                'failed_jobs',
                'warning',
                "Elevated number of failed jobs",
                $failedJobs,
                config('monitoring.thresholds.failed_jobs_warning', 100)
            );
        }

        // Check cache hit rate (low hit rate = warning)
        $cacheHitRate = $metrics['cache']['hit_rate'] ?? 0;
        if ($cacheHitRate <= config('monitoring.thresholds.cache_hit_rate_critical', 40)) {
            $alerts[] = $this->createAlert(
                'cache_hit_rate',
                'critical',
                "Cache hit rate is critically low",
                $cacheHitRate,
                config('monitoring.thresholds.cache_hit_rate_critical', 40)
            );
        } elseif ($cacheHitRate <= config('monitoring.thresholds.cache_hit_rate_warning', 60)) {
            $alerts[] = $this->createAlert(
                'cache_hit_rate',
                'warning',
                "Cache hit rate is below optimal",
                $cacheHitRate,
                config('monitoring.thresholds.cache_hit_rate_warning', 60)
            );
        }

        // Check database connections
        $dbConnections = $metrics['database']['active_connections'] ?? 0;
        if ($dbConnections >= config('monitoring.thresholds.db_connections_critical', 80)) {
            $alerts[] = $this->createAlert(
                'db_connections',
                'critical',
                "Database connection count is critically high",
                $dbConnections,
                config('monitoring.thresholds.db_connections_critical', 80)
            );
        } elseif ($dbConnections >= config('monitoring.thresholds.db_connections_warning', 60)) {
            $alerts[] = $this->createAlert(
                'db_connections',
                'warning',
                "Database connection count is elevated",
                $dbConnections,
                config('monitoring.thresholds.db_connections_warning', 60)
            );
        }

        return $alerts;
    }

    /**
     * Create an alert
     * 
     * @param string $type
     * @param string $severity critical|warning|info
     * @param string $message
     * @param float $value
     * @param float $threshold
     * @return array
     */
    protected function createAlert(string $type, string $severity, string $message, float $value, float $threshold): array
    {
        return [
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'value' => round($value, 2),
            'threshold' => $threshold,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Clear cached alerts (force refresh)
     * 
     * @return void
     */
    public function clearAlerts(): void
    {
        Cache::forget('monitoring:alerts');
    }
}

