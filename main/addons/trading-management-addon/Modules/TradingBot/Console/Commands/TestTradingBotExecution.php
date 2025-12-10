<?php

namespace Addons\TradingManagement\Modules\TradingBot\Console\Commands;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Jobs\FilterAnalysisJob;
use Addons\TradingManagement\Modules\RiskManagement\Jobs\RiskManagementJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Test Trading Bot Execution Flow
 * 
 * Manually test the complete execution flow with mock data
 */
class TestTradingBotExecution extends Command
{
    protected $signature = 'trading-bot:test-execution {bot_id} {--force : Force trade decision even with low confidence}';
    protected $description = 'Test trading bot execution flow with mock market data';

    public function handle()
    {
        $botId = $this->argument('bot_id');
        $force = $this->option('force');
        
        $bot = TradingBot::findOrFail($botId);
        
        $this->info("Testing Trading Bot Execution: {$bot->name} (ID: {$bot->id})");
        $this->newLine();
        
        // Check prerequisites
        if (!$bot->exchange_connection_id) {
            $this->error("❌ Bot has no exchange connection");
            return 1;
        }
        
        if (!$bot->trading_preset_id) {
            $this->error("❌ Bot has no trading preset");
            return 1;
        }
        
        // Generate mock market data (100 candles for indicators)
        $this->info("Generating mock market data...");
        $symbols = $bot->getStreamingSymbols() ?: ['XAUUSDC'];
        $timeframes = $bot->getStreamingTimeframes() ?: ['5m'];
        
        $marketData = [];
        $basePrice = 2650.0; // Example gold price
        
        foreach ($symbols as $symbol) {
            foreach ($timeframes as $timeframe) {
                // Generate 100 candles with upward trend (to trigger buy signal)
                for ($i = 99; $i >= 0; $i--) {
                    $timestamp = (now()->timestamp - ($i * 300)) * 1000; // 5min intervals
                    $price = $basePrice + ($i * 0.5); // Upward trend
                    $volatility = rand(-10, 10) / 10;
                    
                    $marketData[] = [
                        'timestamp' => $timestamp,
                        'open' => $price + $volatility,
                        'high' => $price + abs($volatility) + 1,
                        'low' => $price - abs($volatility) - 1,
                        'close' => $price,
                        'volume' => rand(100, 1000),
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                    ];
                }
            }
        }
        
        $this->info("✅ Generated " . count($marketData) . " candles");
        
            // Test complete flow by directly calling each step
            $this->info("Testing complete execution flow...");
            
            // Step 1: Test FilterAnalysisJob
            $this->info("Step 1: Testing FilterAnalysisJob...");
            $decision = [
                'should_enter' => true,
                'direction' => 'buy',
                'confidence' => $force ? 1.0 : 0.8,
                'reason' => 'Test execution flow',
                'entry_price' => $basePrice,
                'stop_loss' => $basePrice * 0.98, // 2% SL
                'take_profit' => $basePrice * 1.03, // 3% TP
            ];
            
            $this->info("Decision: " . json_encode($decision, JSON_PRETTY_PRINT));
            
            try {
                $filterJob = new FilterAnalysisJob($bot, $decision, $marketData);
                $filterJob->handle();
                $this->info("✅ FilterAnalysisJob processed successfully");
            
                // Step 2: Manually dispatch and process RiskManagementJob
                // (Since queue might be sync, we'll process it directly)
                $this->info("Step 2: Processing RiskManagementJob directly...");
                try {
                    $riskJob = new RiskManagementJob($bot, $decision, $marketData);
                    $riskJob->handle();
                    $this->info("✅ RiskManagementJob processed successfully");
                } catch (\Exception $e) {
                    $this->error("❌ RiskManagementJob failed: " . $e->getMessage());
                    $this->error("   " . substr($e->getTraceAsString(), 0, 500));
                    $this->info("");
                    $this->info("Common issues:");
                    $this->info("  - Bot missing exchange connection");
                    $this->info("  - Bot missing trading preset");
                    $this->info("  - Account info fetch failed");
                    return 1;
                }
                
                // Step 3: Check if ExecutionJob was dispatched
                $this->info("Step 3: Checking for ExecutionJob...");
                sleep(1); // Give queue time to process
                
                // Check logs to see if ExecutionJob was dispatched
                $logPath = storage_path('logs/laravel.log');
                $recentLogs = file_exists($logPath) 
                    ? shell_exec("tail -n 20 {$logPath} | grep -E 'dispatched to execution|ExecutionJob' 2>/dev/null")
                    : '';
                
                if (strpos($recentLogs, 'dispatched to execution') !== false) {
                    $this->info("✅ ExecutionJob was dispatched (check logs above)");
                    $this->info("");
                    $this->info("Execution flow completed successfully!");
                    $this->info("");
                    $this->info("Summary:");
                    $this->info("  ✅ FilterAnalysisJob: Processed");
                    $this->info("  ✅ RiskManagementJob: Processed & dispatched ExecutionJob");
                    $this->info("  ✅ ExecutionJob: Dispatched (will execute when queue worker processes it)");
                    $this->info("");
                    $this->info("To execute the trade:");
                    $this->info("  Run: docker exec -i 1Panel-php8-mrTy php /www/sites/aitradepulse.com/index/main/artisan queue:work --once");
                } else {
                    // Check queue directly
                    $execJobFound = null;
                    foreach (\DB::table('jobs')->whereNull('reserved_at')->get() as $job) {
                        $payload = json_decode($job->payload, true);
                        if (strpos($payload['displayName'] ?? '', 'ExecutionJob') !== false) {
                            $execJobFound = $job;
                            break;
                        }
                    }
                    
                    if ($execJobFound) {
                        $this->info("✅ ExecutionJob found in queue");
                        $this->info("   Trade execution will proceed when queue worker processes it");
                    } else {
                        $this->warn("⚠️  No ExecutionJob found");
                        $this->info("   Check logs: tail -f storage/logs/laravel.log | grep -E 'RiskManagementJob|ExecutionJob'");
                        $this->info("   RiskManagementJob may have rejected the trade or failed to dispatch");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Test failed: " . $e->getMessage());
                $this->error("   " . $e->getTraceAsString());
                return 1;
            }
        
        $this->newLine();
        $this->info("✅ Test completed!");
        $this->info("");
        $this->info("Next steps:");
        $this->info("1. Check logs: tail -f storage/logs/laravel.log | grep -E 'FilterAnalysisJob|RiskManagementJob|ExecutionJob'");
        $this->info("2. Process queue: php artisan queue:work");
        $this->info("3. Check positions: Check trading_bot_positions table");
        $this->info("");
        $this->info("If ExecutionJob was created, the trade should execute when queue worker processes it.");
        
        return 0;
    }
}
