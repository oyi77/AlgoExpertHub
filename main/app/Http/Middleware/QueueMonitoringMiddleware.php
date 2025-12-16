<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\QueueOptimizer;
use Carbon\Carbon;

class QueueMonitoringMiddleware
{
    protected QueueOptimizer $queueOptimizer;

    public function __construct(QueueOptimizer $queueOptimizer)
    {
        $this->queueOptimizer = $queueOptimizer;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $response = $next($request);
            
            $this->recordMetrics($request, $startTime, $startMemory, true);
            
            return $response;
        } catch (\Throwable $e) {
            $this->recordMetrics($request, $startTime, $startMemory, false, $e);
            
            throw $e;
        }
    }

    /**
     * Record performance metrics for the request
     */
    protected function recordMetrics(Request $request, float $startTime, int $startMemory, bool $success, ?\Throwable $exception = null): void
    {
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = memory_get_usage(true) - $startMemory;
        
        $metrics = [
            'url' => $request->url(),
            'method' => $request->method(),
            'duration_ms' => round($duration, 2),
            'memory_used' => $memoryUsed,
            'success' => $success,
            'timestamp' => Carbon::now()->toISOString(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ];

        if ($exception) {
            $metrics['error'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        // Log slow requests (over 200ms)
        if ($duration > 200) {
            Log::warning('Slow request detected', $metrics);
        }

        // Log failed requests
        if (!$success) {
            Log::error('Request failed', $metrics);
        }

        // Record metrics for queue optimization
        if ($this->isQueueRelatedRequest($request)) {
            $queue = $this->determineQueueFromRequest($request);
            $this->queueOptimizer->recordJobMetrics($queue, $duration, $success);
        }
    }

    /**
     * Determine if this request is queue-related
     */
    protected function isQueueRelatedRequest(Request $request): bool
    {
        $queuePaths = [
            '/admin/queue',
            '/api/queue',
            '/user/signals',
            '/admin/signals',
            '/api/signals'
        ];

        foreach ($queuePaths as $path) {
            if (str_starts_with($request->path(), trim($path, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine queue name from request
     */
    protected function determineQueueFromRequest(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, 'signal')) {
            return 'high'; // Signal operations are high priority
        } elseif (str_contains($path, 'payment')) {
            return 'high'; // Payment operations are high priority
        } elseif (str_contains($path, 'admin')) {
            return 'default'; // Admin operations are normal priority
        }
        
        return 'low'; // Default to low priority
    }
}