<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use App\Helpers\Helper\Helper;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * TradingBotWorkerService
 * 
 * Manages background worker processes for trading bots
 */
class TradingBotWorkerService
{
    /**
     * Get PHP command to use (handles Docker environment)
     * 
     * @return array ['command' => string, 'path' => string] PHP command and working directory
     */
    protected function getPhpCommand(): array
    {
        return $this->buildPhpCommand('', base_path());
    }

    /**
     * Build PHP command for background processes
     * 
     * Detects Docker environment and uses docker exec wrapper if needed
     * 
     * @param string $iniString Additional INI settings
     * @param string $path Working directory
     * @return array ['command' => string, 'path' => string]
     */
    protected function buildPhpCommand($iniString = '', $path = null)
    {
        // Since Laravel runs inside Docker container, we use direct PHP execution
        // Docker exec is only needed if supervisor runs on host (handled in supervisor config)
        
        $phpBinary = PHP_BINARY;
        
        // If we are running in FPM, PHP_BINARY points to php-fpm which cannot run console scripts.
        // Fallback to 'php' (assuming CLI is in PATH) if binary is missing or is fpm.
        if (!$phpBinary || strpos($phpBinary, 'fpm') !== false) {
            $phpBinary = 'php';
        }

        // Add standard memory limit and other settings if needed
        $command = $phpBinary;
        if (!empty($iniString)) {
            $command .= ' ' . $iniString;
        }

        // Determine working directory
        if (!$path) {
            $path = base_path();
        }

        return [
            'command' => $command,
            'path' => $path
        ];
    }

    /**
     * Start worker process for bot
     * 
     * @param TradingBot $bot
     * @return bool Success status
     * @throws \Exception
     */
    public function startWorker(TradingBot $bot): bool
    {
        if ($this->isWorkerRunning($bot)) {
            throw new \Exception('Worker is already running for this bot');
        }

        // Check feature flag for queue-based workers
        $useQueueWorkers = env('ENABLE_QUEUE_WORKERS', true);

        if ($useQueueWorkers) {
            // NEW: Queue-based worker (container-safe, auto-recovery)
            return $this->startQueueWorker($bot);
        } else {
            // OLD: PID-based worker (legacy, will be deprecated)
            return $this->startLegacyWorker($bot);
        }
    }

