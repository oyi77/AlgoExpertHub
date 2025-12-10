<?php

namespace Addons\TradingManagement\Modules\TradingBot\Console\Commands;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Test Trading Bot Flow Command
 * 
 * Tests complete trading bot flow from worker startup to order execution
 */
class TestTradingBotFlow extends Command
{
    protected $signature = 'trading-bot:test-flow {bot_id} {--phase=all : Test phase (worker|signal|execution|monitoring|closure|all)}';
    protected $description = 'Test complete trading bot flow end-to-end';

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
        $botId = $this->argument('bot_id');
        $phase = $this->option('phase');

        $bot = TradingBot::findOrFail($botId);

        $this->info("Testing Trading Bot: {$bot->name} (ID: {$bot->id})");
        $this->newLine();

        if ($phase === 'all' || $phase === 'worker') {
            $this->testWorkerStartup($bot);
        }

        if ($phase === 'all' || $phase === 'signal') {
            $this->testSignalExecution($bot);
        }

        if ($phase === 'all' || $phase === 'execution') {
            $this->testExecution($bot);
        }

        if ($phase === 'all' || $phase === 'monitoring') {
            $this->testPositionMonitoring($bot);
        }

        if ($phase === 'all' || $phase === 'closure') {
            $this->testPositionClosure($bot);
        }

        if ($phase === 'all' || $phase === 'error') {
            $this->testErrorHandling($bot);
        }

