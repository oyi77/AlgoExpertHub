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
        return Helper::buildPhpCommand('', base_path());
    }

    /**
     * Start worker process for bot
     * 
     * @param TradingBot $bot
     * @return int Process ID
     * @throws \Exception
     */
    public function startWorker(TradingBot $bot): int
    {
        if ($this->isWorkerRunning($bot)) {
            throw new \Exception('Worker is already running for this bot');
        }

        $phpConfig = $this->getPhpCommand();
        $phpCommand = $phpConfig['command'];
        $workPath = $phpConfig['path'];
        $artisanPath = $workPath . '/artisan';
        
        $command = [$phpCommand, $artisanPath, "trading-bot:worker", (string)$bot->id];

        try {
            // Start process in background using nohup
            $logPath = storage_path("logs/trading-bot-{$bot->id}.log");
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
            
            Log::info('Executing worker start command', [
                'bot_id' => $bot->id,
                'command' => $commandString,
            ]);
            
            $output = shell_exec($commandString);
            $pid = (int) trim($output);
            
            Log::info('Worker start command output', [
                'bot_id' => $bot->id,
                'raw_output' => $output,
                'parsed_pid' => $pid,
            ]);
            
            if ($pid <= 0) {
                // Check if process actually started by checking log file
                sleep(1); // Wait a moment for process to start
                if (file_exists($logPath)) {
                    $logContent = file_get_contents($logPath);
                    Log::warning('Worker start returned invalid PID but log file exists', [
                        'bot_id' => $bot->id,
                        'pid' => $pid,
                        'log_preview' => substr($logContent, 0, 500),
                    ]);
                }
                throw new \Exception('Failed to start worker process - no PID returned. Output: ' . ($output ?: 'empty'));
            }

            // Update bot with worker PID
            $bot->update([
                'worker_pid' => $pid,
                'worker_started_at' => now(),
            ]);

            Log::info('Trading bot worker started', [
                'bot_id' => $bot->id,
                'pid' => $pid,
            ]);

            // Auto-start MetaAPI stream worker if bot uses MARKET_STREAM_BASED mode
            if ($bot->trading_mode === 'MARKET_STREAM_BASED') {
                // Refresh bot to get relationships
                $bot->refresh();
                $bot->load('dataConnection');
                
                Log::info('Checking if MetaAPI stream worker needed', [
                    'bot_id' => $bot->id,
                    'trading_mode' => $bot->trading_mode,
                    'has_data_connection' => !is_null($bot->dataConnection),
                    'data_connection_id' => $bot->data_connection_id,
                ]);
                
                if ($bot->dataConnection) {
                    $this->ensureMetaApiStreamWorker($bot);
                } else {
                    Log::warning('Bot uses MARKET_STREAM_BASED but has no data connection', [
                        'bot_id' => $bot->id,
                        'data_connection_id' => $bot->data_connection_id,
                    ]);
                }
            }

            return $pid;
        } catch (\Exception $e) {
            Log::error('Failed to start trading bot worker', [
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
        if (!$bot->worker_pid) {
            return true; // Already stopped
        }

        try {
            // Send SIGTERM for graceful shutdown
            if ($this->isProcessRunning($bot->worker_pid)) {
                exec("kill -TERM {$bot->worker_pid} 2>&1");
                
                // Wait up to 10 seconds for graceful shutdown
                $waited = 0;
                while ($this->isProcessRunning($bot->worker_pid) && $waited < 10) {
                    sleep(1);
                    $waited++;
                }

                // Force kill if still running
                if ($this->isProcessRunning($bot->worker_pid)) {
                    exec("kill -9 {$bot->worker_pid} 2>&1");
                    Log::warning('Trading bot worker force killed', [
                        'bot_id' => $bot->id,
                        'pid' => $bot->worker_pid,
                    ]);
                }
            }

            // Update bot
            $bot->update([
                'worker_pid' => null,
            ]);

            Log::info('Trading bot worker stopped', [
                'bot_id' => $bot->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to stop trading bot worker', [
                'bot_id' => $bot->id,
                'pid' => $bot->worker_pid,
                'error' => $e->getMessage(),
            ]);
            return false;
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
        if (!$bot->worker_pid) {
            return false;
        }

        return $this->isProcessRunning($bot->worker_pid);
    }

    /**
     * Check if process is running by PID
     * 
     * @param int $pid
     * @return bool
     */
    protected function isProcessRunning(int $pid): bool
    {
        try {
            $output = shell_exec("ps -p {$pid} -o pid= 2>&1");
            return !empty(trim($output));
        } catch (\Exception $e) {
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
        
        $staleBots = TradingBot::whereNotNull('worker_pid')
            ->where('status', 'stopped')
            ->get();

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

        return $killed;
    }
}
