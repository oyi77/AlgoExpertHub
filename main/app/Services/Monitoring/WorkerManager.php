<?php

namespace App\Services\Monitoring;

use App\Services\QueueOptimizer;
use App\Services\Monitoring\SystemMonitor;
use Illuminate\Support\Facades\Log;

/**
 * WorkerManager Service
 * 
 * Aggregates worker metrics from all sources (queue workers, bot workers, Octane).
 */
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
     * Get all worker metrics
     * 
     * @return array
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
     * Get queue worker metrics
     * 
     * @return array
     */
    protected function getQueueWorkers(): array
    {
        try {
            $health = $this->queueOptimizer->monitorHealth();
            $metrics = $this->queueOptimizer->getMetrics();
            
            $overall = $health['overall'] ?? [];
            
            return [
                'status' => 'active',
                'active' => $overall['active_workers'] ?? 0,
                'total_jobs' => $overall['total_jobs'] ?? 0,
                'failed_jobs' => $this->getFailedJobsCount(),
                'pending_jobs' => $this->getPendingJobsCount(),
                'health_score' => $overall['average_health'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get queue workers', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'active' => 0,
                'total_jobs' => 0,
                'failed_jobs' => 0,
                'pending_jobs' => 0,
                'health_score' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get failed jobs count
     * 
     * @return int
     */
    protected function getFailedJobsCount(): int
    {
        try {
            return \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get pending jobs count
     * 
     * @return int
     */
    protected function getPendingJobsCount(): int
    {
        try {
            return \Illuminate\Support\Facades\DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get bot worker metrics
     * 
     * @return array
     */
    protected function getBotWorkers(): array
    {
        return $this->systemMonitor->getBotWorkers();
    }

    /**
     * Get Octane worker metrics
     * 
     * @return array
     */
    protected function getOctaneWorkers(): array
    {
        return $this->systemMonitor->getOctaneStatus();
    }
}

