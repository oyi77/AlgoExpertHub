<?php

namespace App\Services\Trading;

use App\Models\TradingConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConnectionHealthMonitor
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('trading.connection_health', []);
    }

    /**
     * Check connection health
     */
    public function checkHealth(TradingConnection $connection): array
    {
        $health = [
            'connection_id' => $connection->id,
            'is_healthy' => true,
            'issues' => [],
            'last_check' => now(),
            'metrics' => []
        ];

        // Check last successful ping
        $lastPing = $this->getLastPingTime($connection);
        if ($lastPing && $lastPing->diffInMinutes(now()) > 5) {
            $health['is_healthy'] = false;
            $health['issues'][] = 'No ping response in 5 minutes';
        }

        // Check error rate
        $errorRate = $this->getErrorRate($connection);
        if ($errorRate > 0.1) { // More than 10% errors
            $health['is_healthy'] = false;
            $health['issues'][] = "High error rate: {$errorRate}%";
        }

        // Check response time
        $avgResponseTime = $this->getAverageResponseTime($connection);
        if ($avgResponseTime > 5000) { // More than 5 seconds
            $health['is_healthy'] = false;
            $health['issues'][] = "Slow response time: {$avgResponseTime}ms";
        }

        $health['metrics'] = [
            'last_ping' => $lastPing,
            'error_rate' => $errorRate,
            'avg_response_time' => $avgResponseTime,
            'failed_attempts' => $this->getFailedAttempts($connection)
        ];

        return $health;
    }

    /**
     * Perform failover to backup connection
     */
    public function failover(TradingConnection $connection): ?TradingConnection
    {
        $backupConnection = $this->getBackupConnection($connection);

        if (!$backupConnection) {
            Log::error("No backup connection available for {$connection->id}");
            return null;
        }

        // Test backup connection
        if (!$this->testConnection($backupConnection)) {
            Log::error("Backup connection {$backupConnection->id} is also unhealthy");
            return null;
        }

        // Mark primary as failed
        $this->markConnectionFailed($connection);

        // Activate backup
        $this->activateConnection($backupConnection);

        Log::info("Failover successful from {$connection->id} to {$backupConnection->id}");

        return $backupConnection;
    }

    /**
     * Attempt to recover connection
     */
    public function attemptRecovery(TradingConnection $connection): bool
    {
        $maxRetries = $this->config['max_recovery_attempts'] ?? 3;
        $retryDelay = $this->config['retry_delay_seconds'] ?? 5;

        for ($i = 0; $i < $maxRetries; $i++) {
            $attemptNum = $i + 1;
            Log::info("Recovery attempt {$attemptNum} for connection {$connection->id}");

            if ($this->testConnection($connection)) {
                $this->markConnectionRecovered($connection);
                return true;
            }

            if ($i < $maxRetries - 1) {
                sleep($retryDelay * ($i + 1)); // Exponential backoff
            }
        }

        return false;
    }

    /**
     * Test connection
     */
    protected function testConnection(TradingConnection $connection): bool
    {
        try {
            // Simulate connection test
            $cacheKey = "connection_test:{$connection->id}";
            $result = Cache::remember($cacheKey, 60, function () use ($connection) {
                // Would actually test the connection here
                return true;
            });

            return $result;
        } catch (\Exception $e) {
            Log::error("Connection test failed for {$connection->id}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get last ping time
     */
    protected function getLastPingTime(TradingConnection $connection): ?Carbon
    {
        $cacheKey = "connection_ping:{$connection->id}";
        $timestamp = Cache::get($cacheKey);

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * Record ping
     */
    public function recordPing(TradingConnection $connection): void
    {
        $cacheKey = "connection_ping:{$connection->id}";
        Cache::put($cacheKey, now(), 600); // 10 minutes
    }

    /**
     * Get error rate
     */
    protected function getErrorRate(TradingConnection $connection): float
    {
        $total = Cache::get("connection_requests:{$connection->id}", 0);
        $errors = Cache::get("connection_errors:{$connection->id}", 0);

        if ($total === 0) {
            return 0;
        }

        return ($errors / $total) * 100;
    }

    /**
     * Record request
     */
    public function recordRequest(TradingConnection $connection, bool $success = true): void
    {
        $totalKey = "connection_requests:{$connection->id}";
        $errorKey = "connection_errors:{$connection->id}";

        Cache::increment($totalKey);
        if (!$success) {
            Cache::increment($errorKey);
        }

        // Set expiry if not set
        if (!Cache::has($totalKey . ':ttl')) {
            Cache::put($totalKey . ':ttl', true, 3600);
            Cache::put($errorKey . ':ttl', true, 3600);
        }
    }

    /**
     * Get average response time
     */
    protected function getAverageResponseTime(TradingConnection $connection): float
    {
        $cacheKey = "connection_response_times:{$connection->id}";
        $times = Cache::get($cacheKey, []);

        if (empty($times)) {
            return 0;
        }

        return array_sum($times) / count($times);
    }

    /**
     * Record response time
     */
    public function recordResponseTime(TradingConnection $connection, float $milliseconds): void
    {
        $cacheKey = "connection_response_times:{$connection->id}";
        $times = Cache::get($cacheKey, []);

        $times[] = $milliseconds;

        // Keep only last 100 measurements
        if (count($times) > 100) {
            array_shift($times);
        }

        Cache::put($cacheKey, $times, 3600);
    }

    /**
     * Get failed attempts
     */
    protected function getFailedAttempts(TradingConnection $connection): int
    {
        $cacheKey = "connection_failed_attempts:{$connection->id}";
        return Cache::get($cacheKey, 0);
    }

    /**
     * Increment failed attempts
     */
    public function incrementFailedAttempts(TradingConnection $connection): void
    {
        $cacheKey = "connection_failed_attempts:{$connection->id}";
        Cache::increment($cacheKey);
        Cache::put($cacheKey . ':ttl', true, 3600);
    }

    /**
     * Get backup connection
     */
    protected function getBackupConnection(TradingConnection $connection): ?TradingConnection
    {
        return TradingConnection::where('user_id', $connection->user_id)
            ->where('id', '!=', $connection->id)
            ->where('is_active', true)
            ->where('type', $connection->type)
            ->first();
    }

    /**
     * Mark connection as failed
     */
    protected function markConnectionFailed(TradingConnection $connection): void
    {
        $connection->update([
            'status' => 'failed',
            'last_error' => 'Connection health check failed',
            'failed_at' => now()
        ]);
    }

    /**
     * Mark connection as recovered
     */
    protected function markConnectionRecovered(TradingConnection $connection): void
    {
        $connection->update([
            'status' => 'active',
            'last_error' => null,
            'failed_at' => null,
            'recovered_at' => now()
        ]);

        // Reset failure metrics
        $cacheKey = "connection_failed_attempts:{$connection->id}";
        Cache::forget($cacheKey);
    }

    /**
     * Activate connection
     */
    protected function activateConnection(TradingConnection $connection): void
    {
        $connection->update([
            'status' => 'active',
            'activated_at' => now()
        ]);
    }
}
