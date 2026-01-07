<?php

namespace App\Services\Monitoring;

use App\Services\QueueOptimizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WorkerManager
{
    protected QueueOptimizer $queueOptimizer;
    protected SystemMonitor $systemMonitor;

    public function __construct(QueueOptimizer $queueOptimizer, SystemMonitor $systemMonitor)
    {
        $this->queueOptimizer = $queueOptimizer;
        $this->systemMonitor = $systemMonitor;
    }

    /**
     * Get all worker types status
     */
    public function getAllWorkers(): array
    {
        return [
            'queue' => $this->getQueueWorkers(),
            'bots' => $this->getBotWorkers(),
            'octane' => $this->getOctaneWorkers(),
        ];
    }

    /**
     * Get queue workers status
     */
    protected function getQueueWorkers(): array
    {
        try {
            $metrics = $this->queueOptimizer->getMetrics();
            
            return [
                'active' => $metrics['active_workers'] ?? 0,
                'total_jobs' => $metrics['total_jobs'] ?? 0,
                'pending_jobs' => $metrics['pending_jobs'] ?? 0,
                'failed_jobs' => $metrics['failed_jobs'] ?? 0,
                'processed_jobs' => $metrics['processed_jobs'] ?? 0,
                'status' => $this->getQueueWorkerStatus($metrics),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get queue workers', ['error' => $e->getMessage()]);
            return [
                'active' => 0,
                'total_jobs' => 0,
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'processed_jobs' => 0,
                'status' => 'error',
            ];
        }
    }

    /**
     * Get queue worker health status
     */
    protected function getQueueWorkerStatus(array $metrics): string
    {
        $failedJobs = $metrics['failed_jobs'] ?? 0;
        $pendingJobs = $metrics['pending_jobs'] ?? 0;
        $activeWorkers = $metrics['active_workers'] ?? 0;

        if ($activeWorkers === 0) {
            return 'critical';
        }

        if ($failedJobs > 100 || $pendingJobs > 1000) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Get bot workers status
     */
    protected function getBotWorkers(): array
    {
        // Check if trading bot addon is active
        if (!\App\Support\AddonRegistry::active('trading-management-addon')) {
            return [
                'status' => 'not_installed',
                'active' => 0,
                'total_bots' => 0,
            ];
        }

        try {
            // Try to get bot worker data from TradingBotMonitoringService
            if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService::class)) {
                $monitoringService = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService::class);
                
                // Check if the service has a method to get worker status
                if (method_exists($monitoringService, 'getWorkerStatus')) {
                    return $monitoringService->getWorkerStatus();
                }
            }

            // Fallback: Check database for active bots
            if (DB::getSchemaBuilder()->hasTable('trading_bots')) {
                $activeBots = DB::table('trading_bots')
                    ->where('status', 'active')
                    ->count();
                
                $totalBots = DB::table('trading_bots')->count();

                return [
                    'status' => $activeBots > 0 ? 'running' : 'stopped',
                    'active' => $activeBots,
                    'total_bots' => $totalBots,
                ];
            }

            return [
                'status' => 'not_installed',
                'active' => 0,
                'total_bots' => 0,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get bot workers', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'active' => 0,
                'total_bots' => 0,
            ];
        }
    }

    /**
     * Get Octane workers status
     */
    protected function getOctaneWorkers(): array
    {
        // Check if Octane is installed
        if (!class_exists(\Laravel\Octane\Octane::class)) {
            return [
                'status' => 'not_installed',
                'workers' => 0,
                'memory_mb' => 0,
            ];
        }

        try {
            // Check if Octane server is running
            $process = $this->checkOctaneProcess();
            
            if (empty($process)) {
                return [
                    'status' => 'not_running',
                    'workers' => 0,
                    'memory_mb' => 0,
                ];
            }

            // Parse worker count and memory from process
            $workers = $this->parseOctaneWorkerCount($process);
            $memory = $this->parseOctaneMemory($process);

            return [
                'status' => 'running',
                'workers' => $workers,
                'memory_mb' => $memory,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get Octane workers', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'workers' => 0,
                'memory_mb' => 0,
            ];
        }
    }

    /**
     * Check if Octane process is running
     */
    protected function checkOctaneProcess(): ?string
    {
        try {
            // Try to check via ps command
            $process = shell_exec('ps aux | grep "octane:start" | grep -v grep');
            
            if (!empty($process)) {
                return $process;
            }

            // Fallback: Check PID file if exists
            $pidFile = storage_path('logs/octane.pid');
            if (file_exists($pidFile)) {
                $pid = file_get_contents($pidFile);
                if ($pid && posix_kill((int)$pid, 0)) {
                    // Process is running, get details
                    return shell_exec("ps -p {$pid} -o command=");
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse Octane worker count from process output
     */
    protected function parseOctaneWorkerCount(string $process): int
    {
        // Try to extract workers from command line arguments
        if (preg_match('/--workers=(\d+)/', $process, $matches)) {
            return (int)$matches[1];
        }

        // Default to 1 if not found
        return 1;
    }

    /**
     * Parse Octane memory usage from process output
     */
    protected function parseOctaneMemory(string $process): float
    {
        // Try to get memory from ps output
        if (preg_match('/\s+(\d+)\s+/', $process, $matches)) {
            // This is a rough estimate - actual memory would need more parsing
            return (float)$matches[1] / 1024; // Convert KB to MB
        }

        return 0.0;
    }
}

