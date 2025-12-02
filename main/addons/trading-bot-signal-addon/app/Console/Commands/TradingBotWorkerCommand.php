<?php

namespace Addons\TradingBotSignalAddon\App\Console\Commands;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\TradingBotSignalAddon\App\Listeners\FuturesSignalListener;
use Addons\TradingBotSignalAddon\App\Listeners\NotificationListener;
use Addons\TradingBotSignalAddon\App\Listeners\SpotSignalListener;
use Addons\TradingBotSignalAddon\App\Services\FirebaseService;
use Addons\TradingBotSignalAddon\App\Services\FirebaseAuthService;
use Addons\TradingBotSignalAddon\App\Services\SignalProcessorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TradingBotWorkerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trading-bot:worker 
                            {--interval=90 : Polling interval in seconds}
                            {--once : Run once and exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run trading bot worker to fetch and process signals from Firebase';

    protected $firebaseService;
    protected $processorService;
    protected $notificationListener;
    protected $spotSignalListener;
    protected $futuresSignalListener;
    protected $channelSource;
    protected $running = true;

    public function __construct(
        SignalProcessorService $processorService,
        NotificationListener $notificationListener,
        SpotSignalListener $spotSignalListener,
        FuturesSignalListener $futuresSignalListener
    ) {
        parent::__construct();
        $authService = new FirebaseAuthService();
        $this->firebaseService = new FirebaseService($authService);
        $this->processorService = $processorService;
        $this->notificationListener = $notificationListener;
        $this->spotSignalListener = $spotSignalListener;
        $this->futuresSignalListener = $futuresSignalListener;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Trading Bot Worker...');

        // Find or create trading bot channel source
        $this->channelSource = $this->getOrCreateChannelSource();
        $this->processorService->setChannelSource($this->channelSource);

        // Test Firebase connection
        $connectionTest = $this->firebaseService->testConnection();
        if (!$connectionTest['success']) {
            $this->error('Firebase connection failed: ' . $connectionTest['message']);
            return 1;
        }

        $this->info('Firebase connection successful');
        $interval = (int) $this->option('interval');
        $runOnce = $this->option('once');

        // Setup signal handlers for graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleShutdown']);
            pcntl_signal(SIGINT, [$this, 'handleShutdown']);
        }

        do {
            try {
                $this->processSignals();
                $this->info('Processed signals. Waiting ' . $interval . ' seconds...');
            } catch (\Exception $e) {
                $this->error('Error processing signals: ' . $e->getMessage());
                Log::error('TradingBotWorker error: ' . $e->getMessage());
            }

            if (!$runOnce) {
                sleep($interval);
            }

            // Handle signals if pcntl is available
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        } while (!$runOnce && $this->running);

        $this->info('Trading Bot Worker stopped');
        return 0;
    }

    /**
     * Process signals from Firebase
     */
    protected function processSignals(): void
    {
        $config = config('trading-bot.listeners');
        $lastNotificationTimestamp = Cache::get('trading_bot_last_notification_timestamp');
        $lastSignalTimestamp = Cache::get('trading_bot_last_signal_timestamp');

        // Process notifications
        if ($config['notification_listener'] ?? true) {
            $notifications = $this->firebaseService->getNewNotifications($lastNotificationTimestamp);
            $this->info('Fetched ' . count($notifications) . ' new notifications');

            foreach ($notifications as $notification) {
                $this->notificationListener->handle($notification);
                
                // Update last processed timestamp
                if (!empty($notification['timestamp'])) {
                    $timestamp = is_numeric($notification['timestamp']) 
                        ? (int)$notification['timestamp'] 
                        : strtotime($notification['timestamp']);
                    
                    if (!$lastNotificationTimestamp || $timestamp > $lastNotificationTimestamp) {
                        $lastNotificationTimestamp = $timestamp;
                    }
                }
            }

            if ($lastNotificationTimestamp) {
                Cache::put('trading_bot_last_notification_timestamp', $lastNotificationTimestamp);
            }
        }

        // Process signals
        $signals = $this->firebaseService->getNewSignals($lastSignalTimestamp);
        $this->info('Fetched ' . count($signals) . ' new signals');

        foreach ($signals as $signal) {
            // Route to appropriate listener
            if ($config['spot_signal_listener'] ?? true) {
                $this->spotSignalListener->handle($signal);
            }
            
            if ($config['futures_signal_listener'] ?? true) {
                $this->futuresSignalListener->handle($signal);
            }

            // Update last processed timestamp
            if (!empty($signal['timestamp'])) {
                $timestamp = is_numeric($signal['timestamp']) 
                    ? (int)$signal['timestamp'] 
                    : strtotime($signal['timestamp']);
                
                if (!$lastSignalTimestamp || $timestamp > $lastSignalTimestamp) {
                    $lastSignalTimestamp = $timestamp;
                }
            }
        }

        if ($lastSignalTimestamp) {
            Cache::put('trading_bot_last_signal_timestamp', $lastSignalTimestamp);
        }
    }

    /**
     * Get or create trading bot channel source
     */
    protected function getOrCreateChannelSource(): ChannelSource
    {
        $source = ChannelSource::where('type', 'trading_bot')
            ->where('name', 'Trading Bot Firebase')
            ->first();

        if (!$source) {
            $source = ChannelSource::create([
                'name' => 'Trading Bot Firebase',
                'type' => 'trading_bot',
                'config' => [
                    'source_type' => 'firebase',
                    'firebase_project_id' => config('trading-bot.firebase.project_id'),
                ],
                'status' => 'active',
                'is_admin_owned' => true,
                'user_id' => null,
            ]);
        }

        return $source;
    }

    /**
     * Handle shutdown signal
     */
    public function handleShutdown(): void
    {
        $this->running = false;
        $this->info('Shutdown signal received...');
    }
}

