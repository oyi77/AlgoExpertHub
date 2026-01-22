<?php

namespace Addons\TradingManagement\Modules\TradingBot\Jobs;

use Addons\TradingManagement\Modules\TradingBot\Jobs\BotConfigListenerJob;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Workers\ProcessMarketStreamBotWorker;
use Addons\TradingManagement\Modules\TradingBot\Workers\ProcessSignalBasedBotWorker;
use App\Services\LogRotationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TradingBotWorkerJob
 * 
 * Queue-based worker for trading bots
 * Replaces the PID-based process management with a robust queue system
 */
class TradingBotWorkerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     * Set to 1 to prevent automatic retries (bot lifecycle is managed by status)
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     * Set to 0 for infinite (long-running worker)
     */
    public $timeout = 0;

    /**
     * The bot ID to process
     */
    protected int $botId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $botId)
    {
        $this->botId = $botId;
        $this->onQueue('trading-bots'); // Dedicated queue for trading bots
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot = TradingBot::find($this->botId);

        if (!$bot) {
            Log::warning("Trading bot not found, exiting worker", ['bot_id' => $this->botId]);
            return;
        }

        // Setup dedicated logging for this bot
        $this->setupBotLogger($bot->id);

        try {
            // START listener when bot starts
            if ($bot->status === 'running' || $bot->status === 'paused') {
                dispatch(new BotConfigListenerJob($bot->id, 'subscribe'));
                Log::info('Bot config listener started', ['bot_id' => $bot->id]);
            }

            Log::info('Trading bot worker job started', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'trading_mode' => $bot->trading_mode,
                'status' => $bot->status,
                'queue_job_id' => $this->job->getJobId(),
            ]);

            // Update bot status to indicate worker is running
            $bot->update([
                'worker_status' => 'running',
                'worker_started_at' => now(),
                'worker_last_heartbeat' => now(),
            ]);

            // Determine worker type based on trading mode
            if ($bot->trading_mode === 'MARKET_STREAM_BASED') {
                $worker = new ProcessMarketStreamBotWorker($bot);
                Log::info('Using ProcessMarketStreamBotWorker', ['bot_id' => $bot->id]);
            } else {
                $worker = new ProcessSignalBasedBotWorker($bot);
                Log::info('Using ProcessSignalBasedBotWorker', ['bot_id' => $bot->id]);
            }

            // Main worker loop
            $iteration = 0;
            $shouldExit = false;

            while (!$shouldExit) {
                try {
                    $iteration++;

                    // Refresh bot from database to get latest status
                    $bot->refresh();

                    // Update heartbeat every iteration
                    if ($iteration % 10 === 0) {
                        $bot->update(['worker_last_heartbeat' => now()]);
                        
                        Log::debug('Trading bot worker heartbeat', [
                            'bot_id' => $bot->id,
                            'iteration' => $iteration,
                            'status' => $bot->status,
                        ]);
                    }

                    // Check if bot should stop
                    if ($bot->isStopped()) {
                        Log::info("Bot stopped, exiting worker gracefully", ['bot_id' => $bot->id]);
                        $shouldExit = true;
                        break;
                    }

                    // If paused, just wait
                    if ($bot->isPaused()) {
                        Log::debug("Bot paused, waiting...", ['bot_id' => $bot->id]);
                        sleep(5);
                        continue;
                    }

                    // Run worker iteration
                    Log::debug('Running worker iteration', [
                        'bot_id' => $bot->id,
                        'iteration' => $iteration
                    ]);
                    
                    $worker->run();

                    // Sleep for configured interval
                    $interval = $bot->position_monitoring_interval ?? 5;
                    sleep($interval);

                } catch (\Exception $e) {
                    Log::error('Trading bot worker error', [
                        'bot_id' => $bot->id,
                        'iteration' => $iteration,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Back off on error to prevent tight error loops
                    sleep(10);
                }
            }

            // Cleanup: Update bot status
            $bot->update([
                'worker_status' => 'stopped',
                'worker_last_heartbeat' => now(),
            ]);

            Log::info("Trading bot worker job stopped", [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'total_iterations' => $iteration,
            ]);
        } finally {
            // STOP listener in finally (ALWAYS call)
            dispatch(new BotConfigListenerJob($this->botId, 'unsubscribe'));
            Log::info('Bot config listener stopped', ['bot_id' => $this->botId]);
        }
    }

    /**
     * Setup custom logger for this bot
     */
    protected function setupBotLogger(int $botId): void
    {
        $logPath = storage_path("logs/trading-bot-{$botId}.log");

        // Rotate log file if needed
        $logRotation = app(LogRotationService::class);
        $logRotation->rotateIfNeeded($logPath, 1000);

        // Configure a custom log channel for this bot
        config(['logging.channels.trading-bot-' . $botId => [
            'driver' => 'single',
            'path' => $logPath,
            'level' => env('LOG_LEVEL', 'debug'),
        ]]);

        // Set this as the default channel for this process
        config(['logging.default' => 'trading-bot-' . $botId]);

        // Clear the log manager cache to pick up the new config
        app()->forgetInstance('log');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Trading bot worker job failed', [
            'bot_id' => $this->botId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Update bot status to indicate failure
        $bot = TradingBot::find($this->botId);
        if ($bot) {
            $bot->update([
                'worker_status' => 'failed',
                'worker_last_heartbeat' => now(),
            ]);
        }
    }
}
