<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Jobs;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TestConnectionHealthJob
 * 
 * Scheduled job to periodically test connection health
 */
class TestConnectionHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 1;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    protected ?int $connectionId;

    /**
     * Create a new job instance.
     * 
     * @param int|null $connectionId If null, tests all active connections
     */
    public function __construct(?int $connectionId = null)
    {
        $this->connectionId = $connectionId;
    }

    /**
     * Execute the job.
     */
    public function handle(ExchangeConnectionService $service): void
    {
        try {
            // Set memory limit for this job
            $oldMemoryLimit = ini_get('memory_limit');
            ini_set('memory_limit', '256M');
            
            if ($this->connectionId) {
                // Test specific connection
                $connection = ExchangeConnection::find($this->connectionId);
                if ($connection) {
                    $this->testConnection($connection, $service);
                }
            } else {
                // Test all active connections that are stale (not tested in last 5 minutes)
                // Limit to 10 connections per run to prevent memory issues
                $connections = ExchangeConnection::where('is_active', true)
                    ->where(function($query) {
                        $query->whereNull('last_tested_at')
                              ->orWhere('last_tested_at', '<', now()->subMinutes(5));
                    })
                    ->limit(10) // Limit batch size
                    ->get();

                foreach ($connections as $connection) {
                    // Check memory usage before each connection test
                    $memoryUsage = memory_get_usage(true);
                    $memoryLimit = ini_get('memory_limit');
                    $memoryLimitBytes = $this->convertToBytes($memoryLimit);
                    
                    // If memory usage is above 80%, skip remaining connections
                    if ($memoryUsage > ($memoryLimitBytes * 0.8)) {
                        Log::warning('Memory limit approaching, skipping remaining connection tests', [
                            'memory_usage' => $memoryUsage,
                            'memory_limit' => $memoryLimitBytes,
                            'connections_tested' => $connections->count(),
                        ]);
                        break;
                    }
                    
                    $this->testConnection($connection, $service);
                    
                    // Free memory after each test
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }

                Log::info('Connection health check completed', [
                    'connections_tested' => $connections->count(),
                ]);
            }
            
            // Restore original memory limit
            if ($oldMemoryLimit) {
                ini_set('memory_limit', $oldMemoryLimit);
            }
        } catch (\Throwable $e) {
            // Catch all exceptions to prevent job retries
            Log::error('TestConnectionHealthJob handle error', [
                'connection_id' => $this->connectionId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Don't re-throw - job should complete without retry
        }
    }
    
    /**
     * Convert memory limit string to bytes
     */
    protected function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Test a single connection
     */
    protected function testConnection(ExchangeConnection $connection, ExchangeConnectionService $service): void
    {
        try {
            // Set a shorter timeout for this specific connection test
            $oldTimeLimit = ini_get('max_execution_time');
            set_time_limit(20); // 20 seconds max per connection
            
            // Check memory before test
            $memoryBefore = memory_get_usage(true);
            
            $result = $service->testConnection($connection);
            
            // Check memory after test
            $memoryAfter = memory_get_usage(true);
            $memoryUsed = $memoryAfter - $memoryBefore;
            
            // Log if significant memory was used
            if ($memoryUsed > 10 * 1024 * 1024) { // More than 10MB
                Log::debug('Connection test used significant memory', [
                    'connection_id' => $connection->id,
                    'memory_used' => round($memoryUsed / 1024 / 1024, 2) . 'MB',
                ]);
            }
            
            // Restore original time limit
            set_time_limit($oldTimeLimit ?: 30);
            
            if (!$result['success']) {
                Log::warning('Connection health check failed', [
                    'connection_id' => $connection->id,
                    'connection_name' => $connection->name,
                    'error' => $result['message'],
                ]);
            }
        } catch (\Throwable $e) {
            // Catch all exceptions (including fatal errors)
            Log::error('Connection health check error', [
                'connection_id' => $connection->id,
                'connection_name' => $connection->name,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Don't re-throw - continue with next connection
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::warning('TestConnectionHealthJob failed - will not retry', [
            'connection_id' => $this->connectionId,
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);
    }
}
