<?php

namespace Addons\TradingManagement\Modules\TradingBot\Console\Commands;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Workers\ProcessMarketStreamBotWorker;
use Addons\TradingManagement\Modules\TradingBot\Workers\ProcessSignalBasedBotWorker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

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
    protected ?Logger $botLogger = null;

    public function handle()
    {
        $botId = $this->argument('bot_id');
        $this->bot = TradingBot::findOrFail($botId);

        // Setup custom logger to write to bot's dedicated log file
        $this->setupBotLogger($botId);

        // Setup signal handlers for graceful shutdown
        $this->setupSignalHandlers();

        $this->info("Starting trading bot worker for: {$this->bot->name} (ID: {$this->bot->id})");
        
        // Log to bot's dedicated log file
        Log::info('Trading bot worker started', [
            'bot_id' => $this->bot->id,
            'bot_name' => $this->bot->name,
            'trading_mode' => $this->bot->trading_mode,
            'status' => $this->bot->status,
            'pid' => getmypid(),
        ]);

        // Determine worker type based on trading mode
        if ($this->bot->trading_mode === 'MARKET_STREAM_BASED') {
            $worker = new ProcessMarketStreamBotWorker($this->bot);
            Log::info('Using ProcessMarketStreamBotWorker', ['bot_id' => $this->bot->id]);
        } else {
            $worker = new ProcessSignalBasedBotWorker($this->bot);
            Log::info('Using ProcessSignalBasedBotWorker', ['bot_id' => $this->bot->id]);
        }

        // Main loop
        $iteration = 0;
        while (!$this->shouldExit) {
            try {
                $iteration++;
                
                // Log heartbeat every 10 iterations (to show worker is alive)
                if ($iteration % 10 === 0) {
                    Log::debug('Trading bot worker heartbeat', [
                        'bot_id' => $this->bot->id,
                        'iteration' => $iteration,
                        'status' => $this->bot->status,
                    ]);
                }
                
                // Check bot status (refresh from database)
                $this->bot->refresh();
                
                if ($this->bot->isStopped() || $this->bot->isPaused()) {
                    Log::info("Bot status changed to {$this->bot->status}, exiting gracefully", ['bot_id' => $this->bot->id]);
                    $this->info("Bot status changed to {$this->bot->status}, exiting gracefully");
                    break;
                }

                // Run worker iteration
                Log::debug('Running worker iteration', ['bot_id' => $this->bot->id, 'iteration' => $iteration]);
                $worker->run();

                // Sleep for configured interval
                $interval = $this->bot->position_monitoring_interval ?? 5;
                sleep($interval);

            } catch (\Exception $e) {
                // Use bot-specific logger if available, otherwise fallback to default
                if ($this->botLogger) {
                    $this->botLogger->error('Trading bot worker error', [
                        'bot_id' => $this->bot->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                } else {
                    Log::channel('trading-bot-' . $this->bot->id)->error('Trading bot worker error', [
                        'bot_id' => $this->bot->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
                
                $this->error("Error: {$e->getMessage()}");
                
                // Sleep before retry
                sleep(10);
            }
        }

        Log::info("Trading bot worker stopped", [
            'bot_id' => $this->bot->id,
            'bot_name' => $this->bot->name,
            'total_iterations' => $iteration ?? 0,
        ]);
        $this->info("Trading bot worker stopped for: {$this->bot->name}");
    }

    /**
     * Setup custom logger for this bot that writes to dedicated log file
     */
    protected function setupBotLogger(int $botId): void
    {
        $logPath = storage_path("logs/trading-bot-{$botId}.log");
        
        // Configure a custom log channel for this bot
        config(['logging.channels.trading-bot-' . $botId => [
            'driver' => 'single',
            'path' => $logPath,
            'level' => env('LOG_LEVEL', 'debug'),
        ]]);
        
        // Set this as the default channel for this process
        // This ensures all Log:: calls in workers write to the bot's log file
        config(['logging.default' => 'trading-bot-' . $botId]);
        
        // Clear the log manager cache to pick up the new config
        app()->forgetInstance('log');
        
        // Also create a direct logger instance for command output
        $logger = new Logger("trading-bot-{$botId}");
        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        $this->botLogger = $logger;
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
