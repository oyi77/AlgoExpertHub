<?php

namespace Addons\TradingBotSignalAddon\App\Console\Commands;

use Addons\TradingBotSignalAddon\App\Services\FirebaseService;
use Addons\TradingBotSignalAddon\App\Services\FirebaseAuthService;
use Addons\TradingBotSignalAddon\App\Services\SignalProcessorService;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Console\Command;

class SyncFirebaseDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trading-bot:sync 
                            {--all : Sync all data from Firebase}
                            {--notifications : Sync notifications only}
                            {--signals : Sync signals only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all data from Firebase (one-time sync)';

    protected $firebaseService;
    protected $processorService;

    public function __construct(
        SignalProcessorService $processorService
    ) {
        parent::__construct();
        $authService = new FirebaseAuthService();
        $this->firebaseService = new FirebaseService($authService);
        $this->processorService = $processorService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Firebase data sync...');

        // Get or create channel source
        $channelSource = ChannelSource::where('type', 'trading_bot')
            ->where('name', 'Trading Bot Firebase')
            ->first();

        if (!$channelSource) {
            $channelSource = ChannelSource::create([
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

        $this->processorService->setChannelSource($channelSource);

        $syncAll = $this->option('all');
        $syncNotifications = $this->option('notifications') || $syncAll;
        $syncSignals = $this->option('signals') || $syncAll;

        if ($syncNotifications) {
            $this->syncNotifications();
        }

        if ($syncSignals) {
            $this->syncSignals();
        }

        $this->info('Sync completed!');
        return 0;
    }

    /**
     * Sync all notifications
     */
    protected function syncNotifications(): void
    {
        $this->info('Syncing notifications...');
        $page = 1;
        $total = 0;

        do {
            $notifications = $this->firebaseService->getAllNotifications($page, 300);
            $count = count($notifications);

            foreach ($notifications as $notification) {
                $this->processorService->processNotification($notification);
                $total++;
            }

            $this->info("Processed page {$page}: {$count} notifications (Total: {$total})");
            $page++;

            // Limit to prevent infinite loop
            if ($page > 100) {
                $this->warn('Reached page limit (100). Stopping sync.');
                break;
            }
        } while ($count > 0);

        $this->info("Total notifications synced: {$total}");
    }

    /**
     * Sync all signals
     */
    protected function syncSignals(): void
    {
        $this->info('Syncing signals...');
        $page = 1;
        $total = 0;

        do {
            $signals = $this->firebaseService->getAllSignals($page, 300);
            $count = count($signals);

            foreach ($signals as $signal) {
                $this->processorService->processSignal($signal);
                $total++;
            }

            $this->info("Processed page {$page}: {$count} signals (Total: {$total})");
            $page++;

            // Limit to prevent infinite loop
            if ($page > 100) {
                $this->warn('Reached page limit (100). Stopping sync.');
                break;
            }
        } while ($count > 0);

        $this->info("Total signals synced: {$total}");
    }
}

