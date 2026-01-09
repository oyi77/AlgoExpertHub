<?php

namespace App\Services\Monitoring;

use App\Services\CacheManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * SystemMonitor Service
 * 
 * Collects system health metrics including CPU, memory, disk usage,
 * database performance, cache statistics, and worker status.
 */
class SystemMonitor
{
    protected CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Collect all system metrics
     * 
     * @return array
     */
    public function collectMetrics(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'system' => $this->getSystemMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
        ];
    }

    /**
     * Get system metrics (CPU, memory, disk)
     * 
     * @return array
     */
    protected function getSystemMetrics(): array
    {
        $metrics = [
            'cpu_load_1m' => $this->getCpuLoad(1),
            'cpu_load_5m' => $this->getCpuLoad(5),
            'cpu_load_15m' => $this->getCpuLoad(15),
            'memory_usage_mb' => $this->getMemoryUsage(),
            'memory_peak_mb' => $this->getMemoryPeak(),
            'memory_usage_percent' => $this->getMemoryUsagePercent(),
            'disk_usage_percent' => $this->getDiskUsage(),
        ];

        return $metrics;
    }

    /**
     * Get CPU load average
     * 
     * @param int $minutes 1, 5, or 15
     * @return float
     */
    protected function getCpuLoad(int $minutes): float
    {
        try {
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                return match($minutes) {
                    1 => $load[0] ?? 0.0,
                    5 => $load[1] ?? 0.0,
                    15 => $load[2] ?? 0.0,
                    default => 0.0,
                };
            }

            // Fallback: Try reading from /proc/loadavg on Linux
            if (file_exists('/proc/loadavg')) {
                $load = file_get_contents('/proc/loadavg');
                $parts = explode(' ', $load);
                return match($minutes) {
                    1 => (float)($parts[0] ?? 0),
                    5 => (float)($parts[1] ?? 0),
                    15 => (float)($parts[2] ?? 0),
                    default => 0.0,
                };
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get CPU load', ['error' => $e->getMessage()]);
        }

        return 0.0;
    }

    /**
     * Get current memory usage in MB
     * 
     * @return float
     */
    protected function getMemoryUsage(): float
    {
        try {
            return round(memory_get_usage(true) / 1024 / 1024, 2);
        } catch (\Exception $e) {
            Log::warning('Failed to get memory usage', ['error' => $e->getMessage()]);
            return 0.0;
        }
    }

    /**
     * Get peak memory usage in MB
     * 
     * @return float
     */
    protected function getMemoryPeak(): float
    {
        try {
            return round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        } catch (\Exception $e) {
            Log::warning('Failed to get peak memory', ['error' => $e->getMessage()]);
            return 0.0;
        }
    }

    /**
     * Get memory usage percentage
     * 
     * @return float
     */
    protected function getMemoryUsagePercent(): float
    {
        try {
            $used = memory_get_usage(true);
            $limit = $this->getMemoryLimit();
            
            if ($limit > 0) {
                return round(($used / $limit) * 100, 2);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to calculate memory percentage', ['error' => $e->getMessage()]);
        }

        return 0.0;
    }

    /**
     * Get PHP memory limit in bytes
     * 
     * @return int
     */
    protected function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit == -1) {
            // Unlimited memory
            return 0;
        }

        // Convert to bytes
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int)$limit;

        return match($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Get disk usage percentage
     * 
     * @return float
     */
    protected function getDiskUsage(): float
    {
        try {
            $path = base_path();
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            
            if ($total > 0) {
                $used = $total - $free;
                return round(($used / $total) * 100, 2);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get disk usage', ['error' => $e->getMessage()]);
        }

        return 0.0;
    }

    /**
     * Get database metrics
     * 
     * @return array
     */
    protected function getDatabaseMetrics(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            
            // Get active connections
            $activeConnections = DB::selectOne("SHOW STATUS WHERE Variable_name = 'Threads_connected'");
            $connections = $activeConnections->Value ?? 0;

            // Get slow queries count (queries > 100ms)
            $slowQueries = DB::selectOne("SHOW STATUS WHERE Variable_name = 'Slow_queries'");
            $slowCount = $slowQueries->Value ?? 0;

            return [
                'active_connections' => (int)$connections,
                'slow_queries' => (int)$slowCount,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get database metrics', ['error' => $e->getMessage()]);
            return [
                'active_connections' => 0,
                'slow_queries' => 0,
            ];
        }
    }

    /**
     * Get cache metrics
     * 
     * @return array
     */
    protected function getCacheMetrics(): array
    {
        try {
            $stats = $this->cacheManager->getStats();
            $memoryStats = $this->cacheManager->getMemoryStats();
            
            return [
                'hit_rate' => round($stats['hit_rate'] ?? 0, 2),
                'hits' => $stats['hits'] ?? 0,
                'misses' => $stats['misses'] ?? 0,
                'memory_mb' => isset($memoryStats['used_memory']) 
                    ? round($memoryStats['used_memory'] / 1024 / 1024, 2) 
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get cache metrics', ['error' => $e->getMessage()]);
            return [
                'hit_rate' => 0,
                'hits' => 0,
                'misses' => 0,
                'memory_mb' => 0,
            ];
        }
    }

    /**
     * Get Octane status and metrics
     * 
     * @return array
     */
    public function getOctaneStatus(): array
    {
        // Check if Octane is installed
        if (!class_exists(\Laravel\Octane\Octane::class)) {
            return [
                'status' => 'not_installed',
                'message' => 'Laravel Octane is not installed',
            ];
        }

        // Check if Octane server is running
        try {
            $process = shell_exec('ps aux | grep "octane:start" | grep -v grep');
            
            if (empty($process)) {
                return [
                    'status' => 'not_running',
                    'message' => 'Laravel Octane is installed but not running',
                ];
            }

            // Parse Octane process info
            $lines = explode("\n", trim($process));
            $workerCount = count(array_filter($lines));
            
            // Try to get memory usage from process
            $memoryMb = 0;
            foreach ($lines as $line) {
                if (preg_match('/\s+(\d+)\s+/', $line, $matches)) {
                    $pid = $matches[1];
                    $memInfo = shell_exec("ps -p {$pid} -o rss= 2>/dev/null");
                    if ($memInfo) {
                        $memoryMb += round(trim($memInfo) / 1024, 2);
                    }
                }
            }

            return [
                'status' => 'running',
                'workers' => $workerCount,
                'memory_mb' => $memoryMb,
                'message' => "Octane running with {$workerCount} worker(s)",
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get Octane status', ['error' => $e->getMessage()]);
            return [
                'status' => 'unknown',
                'message' => 'Unable to determine Octane status',
            ];
        }
    }

    /**
     * Get bot worker metrics (if trading bot addon is active)
     * 
     * @return array
     */
    public function getBotWorkers(): array
    {
        // Check if trading bot addon is active
        if (!class_exists(\App\Support\AddonRegistry::class)) {
            return [
                'status' => 'addon_not_available',
                'active' => 0,
                'total' => 0,
            ];
        }

        try {
            $isActive = \App\Support\AddonRegistry::active('trading-management-addon');
            
            if (!$isActive) {
                return [
                    'status' => 'addon_inactive',
                    'active' => 0,
                    'total' => 0,
                ];
            }

            // Check if TradingBotMonitoringService exists
            if (!class_exists(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService::class)) {
                return [
                    'status' => 'service_not_available',
                    'active' => 0,
                    'total' => 0,
                ];
            }

            $monitoringService = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService::class);
            
            // Get bot worker stats
            $bots = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::all();
            $activeBots = $bots->filter(function ($bot) {
                return $bot->isRunning() && $bot->worker_pid;
            });

            return [
                'status' => 'active',
                'active' => $activeBots->count(),
                'total' => $bots->count(),
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get bot workers', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'active' => 0,
                'total' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }
}

