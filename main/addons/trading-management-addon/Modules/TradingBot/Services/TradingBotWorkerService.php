<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * TradingBotWorkerService
 * 
 * Manages background worker processes for trading bots
 */
class TradingBotWorkerService
{
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

        $artisanPath = base_path('artisan');
        $command = "php {$artisanPath} trading-bot:worker {$bot->id}";

        try {
            // Start process in background
            $process = Process::start($command, function ($type, $output) use ($bot) {
                Log::debug("Trading bot worker output", [
                    'bot_id' => $bot->id,
                    'type' => $type,
                    'output' => $output,
                ]);
            });

            $pid = $process->getPid();

            // Update bot with worker PID
            $bot->update([
                'worker_pid' => $pid,
                'worker_started_at' => now(),
            ]);

            Log::info('Trading bot worker started', [
                'bot_id' => $bot->id,
                'pid' => $pid,
            ]);

            return $pid;
        } catch (ProcessFailedException $e) {
            Log::error('Failed to start trading bot worker', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to start worker: ' . $e->getMessage());
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
                Process::run("kill -TERM {$bot->worker_pid}");
                
                // Wait up to 10 seconds for graceful shutdown
                $waited = 0;
                while ($this->isProcessRunning($bot->worker_pid) && $waited < 10) {
                    sleep(1);
                    $waited++;
                }

                // Force kill if still running
                if ($this->isProcessRunning($bot->worker_pid)) {
                    Process::run("kill -9 {$bot->worker_pid}");
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
            $result = Process::run("ps -p {$pid} -o pid=");
            return !empty(trim($result->output()));
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
                    Process::run("kill -9 {$bot->worker_pid}");
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
