<?php

namespace Addons\TradingManagement\Modules\TradingBot\Console\Commands;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verify TestProj Bot Command
 * 
 * Comprehensive verification of TestProj bot configuration and readiness
 */
class VerifyTestProjBot extends Command
{
    protected $signature = 'trading-bot:verify-testproj';
    protected $description = 'Verify TestProj bot configuration and readiness for end-to-end testing';

    protected TradingBotWorkerService $workerService;
    protected TradingBotMonitoringService $monitoringService;

    public function __construct(
        TradingBotWorkerService $workerService,
        TradingBotMonitoringService $monitoringService
    ) {
        parent::__construct();
        $this->workerService = $workerService;
        $this->monitoringService = $monitoringService;
    }

    public function handle()
    {
        $this->info("=== TestProj Bot Verification ===");
        $this->newLine();

        // Find TestProj bot
        $bot = TradingBot::where('name', 'TestProj')->first();

        if (!$bot) {
            $this->error("❌ TestProj bot not found in database");
            $this->info("   Available bots:");
            $allBots = TradingBot::select('id', 'name', 'status')->get();
            foreach ($allBots as $b) {
                $this->info("     - ID: {$b->id}, Name: {$b->name}, Status: {$b->status}");
            }
            return 1;
        }

        $this->info("✅ Found TestProj bot (ID: {$bot->id})");
        $this->newLine();

        // Pre-Start Verification
        $this->info("=== Pre-Start Verification ===");
        $allPassed = true;

        // Check exchange connection
        if (!$bot->exchange_connection_id) {
            $this->error("❌ Bot has no exchange connection configured");
            $allPassed = false;
        } else {
            $connection = $bot->exchangeConnection;
            if (!$connection) {
                $this->error("❌ Exchange connection not found (ID: {$bot->exchange_connection_id})");
                $allPassed = false;
            } else {
                $provider = $connection->provider ?? 'N/A';
                $status = $connection->status ?? 'N/A';
                $this->info("✅ Exchange connection: {$connection->name}");
                $this->info("   Provider: {$provider}");
                $this->info("   Status: {$status}");
                
                if ($connection->status !== 'active') {
                    $this->warn("⚠️  Connection is not active. Status: {$connection->status}");
                    $allPassed = false;
                }

                // Test connection
                $this->info("   Testing connection...");
                try {
                    $connectionService = app(\Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService::class);
                    if (method_exists($connectionService, 'testConnection')) {
                        $testResult = $connectionService->testConnection($connection);
                        $success = isset($testResult['success']) ? $testResult['success'] : false;
                        if ($success) {
                            $this->info("   ✅ Connection test passed");
                        } else {
                            $message = isset($testResult['message']) ? $testResult['message'] : 'Unknown error';
                            $this->error("   ❌ Connection test failed: " . $message);
                            $allPassed = false;
                        }
                    } else {
                        $this->warn("   ⚠️  Connection test method not available");
                    }
                } catch (\Exception $e) {
                    $this->error("   ❌ Connection test error: " . $e->getMessage());
                    $allPassed = false;
                }
            }
        }

        // Check trading preset
        if (!$bot->trading_preset_id) {
            $this->error("❌ Bot has no trading preset configured");
            $allPassed = false;
        } else {
            $preset = $bot->tradingPreset;
            if (!$preset) {
                $this->error("❌ Trading preset not found (ID: {$bot->trading_preset_id})");
                $allPassed = false;
            } else {
                $sizingStrategy = $preset->position_sizing_strategy ?? 'N/A';
                $sizingValue = $preset->position_sizing_value ?? 'N/A';
                $this->info("✅ Trading preset: {$preset->name}");
                $this->info("   Position sizing: {$sizingStrategy}");
                $this->info("   Position value: {$sizingValue}");
            }
        }

        // Check bot status
        $this->info("Bot status: {$bot->status}");
        if ($bot->status !== 'stopped') {
            $this->warn("⚠️  Bot is not stopped. Current status: {$bot->status}");
            $this->info("   For clean testing, bot should be stopped first");
        }

        // Check queue workers
        $this->info("Checking queue workers...");
        try {
            $output = shell_exec("ps aux | grep 'queue:work' | grep -v grep | wc -l 2>&1");
            $queueWorkersCount = (int) trim($output);
            if ($queueWorkersCount > 0) {
                $this->info("✅ Queue workers running: {$queueWorkersCount}");
            } else {
                $this->error("❌ No queue workers running");
                $this->info("   Start queue workers: php artisan queue:work");
                $allPassed = false;
            }
        } catch (\Exception $e) {
            $this->warn("⚠️  Could not check queue workers: " . $e->getMessage());
        }

        // Check scheduler
        $this->info("Checking scheduler...");
        try {
            $output = shell_exec("crontab -l 2>/dev/null | grep schedule:run");
            if (!empty(trim($output))) {
                $this->info("✅ Scheduler configured in cron");
            } else {
                $this->warn("⚠️  Scheduler not found in cron");
                $this->info("   Add to crontab: * * * * * php /path/to/artisan schedule:run");
            }
        } catch (\Exception $e) {
            $this->warn("⚠️  Could not check scheduler: " . $e->getMessage());
        }

        $this->newLine();

        if (!$allPassed) {
            $this->error("❌ Pre-start verification failed. Please fix the issues above.");
            return 1;
        }

        $this->info("✅ Pre-start verification passed!");
        $this->newLine();

        // Start Verification
        $this->info("=== Start Verification ===");
        $this->info("To start the bot, run:");
        $this->info("  php artisan trading-bot:test-flow {$bot->id} --phase=worker");
        $this->info("Or start via UI: /admin/trading-management/trading-bots/{$bot->id}");
        $this->newLine();

        // Signal Execution Verification
        $this->info("=== Signal Execution Verification ===");
        $this->info("To test signal execution:");
        $this->info("  1. Create a signal matching bot's symbol/timeframe");
        $this->info("  2. Publish the signal (set is_published=1)");
        $this->info("  3. Check worker logs: tail -f storage/logs/laravel.log | grep 'bot_id\":{$bot->id}'");
        $this->info("  4. Check queue: php artisan queue:work --once --verbose");
        $this->info("  5. Verify position created in database");
        $this->newLine();

        // Monitoring Verification
        $this->info("=== Monitoring Verification ===");
        $this->info("Check monitoring UI:");
        $this->info("  /admin/trading-management/trading-bots/{$bot->id}");
        $this->info("  - Worker status panel");
        $this->info("  - Position monitoring dashboard");
        $this->info("  - Queue jobs status");
        $this->newLine();

        // System Health
        $this->info("=== System Health ===");
        $this->info("View system health dashboard:");
        $this->info("  /admin/trading-management/system-health");
        $this->newLine();

        $this->info("✅ Verification checklist complete!");
        $this->info("Ready for end-to-end testing.");

        return 0;
    }
}
