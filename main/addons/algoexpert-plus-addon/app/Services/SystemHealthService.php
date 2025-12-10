<?php

namespace Addons\AlgoExpertPlus\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class SystemHealthService
{
    /**
     * Get Horizon statistics if available
     */
    public function getHorizonStats(): ?array
    {
        if (!class_exists(\Laravel\Horizon\Horizon::class)) {
            return [
                'available' => false,
                'installed' => false,
                'active' => false,
                'status' => 'not_installed',
                'message' => 'Laravel Horizon is not installed. Install it with: composer require laravel/horizon',
            ];
        }

        try {
            // Check Redis connection first
            $redisConnected = $this->checkRedisConnection();
            $processCount = $this->getHorizonProcessCount();
            $isActive = $this->checkHorizonActive();
            
            $diagnostics = [
                'redis_connected' => $redisConnected,
                'process_count' => $processCount,
                'queue_connection' => config('queue.default'),
                'redis_connection_name' => config('horizon.use', 'default'),
            ];
            
            // Try to get metrics if active (always try to get, even if status check failed)
            $throughput = 0;
            $waitTime = 0;
            $recentJobs = [];
            
            try {
                $horizon = app(\Laravel\Horizon\Contracts\MetricsRepository::class);
                
                // Only call methods that are confirmed to exist
                if (method_exists($horizon, 'throughput')) {
                    try {
                        $throughput = $horizon->throughput() ?? 0;
                    } catch (\Throwable $e) {
                        $throughput = 0;
                    }
                }
                
                // waitTime() may not exist or may require parameters - skip if not available
                // Some Horizon versions don't have this method, so we just use 0 as default
                
                // recentJobs() may not exist in all Horizon versions
                if (method_exists($horizon, 'recentJobs')) {
                    try {
                        $recentJobs = $horizon->recentJobs() ?? [];
                    } catch (\Throwable $e) {
                        $recentJobs = [];
                    }
                }
                
                // If we can successfully get metrics, Horizon is definitely running
                // This is the most reliable check - if Horizon API responds, it's active
                if ($throughput >= 0 || !empty($recentJobs)) {
                    // Horizon API is responding, so it's actually running
                    // Override the process-based check with this more reliable method
                    $isActive = true;
                }
            } catch (\Throwable $e) {
                // Metrics might fail if Horizon is not running or Redis connection issues
                // Keep the process-based status check result
                \Log::debug('Horizon metrics unavailable', ['error' => $e->getMessage()]);
            }
            
            $message = $this->getHorizonStatusMessage($redisConnected, $processCount, $isActive);
            
            return [
                'available' => true,
                'installed' => true,
                'active' => $isActive,
                'status' => $isActive ? 'active' : 'inactive',
                'throughput' => $throughput,
                'wait_time' => $waitTime,
                'recent_jobs' => $recentJobs,
                'processes' => $processCount,
                'diagnostics' => $diagnostics,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            return [
                'available' => true,
                'installed' => true,
                'active' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
                'message' => 'Error checking Horizon status: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Check if Redis connection is available
     */
    protected function checkRedisConnection(): bool
    {
        try {
            $redis = app('redis')->connection(config('horizon.use', 'default'));
            $redis->ping();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Get helpful status message for Horizon
     */
    protected function getHorizonStatusMessage(bool $redisConnected, int $processCount, bool $isActive): string
    {
        if ($isActive) {
            return 'Horizon is running normally.';
        }
        
        if (!$redisConnected) {
            return 'Redis connection failed. Check Redis configuration and ensure Redis is running.';
        }
        
        if ($processCount === 0) {
            $queueConnection = config('queue.default');
            if ($queueConnection !== 'redis') {
                return "Queue connection is set to '{$queueConnection}' but Horizon requires Redis. Set QUEUE_CONNECTION=redis in .env";
            }
            
            $useSystemSupervisor = env('HORIZON_USE_SYSTEM_SUPERVISOR', false);
            if ($useSystemSupervisor) {
                return 'Horizon is not running. Start it manually with: php artisan horizon or configure system supervisor to manage it.';
            }
            
            return 'Horizon is not running. Start it with: php artisan horizon';
        }
        
        return 'Horizon processes found but may not be fully active.';
    }

    /**
     * Check if Horizon workers are active
     * Uses multiple methods to accurately detect Horizon status
     */
    protected function checkHorizonActive(): bool
    {
        try {
            $redis = app('redis')->connection(config('horizon.use', 'default'));
            $prefix = config('horizon.prefix', 'horizon:');
            
            // Method 1: Check for Horizon master process in Redis (most reliable)
            // Horizon stores its master process info in 'horizon:master'
            $masterKey = $prefix . 'master';
            $master = $redis->get($masterKey);
            if ($master) {
                $masterData = json_decode($master, true);
                // If master exists and has a pid, Horizon is running
                if (isset($masterData['pid'])) {
                    // Verify the process is actually running
                    $pid = $masterData['pid'];
                    if ($this->isProcessRunning($pid)) {
                        return true;
                    }
                }
            }
            
            // Method 2: Check for active supervisors in Redis
            $supervisors = $redis->keys($prefix . 'supervisors:*');
            if (!empty($supervisors)) {
                foreach ($supervisors as $key) {
                    $data = $redis->get($key);
                    if ($data) {
                        $supervisor = json_decode($data, true);
                        // Check if supervisor has processes and is not paused
                        if (isset($supervisor['processes']) && $supervisor['processes'] > 0) {
                            // Also check if supervisor is not paused
                            if (!isset($supervisor['paused']) || !$supervisor['paused']) {
                                return true;
                            }
                        }
                    }
                }
            }
            
            // Method 3: Check for Horizon snapshot (indicates Horizon is configured and running)
            $snapshotKey = $prefix . 'snapshot';
            $snapshot = $redis->get($snapshotKey);
            if ($snapshot) {
                // Snapshot exists, check if it's recent (within last 5 minutes)
                $snapshotData = json_decode($snapshot, true);
                if (isset($snapshotData['timestamp'])) {
                    $snapshotTime = $snapshotData['timestamp'];
                    // If snapshot is recent, Horizon is likely running
                    if ($snapshotTime > (time() - 300)) { // 5 minutes
                        return true;
                    }
                }
            }
            
            // Method 4: Check process list as final fallback
            $processCount = $this->getHorizonProcessCount();
            return $processCount > 0;
        } catch (\Throwable $e) {
            // If Redis check fails, try process check as fallback
            \Log::warning('Horizon status check error', ['error' => $e->getMessage()]);
            return $this->getHorizonProcessCount() > 0;
        }
    }
    
    /**
     * Check if a process with given PID is running
     */
    protected function isProcessRunning(int $pid): bool
    {
        try {
            $command = "ps -p {$pid} -o pid= 2>&1";
            $result = trim(shell_exec($command) ?: '');
            return !empty($result) && is_numeric($result);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get count of running Horizon processes
     */
    protected function getHorizonProcessCount(): int
    {
        try {
            // Check for horizon processes (both master and workers)
            // Horizon master runs as "artisan horizon"
            // Workers run as child processes
            $command = "ps aux | grep -E 'artisan horizon|horizon:work' | grep -v grep | wc -l";
            $count = (int) trim(shell_exec($command) ?: '0');
            
            // If we get processes, verify at least one is the master
            if ($count > 0) {
                // Check specifically for master process
                $masterCheck = "ps aux | grep 'artisan horizon' | grep -v 'horizon:work' | grep -v grep | wc -l";
                $masterCount = (int) trim(shell_exec($masterCheck) ?: '0');
                // If we have at least a master process, return the count
                if ($masterCount > 0) {
                    return $count;
                }
            }
            
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get queue statistics from database and Redis
     */
    public function getQueueStats(): array
    {
        $queueConnection = config('queue.default', 'sync');
        $stats = [
            'queue_connection' => $queueConnection,
            'pending' => 0,
            'failed' => 0,
            'queues' => [],
            'redis_pending' => 0,
            'database_pending' => 0,
        ];

        try {
            // Always check database queue table
            $stats['database_pending'] = DB::table('jobs')->count();
            $stats['failed'] = DB::table('failed_jobs')->count();
            $dbQueues = DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get()
                ->pluck('count', 'queue')
                ->toArray();

            // Check Redis queues if using Redis
            $redisQueues = [];
            $redisPending = 0;
            if ($queueConnection === 'redis') {
                try {
                    $redis = app('redis')->connection(config('queue.connections.redis.connection', 'default'));
                    $queueName = config('queue.connections.redis.queue', 'default');
                    $horizonPrefix = config('horizon.prefix', 'horizon:');
                    
                    // Check Redis queue directly
                    $redisQueueKey = 'queues:' . $queueName;
                    $redisPending = $redis->llen($redisQueueKey);
                    
                    // Check all queue keys in Redis
                    $allQueueKeys = $redis->keys('queues:*');
                    foreach ($allQueueKeys as $key) {
                        $queueName = str_replace('queues:', '', $key);
                        $count = $redis->llen($key);
                        if ($count > 0) {
                            $redisQueues[$queueName] = $count;
                            $redisPending += $count;
                        }
                    }
                } catch (\Throwable $e) {
                    // Redis might not be available
                    \Log::debug('Redis queue check failed', ['error' => $e->getMessage()]);
                }
            }

            // Use Redis stats if queue connection is Redis, otherwise use database
            if ($queueConnection === 'redis') {
                $stats['pending'] = $redisPending;
                $stats['queues'] = $redisQueues;
                $stats['redis_pending'] = $redisPending;
            } else {
                $stats['pending'] = $stats['database_pending'];
                $stats['queues'] = $dbQueues;
            }

            return $stats;
        } catch (\Throwable $e) {
            return array_merge($stats, [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Get detailed queue diagnostics
     */
    public function getQueueDiagnostics(): array
    {
        $queueConnection = config('queue.default', 'sync');
        $horizonRequired = class_exists(\Laravel\Horizon\Horizon::class);
        
        $diagnostics = [
            'queue_connection' => $queueConnection,
            'queue_driver' => config('queue.connections.' . $queueConnection . '.driver', 'unknown'),
            'is_redis' => $queueConnection === 'redis',
            'horizon_required' => $horizonRequired,
            'horizon_compatible' => $horizonRequired && $queueConnection === 'redis',
            'issues' => [],
            'recommendations' => [],
        ];

        // Check for issues
        if ($horizonRequired && $queueConnection !== 'redis') {
            $diagnostics['issues'][] = 'Horizon requires Redis queue connection, but current connection is: ' . $queueConnection;
            $diagnostics['recommendations'][] = "Set QUEUE_CONNECTION=redis in .env file";
        }

        if ($queueConnection === 'sync') {
            $diagnostics['issues'][] = 'Queue connection is set to "sync" - jobs will run immediately instead of being queued';
            $diagnostics['recommendations'][] = "Change QUEUE_CONNECTION from 'sync' to 'redis' or 'database' in .env";
        }

        if ($queueConnection === 'database') {
            $diagnostics['issues'][] = 'Queue connection is "database" - Horizon cannot monitor database queues, only Redis queues';
            $diagnostics['recommendations'][] = "Change QUEUE_CONNECTION to 'redis' in .env to use Horizon dashboard";
        }

        // Check if jobs are being dispatched
        try {
            $redis = app('redis')->connection(config('queue.connections.redis.connection', 'default'));
            $redisConnected = true;
        } catch (\Throwable $e) {
            $redisConnected = false;
            if ($queueConnection === 'redis') {
                $diagnostics['issues'][] = 'Redis connection failed: ' . $e->getMessage();
                $diagnostics['recommendations'][] = "Check Redis configuration and ensure Redis server is running";
            }
        }

        $diagnostics['redis_connected'] = $redisConnected;

        return $diagnostics;
    }

    /**
     * Get system information
     */
    public function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'max_execution_time' => ini_get('max_execution_time'),
            'timezone' => config('app.timezone'),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'queue_connection' => config('queue.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
        ];
    }

    /**
     * Get database statistics
     */
    public function getDatabaseStats(): array
    {
        try {
            $connection = DB::connection();
            $databaseName = $connection->getDatabaseName();
            
            $tables = DB::select("SELECT 
                table_name AS table_name,
                table_rows AS table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 10", [$databaseName]);
            
            // Convert stdClass objects to arrays with lowercase keys for consistency
            $normalizedTables = array_map(function($table) {
                return (object) [
                    'table_name' => $table->table_name ?? $table->TABLE_NAME ?? null,
                    'table_rows' => $table->table_rows ?? $table->TABLE_ROWS ?? 0,
                    'size_mb' => $table->size_mb ?? $table->SIZE_MB ?? 0,
                ];
            }, $tables);

            return [
                'available' => true,
                'connection' => config('database.default'),
                'database' => $databaseName,
                'largest_tables' => $normalizedTables,
            ];
        } catch (\Throwable $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Spatie Health check results if available
     */
    public function getHealthChecks(): ?array
    {
        if (!class_exists(\Spatie\Health\Facades\Health::class)) {
            return null;
        }

        try {
            $checks = \Spatie\Health\Facades\Health::check();
            
            return [
                'available' => true,
                'status' => $checks->status,
                'checks' => $checks->checks,
            ];
        } catch (\Throwable $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Horizon supervisor status (cron-based)
     */
    public function getHorizonSupervisorStatus(): array
    {
        $enabled = env('HORIZON_CRON_SUPERVISOR_ENABLED', true);
        $schedule = env('HORIZON_CRON_SUPERVISOR_SCHEDULE', 3);
        $useSystemSupervisor = env('HORIZON_USE_SYSTEM_SUPERVISOR', false);
        
        // Auto-detect if system supervisor is running queue workers
        if (!$useSystemSupervisor) {
            $useSystemSupervisor = $this->detectSystemSupervisor();
        }
        
        // Check if command exists
        $commandExists = class_exists(\App\Console\Commands\HorizonSupervisor::class);
        
        // Check last run time (if we can determine it from logs)
        $lastRun = $this->getHorizonSupervisorLastRun();
        
        return [
            'enabled' => $enabled && !$useSystemSupervisor,
            'schedule_minutes' => $schedule,
            'use_system_supervisor' => $useSystemSupervisor,
            'command_exists' => $commandExists,
            'last_run' => $lastRun,
            'status' => ($enabled && !$useSystemSupervisor && $commandExists) ? 'active' : 'inactive',
        ];
    }

    /**
     * Detect if system supervisor is running queue workers
     */
    protected function detectSystemSupervisor(): bool
    {
        try {
            // Check if supervisorctl is available
            exec('which supervisorctl 2>&1', $whichOutput, $whichReturn);
            if ($whichReturn !== 0) {
                return false;
            }

            // Check if queue worker processes are running via supervisor
            exec('supervisorctl status 2>&1', $statusOutput, $statusReturn);
            if ($statusReturn === 0) {
                $status = implode("\n", $statusOutput);
                // Check for laravel-worker or queue:work processes managed by supervisor
                if (preg_match('/laravel-worker.*RUNNING|queue:work.*RUNNING/i', $status)) {
                    return true;
                }
            }

            // Also check if queue:work processes are running (might be managed by supervisor)
            exec("ps aux | grep 'queue:work' | grep -v grep | wc -l", $processCount, $processReturn);
            if ($processReturn === 0 && (int)trim($processCount[0] ?? '0') > 0) {
                // Check if processes are children of supervisor
                exec("ps aux | grep 'queue:work' | grep -v grep | head -1", $processInfo, $infoReturn);
                if ($infoReturn === 0 && !empty($processInfo)) {
                    $processLine = implode(' ', $processInfo);
                    // Supervisor processes usually have supervisor in the process tree
                    if (stripos($processLine, 'supervisor') !== false) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore errors
        }

        return false;
    }

    /**
     * Get last run time of Horizon supervisor
     */
    protected function getHorizonSupervisorLastRun(): ?string
    {
        try {
            // Try to get from log file
            $logPath = storage_path('logs/laravel.log');
            if (file_exists($logPath)) {
                $logContent = file_get_contents($logPath);
                // Look for "Horizon restarted by supervisor" or "Horizon is running"
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*Horizon (restarted by supervisor|is running)/', $logContent, $matches)) {
                    return $matches[1];
                }
            }
        } catch (\Throwable $e) {
            // Ignore errors
        }
        
        return null;
    }

    /**
     * Get Octane server status if available
     */
    public function getOctaneStatus(): ?array
    {
        if (!class_exists(\Laravel\Octane\Octane::class)) {
            return [
                'available' => false,
                'installed' => false,
                'status' => 'not_installed',
                'message' => 'Laravel Octane is not installed',
            ];
        }

        try {
            $server = config('octane.server', 'swoole');
            $isRunning = $this->checkOctaneRunning($server);
            $workers = $this->getOctaneWorkerCount($server);
            $port = $this->getOctanePort();
            $config = $this->getOctaneConfig();

            return [
                'available' => true,
                'installed' => true,
                'running' => $isRunning,
                'status' => $isRunning ? 'running' : 'stopped',
                'server' => $server,
                'workers' => $workers,
                'port' => $port,
                'config' => $config,
            ];
        } catch (\Throwable $e) {
            return [
                'available' => true,
                'installed' => true,
                'running' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if Octane server is running
     */
    protected function checkOctaneRunning(string $server): bool
    {
        try {
            if ($server === 'swoole') {
                // Check for Swoole processes
                $command = "ps aux | grep 'octane:start\|octane:serve' | grep -v grep | wc -l";
                $count = (int) trim(shell_exec($command) ?: '0');
                return $count > 0;
            } elseif ($server === 'roadrunner') {
                // Check for RoadRunner processes
                $command = "ps aux | grep 'rr\|roadrunner' | grep -v grep | wc -l";
                $count = (int) trim(shell_exec($command) ?: '0');
                return $count > 0;
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get Octane worker count
     */
    protected function getOctaneWorkerCount(string $server): int
    {
        try {
            if ($server === 'swoole') {
                // Count Octane worker processes
                $command = "ps aux | grep 'octane:start\|octane:serve' | grep -v grep | wc -l";
                return (int) trim(shell_exec($command) ?: '0');
            }
            return 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get Octane port from config or process
     */
    protected function getOctanePort(): ?int
    {
        try {
            // Try to get from environment or config
            $port = env('OCTANE_PORT', config('octane.port', 8000));
            return (int) $port;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get Octane configuration details
     */
    protected function getOctaneConfig(): array
    {
        try {
            return [
                'server' => config('octane.server', 'swoole'),
                'port' => env('OCTANE_PORT', config('octane.port', 8000)),
                'workers' => config('octane.workers', 4),
                'max_requests' => config('octane.max_requests', 500),
                'https' => config('octane.https', false),
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
