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
                    $workerService->startWorker($bot);
                    $restarted++;
                    
                    Log::warning('Trading bot worker restarted', [
                        'bot_id' => $bot->id,
                        'name' => $bot->name,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to restart trading bot worker', [
                        'bot_id' => $bot->id,
                        'error' => $e->getMessage(),
                    ]);
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