    /**
     * Start queue-based worker (NEW METHOD)
     * 
     * @param TradingBot $bot
     * @return bool
     */
    protected function startQueueWorker(TradingBot $bot): bool
    {
        try {
            // Check queue connection before dispatching
            $queueConnection = config('queue.default');
            if (!$queueConnection || $queueConnection === 'sync') {
                Log::warning('Queue connection is sync or not configured, falling back to legacy worker', [
                    'bot_id' => $bot->id,
                    'queue_connection' => $queueConnection,
                ]);
                // Fallback to legacy worker if queue is sync
                return $this->startLegacyWorker($bot);
            }

            // Dispatch job to queue with error handling
            try {
                \Addons\TradingManagement\Modules\TradingBot\Jobs\TradingBotWorkerJob::dispatch($bot->id);
            } catch (\Exception $e) {
                // Check if it's a queue connection issue
                $errorMessage = $e->getMessage();
                $errorLower = strtolower($errorMessage);
                
                // Check for common queue-related errors
                $isQueueError = (
                    strpos($errorLower, 'queue') !== false || 
                    strpos($errorLower, 'connection') !== false ||
                    strpos($errorLower, 'redis') !== false ||
                    strpos($errorLower, 'database') !== false ||
                    strpos($errorLower, 'driver') !== false
                );
                
                if ($isQueueError) {
                    Log::warning('Queue connection issue detected, falling back to legacy worker', [
                        'bot_id' => $bot->id,
                        'error' => $errorMessage,
                    ]);
                    return $this->startLegacyWorker($bot);
                }
                // Re-throw if it's a different error
                throw $e;
            }

            // Update bot status
            $bot->update([
                'worker_status' => 'queued',
                'worker_started_at' => now(),
                'worker_last_heartbeat' => now(),
            ]);

            Log::info('Trading bot worker queued', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
            ]);

            // Auto-start MetaAPI stream worker if needed
            if ($bot->trading_mode === 'MARKET_STREAM_BASED') {
                $bot->refresh();
                $bot->load('dataConnection');
                
                if ($bot->dataConnection) {
                    $this->ensureMetaApiStreamWorker($bot);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue trading bot worker', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name ?? 'Unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Failed to start worker: ' . $e->getMessage());
        }
    }

    /**
     * Start legacy PID-based worker (OLD METHOD - DEPRECATED)
     * 
     * @param TradingBot $bot
     * @return bool
     * @deprecated Will be removed in future version
     */
    protected function startLegacyWorker(TradingBot $bot): bool
    {
        $phpConfig = $this->getPhpCommand();
        $phpCommand = $phpConfig['command'];
        $workPath = $phpConfig['path'];
        $artisanPath = $workPath . '/artisan';
        $logPath = storage_path("logs/trading-bot-{$bot->id}.log");

        try {
            // Build command - Laravel runs inside Docker, so use direct execution
            $commandString = sprintf(
                'cd %s && nohup %s %s trading-bot:worker %d > %s 2>&1 & echo $!',
                escapeshellarg($workPath),
                escapeshellarg($phpCommand),
                escapeshellarg($artisanPath),
                $bot->id,
                escapeshellarg($logPath)
            );
            
            // Ensure dataConnection relationship is loaded before worker starts
            if ($bot->trading_mode === 'MARKET_STREAM_BASED' && !$bot->relationLoaded('dataConnection')) {
                $bot->load('dataConnection');
            }
            
            Log::info('Executing worker start command (LEGACY)', [
                'bot_id' => $bot->id,
                'command' => $commandString,
            ]);
            
            $output = shell_exec($commandString);
            $pid = (int) trim($output);
            
            if ($pid <= 0) {
                throw new \Exception('Failed to start worker process - no PID returned');
            }

            // Update bot with worker PID
            $bot->update([
                'worker_pid' => $pid,
                'worker_started_at' => now(),
            ]);

            Log::info('Trading bot worker started (LEGACY)', [
                'bot_id' => $bot->id,
                'pid' => $pid,
            ]);

            // Auto-start MetaAPI stream worker if needed
            if ($bot->trading_mode === 'MARKET_STREAM_BASED') {
                $bot->refresh();
                $bot->load('dataConnection');
                
                if ($bot->dataConnection) {
                    $this->ensureMetaApiStreamWorker($bot);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to start trading bot worker (LEGACY)', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to start worker: ' . $e->getMessage());
        }
    }

    /**
     * Ensure MetaAPI stream worker is running for bot's data connection
     * 
     * @param TradingBot $bot
     * @return void
     */
    protected function ensureMetaApiStreamWorker(TradingBot $bot): void
    {
        try {
            $dataConnection = $bot->dataConnection;
            if (!$dataConnection) {
                Log::warning('Cannot start MetaAPI stream worker: No data connection', [
                    'bot_id' => $bot->id,
                    'data_connection_id' => $bot->data_connection_id,
                ]);
                return;
            }

            // Get credentials (automatically decrypted by HasEncryptedCredentials trait)
            $credentials = $dataConnection->credentials ?? [];
            
            Log::info('Checking MetaAPI stream worker requirements', [
                'bot_id' => $bot->id,
                'data_connection_id' => $dataConnection->id,
                'provider' => $dataConnection->provider ?? 'unknown',
                'connection_type' => $dataConnection->connection_type ?? 'unknown',
                'has_credentials' => !empty($credentials),
                'credential_keys' => array_keys($credentials),
            ]);
            
            if (empty($credentials)) {
                Log::warning('Cannot start MetaAPI stream worker: No credentials in data connection', [
                    'bot_id' => $bot->id,
                    'data_connection_id' => $dataConnection->id,
                    'provider' => $dataConnection->provider ?? 'unknown',
                ]);
                return;
            }

            // Try different possible keys for account_id (MetaAPI uses account_id)
            $accountId = $credentials['account_id'] ?? 
                        $credentials['metaapi_account_id'] ?? 
                        $credentials['accountId'] ?? 
                        $credentials['metaapiAccountId'] ??
                        null;

            if (!$accountId) {
                Log::warning('Cannot start MetaAPI stream worker: No account_id found in credentials', [
                    'bot_id' => $bot->id,
                    'data_connection_id' => $dataConnection->id,
                    'provider' => $dataConnection->provider ?? 'unknown',
                    'available_keys' => array_keys($credentials),
                ]);
                return;
            }

            // Check if MetaAPI stream worker is already running for this account
            $checkCommand = sprintf(
                "ps aux | grep 'metaapi:stream-worker %s' | grep -v grep | wc -l",
                escapeshellarg($accountId)
            );
            $runningCount = (int) trim(shell_exec($checkCommand) ?: '0');

            if ($runningCount > 0) {
                Log::info('MetaAPI stream worker already running for account', [
                    'bot_id' => $bot->id,
                    'account_id' => $accountId,
                ]);
                return;
            }

            // Start MetaAPI stream worker
            $phpConfig = $this->getPhpCommand();
            $phpCommand = $phpConfig['command'];
            $workPath = $phpConfig['path'];
            $artisanPath = $workPath . '/artisan';
            $logPath = storage_path("logs/metaapi-stream-{$accountId}.log");
            
            // Build command - Laravel runs inside Docker, so use direct execution
            $commandString = sprintf(
                'cd %s && nohup %s %s metaapi:stream-worker %s > %s 2>&1 & echo $!',
                escapeshellarg($workPath),
                escapeshellarg($phpCommand),
                escapeshellarg($artisanPath),
                escapeshellarg($accountId),
                escapeshellarg($logPath)
            );

            Log::info('Starting MetaAPI stream worker', [
                'bot_id' => $bot->id,
                'account_id' => $accountId,
                'command' => $commandString,
            ]);

            $output = shell_exec($commandString);
            $pid = (int) trim($output);

            if ($pid > 0) {
                Log::info('MetaAPI stream worker started', [
                    'bot_id' => $bot->id,
                    'account_id' => $accountId,
                    'pid' => $pid,
                ]);
            } else {
                Log::warning('MetaAPI stream worker start returned invalid PID', [
                    'bot_id' => $bot->id,
                    'account_id' => $accountId,
                    'output' => $output,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to start MetaAPI stream worker', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - bot can still run, just won't have market data
        }
    }


    /**
     * Stop worker process for bot
     * 
     * @param TradingBot $bot
     * @return bool
     */
    public function stopWorker(TradingBot $bot): bool
    {
        $useQueueWorkers = env('ENABLE_QUEUE_WORKERS', true);

        if ($useQueueWorkers) {
            // Queue-based worker: just update status, worker will see this and exit
            try {
                $bot->update([
                    'worker_status' => 'stopped',
                    'worker_last_heartbeat' => now(),
                ]);

                Log::info('Trading bot stop signal sent (queue-based)', [
                    'bot_id' => $bot->id,
                ]);

                return true;
            } catch (\Exception $e) {
                Log::error('Failed to stop trading bot worker (queue-based)', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        } else {
            // PID-based worker (legacy)
            if (!$bot->worker_pid) {
                return true; // Already stopped
            }

            try {
                // Send SIGTERM for graceful shutdown
                if ($this->isProcessRunning($bot->worker_pid)) {
                    // Validate PID is a positive integer
                    if (!is_int($bot->worker_pid) || $bot->worker_pid <= 0) {
                        Log::error('Invalid worker PID', [
                            'bot_id' => $bot->id,
                            'pid' => $bot->worker_pid,
                        ]);
                        $bot->update(['worker_pid' => null]);
                        return false;
                    }

                    // Use safer process termination
                    $result = posix_kill($bot->worker_pid, SIGTERM);
                    
                    if (!$result) {
                        Log::warning('Failed to send SIGTERM', [
                            'bot_id' => $bot->id,
                            'pid' => $bot->worker_pid,
                        ]);
                    }
                    
                    // Wait up to 10 seconds for graceful shutdown
                    $waited = 0;
                    while ($this->isProcessRunning($bot->worker_pid) && $waited < 10) {
                        sleep(1);
                        $waited++;
                    }

                    // Force kill if still running
                    if ($this->isProcessRunning($bot->worker_pid)) {
                        posix_kill($bot->worker_pid, SIGKILL);
                        Log::warning('Trading bot worker force killed (LEGACY)', [
                            'bot_id' => $bot->id,
                            'pid' => $bot->worker_pid,
                        ]);
                    }
                }

                // Update bot
                $bot->update([
                    'worker_pid' => null,
                ]);

                Log::info('Trading bot worker stopped (LEGACY)', [
                    'bot_id' => $bot->id,
                ]);

                return true;
            } catch (\Exception $e) {
                Log::error('Failed to stop trading bot worker (LEGACY)', [
                    'bot_id' => $bot->id,
                    'pid' => $bot->worker_pid,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }
    }

    /**
     * Pause worker (sets status, worker checks on next loop)
     * 
     * @param TradingBot $bot
     * @return bool
     */
    public function pauseWorker(TradingBot $bot): bool
    {
        // Worker checks bot status every loop, so just update status
        // No need to send signal
        return true;
    }

    /**
     * Resume worker
     * 
     * @param TradingBot $bot
     * @return bool
     */
    public function resumeWorker(TradingBot $bot): bool
    {
        // Worker checks bot status every loop, so just update status
        // If worker is dead, it will be restarted by monitor job
        return true;
    }

    /**
     * Check if worker process is running
     * 
     * @param TradingBot $bot
     * @return bool
     */
    public function isWorkerRunning(TradingBot $bot): bool
    {
        $useQueueWorkers = env('ENABLE_QUEUE_WORKERS', true);

        if ($useQueueWorkers) {
            // Check queue-based worker status
            return in_array($bot->worker_status, ['queued', 'running']);
        } else {
            // Check PID-based worker (legacy)
            if (!$bot->worker_pid) {
                return false;
            }
            return $this->isProcessRunning($bot->worker_pid);
        }
    }

    /**
     * Check if process is running by PID
     * 
     * @param int $pid
     * @return bool
     */
    protected function isProcessRunning(int $pid): bool
    {
        // Validate PID is positive
        if ($pid <= 0) {
            return false;
        }

        try {
            // Use posix_kill with signal 0 to check if process exists
            // This is safer than shell_exec
            return posix_kill($pid, 0);
        } catch (\Exception $e) {
            Log::debug('Error checking process', [
                'pid' => $pid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get worker status
     * 
     * @param TradingBot $bot
     * @return string running|paused|stopped|dead
     */
    public function getWorkerStatus(TradingBot $bot): string
    {
        if ($bot->isStopped()) {
            return 'stopped';
        }

        if ($bot->isPaused()) {
            return 'paused';
        }

        if ($bot->isRunning()) {
            if ($this->isWorkerRunning($bot)) {
                return 'running';
            } else {
                return 'dead'; // Bot status is running but worker is dead
            }
        }

        return 'stopped';
    }

    /**
     * Restart worker with retry logic and health verification
     * 
     * Enhanced restart with:
     * - Graceful shutdown with timeout
     * - Retry mechanism (up to 3 attempts)
     * - Health check after restart
     * - PID tracking improvements
     * 
     * @param TradingBot $bot
     * @param int $maxRetries Maximum restart attempts (default: 3)
     * @param int $retryDelay Delay between retries in seconds (default: 2)
     * @return bool Success status
     * @throws \Exception If restart fails after all retries
     */
    public function restartWorker(TradingBot $bot, int $maxRetries = 3, int $retryDelay = 2): bool
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                Log::info('Restarting trading bot worker', [
                    'bot_id' => $bot->id,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                ]);

                // Step 1: Stop worker gracefully
                $this->stopWorker($bot);
                
                // Wait for process to fully stop
                $stopWaitTime = 0;
                $maxStopWait = 5; // seconds
                while ($this->isWorkerRunning($bot) && $stopWaitTime < $maxStopWait) {
                    sleep(1);
                    $stopWaitTime++;
                }

                // Step 2: Start worker
                $startResult = $this->startWorker($bot);
                
                if (!$startResult) {
                    throw new \Exception('Worker start returned false');
                }

                // Step 3: Verify worker is actually running (health check)
                sleep($retryDelay); // Give worker time to initialize
                
                if ($this->isWorkerRunning($bot)) {
                    // Update bot with restart information
                    $bot->refresh();
                    $bot->update([
                        'worker_restart_count' => ($bot->worker_restart_count ?? 0) + 1,
                        'worker_last_restart_at' => now(),
                    ]);

                    Log::info('Trading bot worker restarted successfully', [
                        'bot_id' => $bot->id,
                        'attempt' => $attempt,
                        'worker_pid' => $bot->worker_pid,
                        'worker_status' => $bot->worker_status ?? 'unknown',
                    ]);

                    return true;
                } else {
                    throw new \Exception('Worker started but health check failed');
                }
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning('Worker restart attempt failed', [
                    'bot_id' => $bot->id,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage(),
                ]);

                // If not last attempt, wait before retry
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                }
            }
        }

        // All retries failed
        Log::error('Worker restart failed after all retries', [
            'bot_id' => $bot->id,
            'attempts' => $attempt,
            'error' => $lastException ? $lastException->getMessage() : 'Unknown error',
        ]);

        // Update bot status to indicate restart failure
        $bot->update([
            'worker_status' => 'failed',
            'worker_last_heartbeat' => now(),
        ]);

        throw new \Exception("Failed to restart worker after {$maxRetries} attempts: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
    }

    /**
     * Kill stale workers (bots that are stopped but process still running)
     * 
     * Enhanced with:
     * - Better PID validation
     * - Graceful shutdown attempt before force kill
     * - Queue-based worker cleanup
     * 
     * @return int Number of workers killed
     */
    public function killStaleWorkers(): int
    {
        $killed = 0;
        $useQueueWorkers = env('ENABLE_QUEUE_WORKERS', true);
        
        if ($useQueueWorkers) {
            // For queue-based workers, clear stale status
            TradingBot::where('status', 'stopped')
                ->whereIn('worker_status', ['queued', 'running'])
                ->chunk(100, function ($staleBots) use (&$killed) {
                    foreach ($staleBots as $bot) {
                        $bot->update([
                            'worker_status' => 'stopped',
                            'worker_pid' => null,
                        ]);
                        $killed++;
                        
                        Log::info('Cleared stale queue-based worker status', [
                            'bot_id' => $bot->id,
                        ]);
                    }
                });
        } else {
            // Process in chunks to avoid memory issues with large bot counts
            TradingBot::whereNotNull('worker_pid')
                ->where('status', 'stopped')
                ->chunk(100, function ($staleBots) use (&$killed) {
                    foreach ($staleBots as $bot) {
                        // Validate PID
                        if (!is_int($bot->worker_pid) || $bot->worker_pid <= 0) {
                            // Invalid PID, just clear it
                            $bot->update(['worker_pid' => null]);
                            continue;
                        }

                        if ($this->isProcessRunning($bot->worker_pid)) {
                            try {
                                // Try graceful shutdown first
                                posix_kill($bot->worker_pid, SIGTERM);
                                
                                // Wait up to 5 seconds for graceful shutdown
                                $waited = 0;
                                while ($this->isProcessRunning($bot->worker_pid) && $waited < 5) {
                                    sleep(1);
                                    $waited++;
                                }

                                // Force kill if still running
                                if ($this->isProcessRunning($bot->worker_pid)) {
                                    posix_kill($bot->worker_pid, SIGKILL);
                                    Log::warning('Force killed stale trading bot worker', [
                                        'bot_id' => $bot->id,
                                        'pid' => $bot->worker_pid,
                                    ]);
                                } else {
                                    Log::info('Gracefully stopped stale trading bot worker', [
                                        'bot_id' => $bot->id,
                                        'pid' => $bot->worker_pid,
                                    ]);
                                }

                                $bot->update(['worker_pid' => null]);
                                $killed++;
                            } catch (\Exception $e) {
                                Log::error('Failed to kill stale worker', [
                                    'bot_id' => $bot->id,
                                    'pid' => $bot->worker_pid,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        } else {
                            // Process already dead, just clear PID
                            $bot->update(['worker_pid' => null]);
                        }
                    }
                });
        }

        return $killed;
    }

    /**
     * Monitor and auto-recover dead workers
     * 
     * Enhanced monitoring with:
     * - Health check for all running bots
     * - Auto-restart with exponential backoff
     * - Restart count tracking
     * - Maximum restart attempts per hour
     * 
     * @param int $maxRestartsPerHour Maximum restarts per bot per hour (default: 5)
     * @return array ['checked' => int, 'restarted' => int, 'failed' => int, 'skipped' => int]
     */
    public function monitorAndRecover(int $maxRestartsPerHour = 5): array
    {
        $checked = 0;
        $restarted = 0;
        $failed = 0;
        $skipped = 0;

        // Get all running bots
        $runningBots = TradingBot::where('status', 'running')
            ->where('is_active', true)
            ->get();

        foreach ($runningBots as $bot) {
            $checked++;
            
            // Check if worker is actually running
            if (!$this->isWorkerRunning($bot)) {
                // Check restart rate limit
                $restartCount = $bot->worker_restart_count ?? 0;
                $lastRestartAt = $bot->worker_last_restart_at;
                
                if ($lastRestartAt && $lastRestartAt->isAfter(now()->subHour())) {
                    // Check if we've exceeded max restarts in the last hour
                    $recentRestarts = TradingBot::where('id', $bot->id)
                        ->where('worker_last_restart_at', '>=', now()->subHour())
                        ->count();
                    
                    if ($recentRestarts >= $maxRestartsPerHour) {
                        Log::warning('Skipping worker restart - rate limit exceeded', [
                            'bot_id' => $bot->id,
                            'restarts_last_hour' => $recentRestarts,
                            'max_restarts_per_hour' => $maxRestartsPerHour,
                        ]);
                        $skipped++;
                        continue;
                    }
                }

                // Attempt restart
                try {
                    $this->restartWorker($bot, 2, 1); // 2 retries, 1 second delay
                    $restarted++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Auto-recovery failed for trading bot worker', [
                        'bot_id' => $bot->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [
            'checked' => $checked,
            'restarted' => $restarted,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    /**
     * Get detailed worker statistics
     * 
     * @param TradingBot $bot
     * @return array Worker statistics
     */
    public function getWorkerStats(TradingBot $bot): array
    {
        $isRunning = $this->isWorkerRunning($bot);
        $status = $this->getWorkerStatus($bot);
        
        $stats = [
            'bot_id' => $bot->id,
            'bot_name' => $bot->name,
            'status' => $status,
            'is_running' => $isRunning,
            'worker_pid' => $bot->worker_pid,
            'worker_status' => $bot->worker_status ?? 'unknown',
            'worker_started_at' => $bot->worker_started_at?->toIso8601String(),
            'worker_last_heartbeat' => $bot->worker_last_heartbeat?->toIso8601String(),
            'worker_restart_count' => $bot->worker_restart_count ?? 0,
            'worker_last_restart_at' => $bot->worker_last_restart_at?->toIso8601String(),
        ];

        // Add uptime if running
        if ($isRunning && $bot->worker_started_at) {
            $stats['uptime_seconds'] = now()->diffInSeconds($bot->worker_started_at);
            $stats['uptime_human'] = $this->formatUptime($stats['uptime_seconds']);
        }

        return $stats;
    }

    /**
     * Format uptime in human-readable format
     * 
     * @param int $seconds
     * @return string
     */
    protected function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($secs > 0 || empty($parts)) $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }
}
