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
     */
    public function handle(TradingBotWorkerService $workerService)
    {
        // Get all running bots
        $runningBots = TradingBot::running()->get();

        $restarted = 0;
        $checked = 0;

        foreach ($runningBots as $bot) {
            $checked++;
            
            // Check if worker is still running
            if (!$workerService->isWorkerRunning($bot)) {
                // Worker is dead but bot status is running - restart it
                try {
                    // Check bot configuration before restarting
                    if (!$bot->is_active) {
                        Log::info('Skipping restart for inactive bot', [
                            'bot_id' => $bot->id,
                            'name' => $bot->name,
                        ]);
                        continue;
                    }

                    $workerService->startWorker($bot);
                    $restarted++;
                    
                    Log::warning('Trading bot worker restarted', [
                        'bot_id' => $bot->id,
                        'name' => $bot->name,
                        'trading_mode' => $bot->trading_mode ?? 'unknown',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to restart trading bot worker', [
                        'bot_id' => $bot->id,
                        'bot_name' => $bot->name ?? 'Unknown',
                        'trading_mode' => $bot->trading_mode ?? 'unknown',
                        'worker_status' => $bot->worker_status ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    // Update bot status to indicate worker failure
                    try {
                        $bot->update([
                            'worker_status' => 'failed',
                            'worker_last_heartbeat' => now(),
                        ]);
                    } catch (\Exception $updateException) {
                        Log::error('Failed to update bot status after worker restart failure', [
                            'bot_id' => $bot->id,
                            'error' => $updateException->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Kill stale workers (bots that are stopped but process still running)
        $killed = $workerService->killStaleWorkers();

        if ($restarted > 0 || $killed > 0) {
            Log::info('Trading bot workers monitored', [
                'checked' => $checked,
                'restarted' => $restarted,
                'killed_stale' => $killed,
            ]);
        }
    }
}
