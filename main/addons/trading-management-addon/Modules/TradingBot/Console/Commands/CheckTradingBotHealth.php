<?php

namespace Addons\TradingManagement\Modules\TradingBot\Console\Commands;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CheckTradingBotHealth Command
 * 
 * Comprehensive health check for all trading bots and workers
 */
class CheckTradingBotHealth extends Command
{
    protected $signature = 'trading-bot:health-check {--bot-id= : Check specific bot ID} {--verbose : Show detailed information}';
    protected $description = 'Check health status of all trading bots and workers';

    protected TradingBotWorkerService $workerService;
    protected array $issues = [];
    protected array $warnings = [];
    protected int $healthyCount = 0;

    public function __construct(TradingBotWorkerService $workerService)
    {
        parent::__construct();
        $this->workerService = $workerService;
    }

    public function handle()
    {
        $botId = $this->option('bot-id');
        $verbose = $this->option('verbose');

        $this->info('=== Trading Bot Health Check ===');
        $this->newLine();

        // 1. Check infrastructure
        $this->checkInfrastructure();

        // 2. Check queue
        $this->checkQueue();

        // 3. Check scheduled jobs
        $this->checkScheduledJobs();

        // 4. Check bots
        if ($botId) {
            $bot = TradingBot::find($botId);
            if ($bot) {
                $this->checkBot($bot, $verbose);
            } else {
                $this->error("Bot ID {$botId} not found");
                return 1;
            }
        } else {
            $bots = TradingBot::all();
            foreach ($bots as $bot) {
                $this->checkBot($bot, $verbose);
            }
        }

        // 5. Summary
        $this->newLine();
        $this->displaySummary();

        return count($this->issues) > 0 ? 1 : 0;
    }

    /**
     * Check infrastructure (database, logs, etc.)
     */
    protected function checkInfrastructure(): void
    {
        $this->info('1. Infrastructure Check...');

        try {
            // Check database connection
            DB::connection()->getPdo();
            $this->line('   ✅ Database connection OK');
        } catch (\Exception $e) {
            $this->issues[] = 'Database connection failed: ' . $e->getMessage();
            $this->error('   ❌ Database connection failed');
        }

        // Check log directory
        $logPath = storage_path('logs');
        if (is_writable($logPath)) {
            $this->line('   ✅ Log directory writable');
        } else {
            $this->issues[] = 'Log directory not writable';
            $this->error('   ❌ Log directory not writable');
        }

        $this->newLine();
    }

    /**
     * Check queue status
     */
    protected function checkQueue(): void
    {
        $this->info('2. Queue Check...');

        try {
            $connection = config('queue.default');
            $this->line("   Queue connection: {$connection}");

            if ($connection === 'database') {
                // Check jobs table
                $pendingJobs = DB::table('jobs')->whereNull('reserved_at')->count();
                $reservedJobs = DB::table('jobs')->whereNotNull('reserved_at')->count();
                $failedJobs = DB::table('failed_jobs')->count();

                $this->line("   ✅ Pending jobs: {$pendingJobs}");
                $this->line("   ✅ Reserved jobs: {$reservedJobs}");

                if ($failedJobs > 0) {
                    $this->warnings[] = "Failed jobs in queue: {$failedJobs}";
                    $this->warn("   ⚠️  Failed jobs: {$failedJobs}");
                } else {
                    $this->line('   ✅ No failed jobs');
                }

                // Check for ExecutionJob in queue
                $executionJobs = $this->countJobsInQueue('ExecutionJob');
                if ($executionJobs > 0) {
                    $this->line("   ✅ ExecutionJob(s) in queue: {$executionJobs}");
                }
            } else {
                $this->warn("   ⚠️  Queue connection is not 'database' (current: {$connection})");
            }
        } catch (\Exception $e) {
            $this->issues[] = 'Queue check failed: ' . $e->getMessage();
            $this->error('   ❌ Queue check failed');
        }

        $this->newLine();
    }

