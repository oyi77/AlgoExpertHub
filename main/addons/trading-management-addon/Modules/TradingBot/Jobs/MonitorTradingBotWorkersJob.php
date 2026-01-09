<?php

namespace Addons\TradingManagement\Modules\TradingBot\Jobs;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * MonitorTradingBotWorkersJob
 * 
 * Scheduled job to monitor and restart dead trading bot workers
 * Runs every minute
 */
class MonitorTradingBotWorkersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 120;

    /**
     * Execute the job
     * 
     * Enhanced monitoring with:
     * - Auto-recovery with rate limiting
     * - Better error handling
     * - Comprehensive statistics
     */
    public function handle(TradingBotWorkerService $workerService)
    {
        $startTime = microtime(true);

        // Use enhanced monitoring and auto-recovery
        $monitorResult = $workerService->monitorAndRecover(
            maxRestartsPerHour: (int) env('TRADING_BOT_MAX_RESTARTS_PER_HOUR', 5)
        );

        // Kill stale workers (bots that are stopped but process still running)
        $killed = $workerService->killStaleWorkers();

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        // Log summary
        if ($monitorResult['restarted'] > 0 || $monitorResult['failed'] > 0 || $killed > 0) {
            Log::info('Trading bot workers monitored', [
                'checked' => $monitorResult['checked'],
                'restarted' => $monitorResult['restarted'],
                'failed' => $monitorResult['failed'],
                'skipped' => $monitorResult['skipped'],
                'killed_stale' => $killed,
                'execution_time_ms' => $executionTime,
            ]);
        } else {
            // Log even when nothing happened (for monitoring)
            Log::debug('Trading bot workers monitored (no action needed)', [
                'checked' => $monitorResult['checked'],
                'execution_time_ms' => $executionTime,
            ]);
        }
    }
}
