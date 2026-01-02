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
            // Dispatch job to queue
            \Addons\TradingManagement\Modules\TradingBot\Jobs\TradingBotWorkerJob::dispatch($bot->id);

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
                'error' => $e->getMessage(),
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
     * Restart worker
     * 
     * @param TradingBot $bot
     * @return int New process ID
     * @throws \Exception
     */
    public function restartWorker(TradingBot $bot): int
    {
        $this->stopWorker($bot);
        sleep(1); // Brief pause
        return $this->startWorker($bot);
    }

    /**
     * Kill stale workers (bots that are stopped but process still running)
     * 
     * @return int Number of workers killed
     */
    public function killStaleWorkers(): int
    {
        $killed = 0;
        
        // Process in chunks to avoid memory issues with large bot counts
        TradingBot::whereNotNull('worker_pid')
            ->where('status', 'stopped')
            ->chunk(100, function ($staleBots) use (&$killed) {
                foreach ($staleBots as $bot) {
                    if ($this->isProcessRunning($bot->worker_pid)) {
                        try {
                            exec("kill -9 {$bot->worker_pid} 2>&1");
                            $bot->update(['worker_pid' => null]);
                            $killed++;
                            
                            Log::warning('Killed stale trading bot worker', [
                                'bot_id' => $bot->id,
                                'pid' => $bot->worker_pid,
                            ]);
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

        return $killed;
    }
}
