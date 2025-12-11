<?php

namespace App\Services;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Carbon\Carbon;

class QueueOptimizer
{
    protected array $priorityQueues = ['high', 'default', 'low'];
    protected int $maxWorkers = 10;
    protected int $minWorkers = 2;
    protected float $targetCpuThreshold = 70.0;
    protected int $batchSize = 100;

    /**
     * Dispatch jobs in batches for better performance
     */
    public function dispatchBatch(array $jobs, string $name = null): string
    {
        $batch = Bus::batch($jobs);
        
        if ($name) {
            $batch->name($name);
        }
        
        $batch->allowFailures()
              ->onQueue($this->selectOptimalQueue())
              ->dispatch();
        
        Log::info('Queue batch dispatched', [
            'batch_id' => $batch->id,
            'job_count' => count($jobs),
            'queue' => $this->selectOptimalQueue()
        ]);
        
        return $batch->id;
    }

    /**
     * Monitor queue health and performance metrics
     */
    public function monitorHealth(): array
    {
        $metrics = [];
        
        foreach ($this->priorityQueues as $queue) {
            $size = $this->getQueueSize($queue);
            $processingRate = $this->getProcessingRate($queue);
            $failureRate = $this->getFailureRate($queue);
            
            $metrics[$queue] = [
                'size' => $size,
                'processing_rate' => $processingRate,
                'failure_rate' => $failureRate,
                'health_score' => $this->calculateHealthScore($size, $processingRate, $failureRate),
                'estimated_completion' => $this->estimateCompletionTime($size, $processingRate)
            ];
        }
        
        $metrics['overall'] = [
            'total_jobs' => array_sum(array_column($metrics, 'size')),
            'average_health' => array_sum(array_column($metrics, 'health_score')) / count($metrics),
            'active_workers' => $this->getActiveWorkerCount(),
            'recommended_workers' => $this->calculateOptimalWorkerCount($metrics)
        ];
        
        // Cache metrics for dashboard
        Cache::put('queue_health_metrics', $metrics, 300); // 5 minutes
        
        return $metrics;
    }

    /**
     * Scale workers based on current load
     */
    public function scaleWorkers(int $targetCount = null): bool
    {
        if ($targetCount === null) {
            $metrics = $this->monitorHealth();
            $targetCount = $metrics['overall']['recommended_workers'];
        }
        
        $currentWorkers = $this->getActiveWorkerCount();
        
        if ($targetCount > $currentWorkers) {
            return $this->scaleUp($targetCount - $currentWorkers);
        } elseif ($targetCount < $currentWorkers) {
            return $this->scaleDown($currentWorkers - $targetCount);
        }
        
        return true; // No scaling needed
    }

