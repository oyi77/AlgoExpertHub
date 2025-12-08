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
            return null;
        }

        try {
            $horizon = app(\Laravel\Horizon\Contracts\MetricsRepository::class);
            
            // Check if Horizon workers are running
            $isActive = $this->checkHorizonActive();
            
            return [
                'available' => true,
                'active' => $isActive,
                'status' => $isActive ? 'active' : 'inactive',
                'throughput' => $horizon->throughput() ?? 0,
                'wait_time' => $horizon->waitTime() ?? 0,
                'recent_jobs' => $horizon->recentJobs() ?? [],
                'processes' => $this->getHorizonProcessCount(),
            ];
        } catch (\Throwable $e) {
            return [
                'available' => false,
                'active' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if Horizon workers are active
     */
    protected function checkHorizonActive(): bool
    {
        try {
            // Check Redis for Horizon supervisors
            $redis = app('redis')->connection(config('horizon.use', 'default'));
            $prefix = config('horizon.prefix', 'horizon:');
            
            // Check for active supervisors
            $supervisors = $redis->keys($prefix . 'supervisors:*');
            
            if (empty($supervisors)) {
                // Also check if horizon:snapshot is being run (indicates Horizon is configured)
                // But workers might not be running
                return false;
            }
            
            // Check if any supervisor is actually running
            foreach ($supervisors as $key) {
                $data = $redis->get($key);
                if ($data) {
                    $supervisor = json_decode($data, true);
                    // Check if supervisor has processes
                    if (isset($supervisor['processes']) && $supervisor['processes'] > 0) {
                        return true;
                    }
                }
            }
            
            return false;
        } catch (\Throwable $e) {
            // If Redis check fails, try process check as fallback
            return $this->getHorizonProcessCount() > 0;
        }
    }

    /**
     * Get count of running Horizon processes
     */
    protected function getHorizonProcessCount(): int
    {
        try {
            // Check for horizon processes
            $command = "ps aux | grep 'artisan horizon' | grep -v grep | wc -l";
            $count = (int) trim(shell_exec($command) ?: '0');
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get queue statistics from database
     */
    public function getQueueStats(): array
    {
        try {
            $stats = [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
                'queues' => DB::table('jobs')
                    ->select('queue', DB::raw('count(*) as count'))
                    ->groupBy('queue')
                    ->get()
                    ->pluck('count', 'queue')
                    ->toArray(),
            ];

            return $stats;
        } catch (\Throwable $e) {
            return [
                'pending' => 0,
                'failed' => 0,
                'queues' => [],
                'error' => $e->getMessage(),
            ];
        }
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
                table_name,
                table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 10", [$databaseName]);

            return [
                'available' => true,
                'connection' => config('database.default'),
                'database' => $databaseName,
                'largest_tables' => $tables,
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
