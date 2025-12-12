<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\QueueOptimizer;
use Carbon\Carbon;

abstract class OptimizedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public bool $failOnTimeout = true;

    /**
     * The tags for the job.
     */
    public array $tags = [];

    /**
     * Job priority (high, default, low)
     */
    protected string $priority = 'default';

    /**
     * Whether to use exponential backoff
     */
    protected bool $useExponentialBackoff = true;

    /**
     * Base delay for exponential backoff (seconds)
     */
    protected int $baseDelay = 1;

    /**
     * Maximum delay for exponential backoff (seconds)
     */
    protected int $maxDelay = 300;

    /**
     * Job start time for performance tracking
     */
    protected float $startTime;

    /**
     * Execute the job with performance tracking
     */
    final public function handle(): void
    {
        $this->startTime = microtime(true);
        
        try {
            Log::info('Job started', [
                'job' => static::class,
                'queue' => $this->queue,
                'attempt' => $this->attempts(),
                'tags' => $this->tags
            ]);

            $this->process();
            
            $this->recordSuccess();
            
            Log::info('Job completed successfully', [
                'job' => static::class,
                'duration' => $this->getDuration(),
                'memory' => $this->getMemoryUsage()
            ]);
            
        } catch (\Throwable $e) {
            $this->recordFailure($e);
            
            Log::error('Job failed', [
                'job' => static::class,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'duration' => $this->getDuration()
            ]);
            
            throw $e;
        }
    }

    /**
     * Abstract method that subclasses must implement
     */
    abstract protected function process(): void;

    /**
     * Handle a job failure with exponential backoff
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job failed permanently', [
            'job' => static::class,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->recordFailure($exception, true);
        
        // Call custom failure handler if implemented
        if (method_exists($this, 'onFailure')) {
            $this->onFailure($exception);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job
     */
    public function backoff(): array
    {
        if (!$this->useExponentialBackoff) {
            return [];
        }

        $delays = [];
        for ($attempt = 1; $attempt <= $this->tries; $attempt++) {
            $delay = min(
                $this->maxDelay,
                $this->baseDelay * pow(2, $attempt - 1)
            );
            
            // Add jitter to prevent thundering herd
            $jitter = rand(0, (int)($delay * 0.1));
            $delays[] = $delay + $jitter;
        }

        return $delays;
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return array_merge($this->tags, [
            'priority:' . $this->priority,
            'class:' . class_basename(static::class)
        ]);
    }

    /**
     * Set job priority
     */
    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        $this->onQueue($priority);
        
        return $this;
    }

    /**
     * Add tags to the job
     */
    public function addTags(array $tags): self
    {
        $this->tags = array_merge($this->tags, $tags);
        
        return $this;
    }

    /**
     * Record successful job completion
     */
    protected function recordSuccess(): void
    {
        $duration = $this->getDuration();
        
        app(QueueOptimizer::class)->recordJobMetrics(
            $this->queue ?? 'default',
            $duration,
            true
        );
    }

    /**
     * Record job failure
     */
    protected function recordFailure(\Throwable $exception, bool $permanent = false): void
    {
        $duration = $this->getDuration();
        
        app(QueueOptimizer::class)->recordJobMetrics(
            $this->queue ?? 'default',
            $duration,
            false
        );

        // Additional failure tracking
        $failureData = [
            'job' => static::class,
            'queue' => $this->queue ?? 'default',
            'attempt' => $this->attempts(),
            'permanent' => $permanent,
            'error' => $exception->getMessage(),
            'duration' => $duration,
            'memory' => $this->getMemoryUsage(),
            'timestamp' => Carbon::now()->toISOString()
        ];

        // Store failure data for analysis
        cache()->put(
            'job_failure:' . uniqid(),
            $failureData,
            3600 // 1 hour
        );
    }

    /**
     * Get job execution duration in milliseconds
     */
    protected function getDuration(): float
    {
        if (!isset($this->startTime)) {
            return 0.0;
        }
        
        return (microtime(true) - $this->startTime) * 1000;
    }

    /**
     * Get memory usage in bytes
     */
    protected function getMemoryUsage(): int
    {
        return memory_get_usage(true);
    }

    /**
     * Check if job should be retried based on exception type
     */
    protected function shouldRetry(\Throwable $exception): bool
    {
        // Don't retry certain types of exceptions
        $nonRetryableExceptions = [
            \InvalidArgumentException::class,
            \BadMethodCallException::class,
            \TypeError::class,
        ];

        foreach ($nonRetryableExceptions as $exceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get job metadata for monitoring
     */
    public function getMetadata(): array
    {
        return [
            'class' => static::class,
            'queue' => $this->queue ?? 'default',
            'priority' => $this->priority,
            'tries' => $this->tries,
            'timeout' => $this->timeout,
            'tags' => $this->tags(),
            'created_at' => Carbon::now()->toISOString()
        ];
    }
}