        $this->info("Testing completed!");
    }

    /**
     * Test Phase 1: Worker Startup
     */
    protected function testWorkerStartup(TradingBot $bot)
    {
        $this->info("=== Phase 1: Worker Startup ===");

        // Check if bot is stopped
        if ($bot->status !== 'stopped') {
            $this->warn("Bot is not stopped. Current status: {$bot->status}");
            if (!$this->confirm('Continue anyway?', false)) {
                return;
            }
        }

        // Check prerequisites
        $this->info("Checking prerequisites...");
        
        if (!$bot->exchange_connection_id) {
            $this->error("❌ Bot has no exchange connection configured");
            return;
        }
        $connectionName = $bot->exchangeConnection ? $bot->exchangeConnection->name : 'N/A';
        $this->info("✅ Exchange connection: {$connectionName}");

        if (!$bot->trading_preset_id) {
            $this->error("❌ Bot has no trading preset configured");
            return;
        }
        $presetName = $bot->tradingPreset ? $bot->tradingPreset->name : 'N/A';
        $this->info("✅ Trading preset: {$presetName}");

        // Start bot
        $this->info("Starting bot...");
        try {
            $bot->update(['status' => 'running']);
            $this->workerService->startWorker($bot);
            $this->info("✅ Bot started successfully");
        } catch (\Exception $e) {
            $this->error("❌ Failed to start bot: " . $e->getMessage());
            return;
        }

        // Wait a moment
        sleep(2);

        // Verify worker process
        $this->info("Verifying worker process...");
        $isRunning = $this->workerService->isWorkerRunning($bot);
        
        if ($isRunning) {
            $this->info("✅ Worker process is running (PID: {$bot->worker_pid})");
            
            // Check process details
            try {
                $output = shell_exec("ps -p {$bot->worker_pid} -o pid,etime,stat,cmd --no-headers 2>&1");
                if ($output) {
                    $this->info("   Process info: " . trim($output));
                }
            } catch (\Exception $e) {
                $this->warn("   Could not get process details: " . $e->getMessage());
            }
        } else {
            $this->error("❌ Worker process is NOT running");
        }

        // Check worker status
        $workerStatus = $this->workerService->getWorkerStatus($bot);
        $this->info("Worker status: {$workerStatus}");

        $this->newLine();
    }

    /**
     * Test Phase 2-3: Signal Execution
     */
    protected function testSignalExecution(TradingBot $bot)
    {
        $this->info("=== Phase 2-3: Signal Execution ===");

        // Check if bot is running
        if ($bot->status !== 'running') {
            $this->warn("Bot is not running. Current status: {$bot->status}");
            $this->info("Please start the bot first using: php artisan trading-bot:test-flow {$bot->id} --phase=worker");
            return;
        }

        // Check for existing signals
        $this->info("Checking for published signals...");
        $signals = \App\Models\Signal::where('is_published', 1)
            ->where('published_date', '>=', now()->subMinutes(10))
            ->get();

        if ($signals->count() === 0) {
            $this->warn("⚠️  No published signals found in last 10 minutes");
            $this->info("   To test signal execution:");
            $this->info("   1. Create a signal matching bot's symbol/timeframe");
            $this->info("   2. Publish the signal (set is_published=1)");
            $this->info("   3. Wait for worker to process (check logs)");
            return;
        }

        $this->info("✅ Found {$signals->count()} published signal(s)");

        // Check queue for ExecutionJob
        $this->info("Checking queue for ExecutionJob...");
        $pendingJobs = \DB::table('jobs')
            ->whereNull('reserved_at')
            ->get();

        $executionJobs = 0;
        foreach ($pendingJobs as $job) {
            $payload = json_decode($job->payload, true);
            if (strpos($payload['displayName'] ?? '', 'ExecutionJob') !== false) {
                $executionJobs++;
            }
        }

        if ($executionJobs > 0) {
            $this->info("✅ Found {$executionJobs} ExecutionJob(s) in queue");
        } else {
            $this->warn("⚠️  No ExecutionJob found in queue");
            $this->info("   This might be normal if:");
            $this->info("   - Signal doesn't match bot criteria");
            $this->info("   - Filter strategy rejected the signal");
            $this->info("   - Worker hasn't processed signal yet");
        }

        // Check for positions
        $this->info("Checking for created positions...");
        $positions = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition::where('bot_id', $bot->id)
            ->where('status', 'open')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($positions->count() > 0) {
            $this->info("✅ Found {$positions->count()} open position(s)");
            foreach ($positions as $position) {
                $this->info("   - Position ID: {$position->id}, Symbol: {$position->symbol}, Direction: {$position->direction}");
            }
        } else {
            $this->warn("⚠️  No open positions found");
        }

        $this->newLine();
    }

    /**
     * Test Phase 4: Position Monitoring
     */
    protected function testPositionMonitoring(TradingBot $bot)
    {
        $this->info("=== Phase 4: Position Monitoring ===");

        // Check for open positions
        $positions = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition::where('bot_id', $bot->id)
            ->where('status', 'open')
            ->get();

        if ($positions->count() === 0) {
            $this->warn("⚠️  No open positions to monitor");
            $this->info("   Create a position first or wait for signal execution");
            return;
        }

        $this->info("Found {$positions->count()} open position(s)");

        // Check MonitorPositionsJob
        $this->info("Checking MonitorPositionsJob...");
        
        // Check if job is scheduled
        $scheduleList = \Artisan::call('schedule:list');
        $this->info("   Run 'php artisan schedule:list' to see scheduled jobs");

        // Check recent position updates
        $recentUpdates = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::where('status', 'open')
            ->where('last_price_update_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentUpdates > 0) {
            $this->info("✅ Found {$recentUpdates} position(s) updated in last 5 minutes");
        } else {
            $this->warn("⚠️  No position updates in last 5 minutes");
            $this->info("   MonitorPositionsJob should run every minute");
            $this->info("   Check logs: tail -f storage/logs/laravel.log | grep MonitorPositionsJob");
        }

        // Display position details
        foreach ($positions as $position) {
            $currentPrice = $position->current_price ?? 'N/A';
            $profitLoss = $position->profit_loss ?? 0;
            $this->info("   Position {$position->id}:");
            $this->info("     Symbol: {$position->symbol}");
            $this->info("     Entry: {$position->entry_price}");
            $this->info("     Current: {$currentPrice}");
            $this->info("     P/L: $" . $profitLoss);
        }

        $this->newLine();
    }

    /**
     * Test Phase 5: Position Closure
     */
    protected function testPositionClosure(TradingBot $bot)
    {
        $this->info("=== Phase 5: Position Closure ===");

        // Check for recently closed positions
        $closedPositions = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition::where('bot_id', $bot->id)
            ->where('status', 'closed')
            ->where('closed_at', '>=', now()->subHours(24))
            ->get();

        if ($closedPositions->count() > 0) {
            $this->info("✅ Found {$closedPositions->count()} closed position(s) in last 24 hours");
            foreach ($closedPositions as $position) {
                $this->info("   - Position ID: {$position->id}, Close reason: {$position->close_reason}, P/L: $" . ($position->profit_loss ?? 0));
            }
        } else {
            $this->warn("⚠️  No closed positions found in last 24 hours");
            $this->info("   Positions will close when:");
            $this->info("   - Stop loss is hit");
            $this->info("   - Take profit is hit");
            $this->info("   - Manually closed");
        }

        $this->newLine();
    }

    /**
     * Test Phase 6: Error Handling
     */
    protected function testErrorHandling(TradingBot $bot)
    {
        $this->info("=== Phase 6: Error Handling ===");

        // Check error count
        $errorCount = $this->monitoringService->getBotMetrics($bot)['error_count_24h'] ?? 0;
        
        if ($errorCount > 0) {
            $this->warn("⚠️  Found {$errorCount} error(s) in last 24 hours");
            $this->info("   Check logs: tail -f storage/logs/laravel.log | grep 'bot_id\":{$bot->id}'");
        } else {
            $this->info("✅ No errors in last 24 hours");
        }

        // Check worker auto-restart
        $this->info("Checking worker auto-restart capability...");
        $this->info("   MonitorTradingBotWorkersJob should run every minute");
        $this->info("   It will restart dead workers automatically");

        $this->newLine();
    }
}
