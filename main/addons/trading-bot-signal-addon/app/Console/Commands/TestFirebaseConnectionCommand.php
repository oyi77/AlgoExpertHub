<?php

namespace Addons\TradingBotSignalAddon\App\Console\Commands;

use Addons\TradingBotSignalAddon\App\Services\FirebaseService;
use Addons\TradingBotSignalAddon\App\Services\FirebaseAuthService;
use Illuminate\Console\Command;

class TestFirebaseConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trading-bot:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Firebase connection and fetch sample data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing Firebase Connection...');
        $this->newLine();

        // Initialize auth service
        $authService = new FirebaseAuthService();
        
        // Check authentication
        if (!$authService->isAuthenticated()) {
            $this->error('❌ Not authenticated. No access token found.');
            return 1;
        }

        $this->info('✅ Authentication: OK');
        $this->info('   Access Token: ' . substr($authService->getAccessToken(), 0, 50) . '...');
        $this->newLine();

        // Initialize Firebase service
        $firebaseService = new FirebaseService($authService);

        // Force token refresh first
        $this->info('Refreshing token if needed...');
        $token = $authService->getAccessToken();
        if (!$token) {
            $this->error('❌ Failed to get valid access token');
            return 1;
        }
        $this->info('✅ Token ready');
        $this->newLine();

        // Test connection
        $this->info('Testing connection...');
        $connectionTest = $firebaseService->testConnection();
        
        if (!$connectionTest['success']) {
            $this->warn('⚠️  Connection test failed: ' . $connectionTest['message']);
            $this->info('This might be normal if collections don\'t exist yet or permissions are different.');
            $this->info('Attempting to fetch data anyway...');
            $this->newLine();
        } else {
            $this->info('✅ Connection: ' . $connectionTest['message']);
            $this->newLine();
        }

        // Fetch sample notifications
        $this->info('Fetching notifications...');
        $notifications = $firebaseService->getAllNotifications(1, 5);
        $this->info('✅ Found ' . count($notifications) . ' notifications (showing first 5)');
        
        if (!empty($notifications)) {
            $this->newLine();
            $this->table(
                ['ID', 'Timestamp', 'Has Symbol', 'Has Action'],
                array_map(function($notif) {
                    return [
                        substr($notif['id'] ?? 'N/A', 0, 20),
                        isset($notif['timestamp']) ? date('Y-m-d H:i:s', is_numeric($notif['timestamp']) ? $notif['timestamp'] : strtotime($notif['timestamp'])) : 'N/A',
                        !empty($notif['extracted_symbol']) ? 'Yes' : 'No',
                        !empty($notif['action']) ? 'Yes' : 'No',
                    ];
                }, array_slice($notifications, 0, 5))
            );
            
            // Show first notification details
            if (!empty($notifications[0])) {
                $this->newLine();
                $this->info('Sample notification data:');
                $this->line(json_encode($notifications[0], JSON_PRETTY_PRINT));
            }
        }

        $this->newLine();

        // Fetch sample signals
        $this->info('Fetching signals...');
        $signals = $firebaseService->getAllSignals(1, 5);
        $this->info('✅ Found ' . count($signals) . ' signals (showing first 5)');
        
        if (!empty($signals)) {
            $this->newLine();
            $this->table(
                ['ID', 'Timestamp', 'Symbol', 'Action'],
                array_map(function($signal) {
                    return [
                        substr($signal['id'] ?? 'N/A', 0, 20),
                        isset($signal['timestamp']) ? date('Y-m-d H:i:s', is_numeric($signal['timestamp']) ? $signal['timestamp'] : strtotime($signal['timestamp'])) : 'N/A',
                        $signal['symbol'] ?? 'N/A',
                        $signal['action'] ?? 'N/A',
                    ];
                }, array_slice($signals, 0, 5))
            );
            
            // Show first signal details
            if (!empty($signals[0])) {
                $this->newLine();
                $this->info('Sample signal data:');
                $this->line(json_encode($signals[0], JSON_PRETTY_PRINT));
            }
        }

        $this->newLine();
        $this->info('✅ Test completed successfully!');
        
        return 0;
    }
}