    /**
     * Check scheduled jobs
     */
    protected function checkScheduledJobs(): void
    {
        $this->info('3. Scheduled Jobs Check...');

        try {
            // Check if MonitorPositionsJob is scheduled
            $scheduleList = \Artisan::call('schedule:list');
            $output = \Artisan::output();
            
            $hasMonitorPositions = strpos($output, 'MonitorPositionsJob') !== false;
            $hasMonitorWorkers = strpos($output, 'MonitorTradingBotWorkersJob') !== false;

            if ($hasMonitorPositions) {
                $this->line('   ✅ MonitorPositionsJob scheduled');
            } else {
                $this->issues[] = 'MonitorPositionsJob not found in schedule';
                $this->error('   ❌ MonitorPositionsJob not scheduled');
            }

            if ($hasMonitorWorkers) {
                $this->line('   ✅ MonitorTradingBotWorkersJob scheduled');
            } else {
                $this->issues[] = 'MonitorTradingBotWorkersJob not found in schedule';
                $this->error('   ❌ MonitorTradingBotWorkersJob not scheduled');
            }
        } catch (\Exception $e) {
            $this->warnings[] = 'Could not check scheduled jobs: ' . $e->getMessage();
            $this->warn('   ⚠️  Could not verify scheduled jobs');
        }

        $this->newLine();
    }