    /**
     * Get comprehensive queue metrics
     */
    public function getMetrics(): array
    {
        $metrics = $this->monitorHealth();
        
        // Add historical data
        $metrics['historical'] = [
            'hourly_throughput' => $this->getHourlyThroughput(),
            'daily_averages' => $this->getDailyAverages(),
            'peak_times' => $this->getPeakTimes()
        ];
        
        // Add performance indicators
        $metrics['performance'] = [
            'average_job_duration' => $this->getAverageJobDuration(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage()
        ];
        
        return $metrics;
    }

    /**
     * Implement exponential backoff for failed jobs
     */
    public function handleJobFailure(Job $job, \Throwable $exception): void
    {
        $attempts = $job->attempts();
        $maxAttempts = $job->maxTries() ?? 3;
        
        if ($attempts < $maxAttempts) {
            $delay = $this->calculateExponentialBackoff($attempts);
            
            Log::warning('Job failed, retrying with backoff', [
                'job' => get_class($job),
                'attempt' => $attempts,
                'delay' => $delay,
                'error' => $exception->getMessage()
            ]);
            
            $job->release($delay);
        } else {
            Log::error('Job failed permanently after max attempts', [
                'job' => get_class($job),
                'attempts' => $attempts,
                'error' => $exception->getMessage()
            ]);
            
            $job->fail($exception);
        }
    }

    /**
     * Select optimal queue based on current load
     */
    protected function selectOptimalQueue(): string
    {
        $queueSizes = [];
        
        foreach ($this->priorityQueues as $queue) {
            $queueSizes[$queue] = $this->getQueueSize($queue);
        }
        
        // Return queue with lowest load, but prefer higher priority queues
        if ($queueSizes['high'] < 50) {
            return 'high';
        } elseif ($queueSizes['default'] < 100) {
            return 'default';
        } else {
            return 'low';
        }
    }

    /**
     * Get queue size for a specific queue
     */
    protected function getQueueSize(string $queue): int
    {
        try {
            return Redis::connection('queue')->llen("queues:$queue");
        } catch (\Exception $e) {
            Log::error('Failed to get queue size', ['queue' => $queue, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Calculate processing rate (jobs per minute)
     */
    protected function getProcessingRate(string $queue): float
    {
        $key = "queue_metrics:processing_rate:$queue";
        $rates = Cache::get($key, []);
        
        if (empty($rates)) {
            return 0.0;
        }
        
        return array_sum($rates) / count($rates);
    }

    /**
     * Calculate failure rate percentage
     */
    protected function getFailureRate(string $queue): float
    {
        $key = "queue_metrics:failure_rate:$queue";
        return Cache::get($key, 0.0);
    }

    /**
     * Calculate health score (0-100)
     */
    protected function calculateHealthScore(int $size, float $processingRate, float $failureRate): float
    {
        $sizeScore = max(0, 100 - ($size / 10)); // Penalize large queue sizes
        $rateScore = min(100, $processingRate * 2); // Reward high processing rates
        $failureScore = max(0, 100 - ($failureRate * 10)); // Penalize high failure rates
        
        return ($sizeScore + $rateScore + $failureScore) / 3;
    }

    /**
     * Estimate completion time in minutes
     */
    protected function estimateCompletionTime(int $size, float $processingRate): ?float
    {
        if ($processingRate <= 0) {
            return null;
        }
        
        return $size / $processingRate;
    }

    /**
     * Get active worker count
     */
    protected function getActiveWorkerCount(): int
    {
        try {
            $workers = Redis::connection('queue')->smembers('workers');
            return count($workers);
        } catch (\Exception $e) {
            Log::error('Failed to get worker count', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Calculate optimal worker count based on metrics
     */
    protected function calculateOptimalWorkerCount(array $metrics): int
    {
        $totalJobs = $metrics['overall']['total_jobs'];
        $averageHealth = $metrics['overall']['average_health'];
        
        // Base calculation on job count and health
        $baseWorkers = min($this->maxWorkers, max($this->minWorkers, ceil($totalJobs / 50)));
        
        // Adjust based on health score
        if ($averageHealth < 50) {
            $baseWorkers = min($this->maxWorkers, $baseWorkers + 2);
        } elseif ($averageHealth > 80) {
            $baseWorkers = max($this->minWorkers, $baseWorkers - 1);
        }
        
        return $baseWorkers;
    }

    /**
     * Scale up workers
     */
    protected function scaleUp(int $count): bool
    {
        Log::info("Scaling up queue workers", ['count' => $count]);
        
        // In a real implementation, this would start new worker processes
        // For now, we'll just log the action and update metrics
        Cache::increment('queue_worker_scale_requests', $count);
        
        return true;
    }

    /**
     * Scale down workers
     */
    protected function scaleDown(int $count): bool
    {
        Log::info("Scaling down queue workers", ['count' => $count]);
        
        // In a real implementation, this would gracefully stop worker processes
        // For now, we'll just log the action and update metrics
        Cache::decrement('queue_worker_scale_requests', $count);
        
        return true;
    }

    /**
     * Calculate exponential backoff delay
     */
    protected function calculateExponentialBackoff(int $attempts): int
    {
        // Exponential backoff: 2^attempts seconds, with jitter
        $baseDelay = pow(2, $attempts);
        $jitter = rand(0, $baseDelay / 2);
        
        return min(300, $baseDelay + $jitter); // Max 5 minutes
    }

    /**
     * Get hourly throughput data
     */
    protected function getHourlyThroughput(): array
    {
        $key = 'queue_metrics:hourly_throughput';
        return Cache::get($key, []);
    }

    /**
     * Get daily averages
     */
    protected function getDailyAverages(): array
    {
        $key = 'queue_metrics:daily_averages';
        return Cache::get($key, []);
    }

    /**
     * Get peak usage times
     */
    protected function getPeakTimes(): array
    {
        $key = 'queue_metrics:peak_times';
        return Cache::get($key, []);
    }

    /**
     * Get average job duration
     */
    protected function getAverageJobDuration(): float
    {
        $key = 'queue_metrics:average_duration';
        return Cache::get($key, 0.0);
    }

    /**
     * Get memory usage
     */
    protected function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ];
    }

    /**
     * Get CPU usage (simplified)
     */
    protected function getCpuUsage(): float
    {
        // This is a simplified implementation
        // In production, you'd use system monitoring tools
        return Cache::get('system_cpu_usage', 0.0);
    }

    /**
     * Record job processing metrics
     */
    public function recordJobMetrics(string $queue, float $duration, bool $success): void
    {
        $now = Carbon::now();
        $minute = $now->format('Y-m-d H:i');
        
        // Update processing rate
        $rateKey = "queue_metrics:processing_rate:$queue";
        $rates = Cache::get($rateKey, []);
        $rates[$minute] = ($rates[$minute] ?? 0) + 1;
        
        // Keep only last 60 minutes
        $rates = array_slice($rates, -60, 60, true);
        Cache::put($rateKey, $rates, 3600);
        
        // Update failure rate
        if (!$success) {
            $failureKey = "queue_metrics:failure_rate:$queue";
            $failures = Cache::get($failureKey, 0);
            $total = Cache::get("queue_metrics:total_jobs:$queue", 0);
            
            Cache::put($failureKey, ($failures + 1) / ($total + 1) * 100, 3600);
        }
        
        // Update total jobs
        Cache::increment("queue_metrics:total_jobs:$queue");
        
        // Update average duration
        $durationKey = 'queue_metrics:average_duration';
        $avgDuration = Cache::get($durationKey, 0.0);
        $totalJobs = Cache::get('queue_metrics:total_processed', 0);
        
        $newAvg = (($avgDuration * $totalJobs) + $duration) / ($totalJobs + 1);
        Cache::put($durationKey, $newAvg, 3600);
        Cache::increment('queue_metrics:total_processed');
    }
}