<?php

namespace Addons\TradingManagement\Modules\TradingBot\Console\Commands;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Workers\ProcessMarketStreamBotWorker;
use Addons\TradingManagement\Modules\TradingBot\Workers\ProcessSignalBasedBotWorker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * TradingBotWorker Command
 * 
 * Long-running daemon process for individual trading bot
 * Runs continuously until bot status changes
 */
class TradingBotWorker extends Command
{
    protected $signature = 'trading-bot:worker {bot_id}';
    protected $description = 'Run trading bot worker daemon';

    protected TradingBot $bot;
    protected bool $shouldExit = false;

    public function handle()
    {
        $botId = $this->argument('bot_id');
        $this->bot = TradingBot::findOrFail($botId);

        // Setup signal handlers for graceful shutdown
        $this->setupSignalHandlers();

        $this->info("Starting trading bot worker for: {$this->bot->name} (ID: {$this->bot->id})");

        // Determine worker type based on trading mode
        if ($this->bot->trading_mode === 'MARKET_STREAM_BASED') {
            $worker = new ProcessMarketStreamBotWorker($this->bot);
        } else {
            $worker = new ProcessSignalBasedBotWorker($this->bot);
        }

        // Main loop
        while (!$this->shouldExit) {
            try {
                // Check bot status (refresh from database)
                $this->bot->refresh();
                
                if ($this->bot->isStopped() || $this->bot->isPaused()) {
                    $this->info("Bot status changed to {$this->bot->status}, exiting gracefully");
                    break;
                }

                // Run worker iteration
                $worker->run();

                // Sleep for configured interval
                $interval = $this->bot->position_monitoring_interval ?? 5;
                sleep($interval);

            } catch (\Exception $e) {
                Log::error('Trading bot worker error', [
                    'bot_id' => $this->bot->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $this->error("Error: {$e->getMessage()}");
                
                // Sleep before retry
                sleep(10);
            }
        }

        $this->info("Trading bot worker stopped for: {$this->bot->name}");
    }

    /**
     * Setup signal handlers for graceful shutdown
     */
    protected function setupSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }
    }

    /**
     * Handle shutdown signals
     */
    public function handleSignal($signal): void
    {
        $this->info("Received signal {$signal}, shutting down gracefully...");
        $this->shouldExit = true;
    }
}