    /**
     * Check individual bot health
     */
    protected function checkBot(TradingBot $bot, bool $verbose): void
    {
        $this->info("Bot: {$bot->name} (ID: {$bot->id})");

        $issues = [];
        $warnings = [];

        // Check bot status
        if ($verbose) {
            $this->line("   Status: {$bot->status}");
            $this->line("   Active: " . ($bot->is_active ? 'Yes' : 'No'));
            $this->line("   Trading Mode: {$bot->trading_mode}");
        }

        // Check prerequisites
        if (!$bot->exchange_connection_id) {
            $issues[] = 'No exchange connection configured';
            $this->error('   ❌ No exchange connection');
        } else {
            $connection = $bot->exchangeConnection;
            if (!$connection) {
                $issues[] = 'Exchange connection not found';
                $this->error('   ❌ Exchange connection not found');
            } elseif (!$connection->is_active) {
                $warnings[] = 'Exchange connection is inactive';
                $this->warn('   ⚠️  Exchange connection inactive');
            } else {
                if ($verbose) {
                    $this->line('   ✅ Exchange connection active: ' . $connection->name);
                }
            }
        }

        if (!$bot->trading_preset_id) {
            $warnings[] = 'No trading preset configured';
            $this->warn('   ⚠️  No trading preset');
        } else {
            if ($verbose) {
                $preset = $bot->tradingPreset;
                $this->line('   ✅ Trading preset: ' . ($preset ? $preset->name : 'N/A'));
            }
        }

        // Check worker status
        if ($bot->status === 'running') {
            $workerStatus = $this->workerService->getWorkerStatus($bot);
            
            if ($workerStatus === 'running') {
                if ($verbose) {
                    $pid = $bot->worker_pid ?? 'N/A';
                    $startedAt = $bot->worker_started_at ? $bot->worker_started_at->format('Y-m-d H:i:s') : 'N/A';
                    $this->line("   ✅ Worker running (PID: {$pid}, Started: {$startedAt})");
                    
                    // Verify process actually exists
                    if ($bot->worker_pid && $this->workerService->isWorkerRunning($bot)) {
                        $this->line('   ✅ Worker process verified');
                    } else {
                        $issues[] = 'Worker PID exists but process not found';
                        $this->error('   ❌ Worker process not found');
                    }
                } else {
                    $this->line('   ✅ Worker running');
                }
                $this->healthyCount++;
            } elseif ($workerStatus === 'dead') {
                $issues[] = 'Bot status is running but worker is dead';
                $this->error('   ❌ Worker is dead (should be restarted by MonitorTradingBotWorkersJob)');
            } elseif ($workerStatus === 'paused') {
                $this->line('   ⏸️  Worker paused');
            } else {
                $this->line("   ℹ️  Worker status: {$workerStatus}");
            }
        } elseif ($bot->status === 'stopped') {
            if ($bot->worker_pid) {
                // Check if stale worker exists
                if ($this->workerService->isWorkerRunning($bot)) {
                    $warnings[] = 'Stale worker process still running';
                    $this->warn('   ⚠️  Stale worker process exists (should be killed)');
                } else {
                    if ($verbose) {
                        $this->line('   ✅ No worker process (expected for stopped bot)');
                    }
                }
            }
        }

        // Check recent positions
        $openPositions = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition::where('bot_id', $bot->id)
            ->where('status', 'open')
            ->count();

        if ($openPositions > 0) {
            if ($verbose) {
                $this->line("   ✅ Open positions: {$openPositions}");
                
                // Check if positions have recent price updates
                $recentUpdates = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition::where('bot_id', $bot->id)
                    ->where('status', 'open')
                    ->where('updated_at', '>=', now()->subMinutes(5))
                    ->count();

                if ($recentUpdates === 0 && $openPositions > 0) {
                    $warnings[] = 'Open positions but no recent price updates (MonitorPositionsJob may not be working)';
                    $this->warn('   ⚠️  No recent position updates');
                }
            } else {
                $this->line("   ✅ Open positions: {$openPositions}");
            }
        } else {
            if ($verbose) {
                $this->line('   ℹ️  No open positions');
            }
        }

        // Check recent execution logs
        if ($verbose) {
            $recentExecutions = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::where('connection_id', $bot->exchange_connection_id)
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
            
            if ($recentExecutions > 0) {
                $this->line("   ✅ Recent executions (24h): {$recentExecutions}");
            }
        }

        // Check log file
        $logPath = storage_path("logs/trading-bot-{$bot->id}.log");
        if (file_exists($logPath)) {
            if ($verbose) {
                $logSize = filesize($logPath);
                $logSizeHuman = $this->formatBytes($logSize);
                $this->line("   ✅ Log file exists ({$logSizeHuman})");
            }
        } else {
            if ($verbose && $bot->status === 'running') {
                $warnings[] = 'Log file does not exist for running bot';
                $this->warn('   ⚠️  Log file not found');
            }
        }

        // Store issues and warnings
        foreach ($issues as $issue) {
            $this->issues[] = "Bot {$bot->id} ({$bot->name}): {$issue}";
        }
        foreach ($warnings as $warning) {
            $this->warnings[] = "Bot {$bot->id} ({$bot->name}): {$warning}";
        }

        $this->newLine();
    }

    /**
     * Display summary
     */
    protected function displaySummary(): void
    {
        $this->info('=== Summary ===');
        $this->line("Healthy bots: {$this->healthyCount}");
        $this->line("Issues found: " . count($this->issues));
        $this->line("Warnings: " . count($this->warnings));

        if (count($this->issues) > 0) {
            $this->newLine();
            $this->error('Issues:');
            foreach ($this->issues as $issue) {
                $this->error("  - {$issue}");
            }
        }

        if (count($this->warnings) > 0) {
            $this->newLine();
            $this->warn('Warnings:');
            foreach ($this->warnings as $warning) {
                $this->warn("  - {$warning}");
            }
        }

        if (count($this->issues) === 0 && count($this->warnings) === 0) {
            $this->newLine();
            $this->info('✅ All checks passed!');
        }
    }

    /**
     * Count jobs in queue by class name
     */
    protected function countJobsInQueue(string $className): int
    {
        try {
            $jobs = DB::table('jobs')
                ->whereNull('reserved_at')
                ->get();

            $count = 0;
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                if (strpos($payload['displayName'] ?? '', $className) !== false) {
                    $count++;
                }
            }
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
