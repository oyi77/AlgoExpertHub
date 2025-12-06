<?php

namespace Addons\SmartRiskManagement\App\Jobs;

use Addons\SmartRiskManagement\App\Services\MaxDrawdownControlService;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorDrawdownJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Don't retry - will run again next minute
    public $timeout = 120; // 2 minutes

    /**
     * Execute the job.
     */
    public function handle(MaxDrawdownControlService $drawdownService): void
    {
        try {
            if (!class_exists(ExecutionConnection::class)) {
                return;
            }
            
            $connections = ExecutionConnection::where('is_active', true)
                ->get();
            
            foreach ($connections as $connection) {
                try {
                    // Skip if already in emergency stop
                    $settings = $connection->settings ?? [];
                    if (isset($settings['srm_emergency_stop']) && $settings['srm_emergency_stop']) {
                        continue;
                    }
                    
                    $drawdown = $drawdownService->checkDrawdown($connection);
                    
                    if ($drawdown['exceeds_threshold']) {
                        $reason = "Drawdown ({$drawdown['drawdown_percent']}%) exceeded threshold ({$drawdown['threshold']}%)";
                        $drawdownService->triggerEmergencyStop($connection, $reason);
                    }
                } catch (\Exception $e) {
                    Log::error("MonitorDrawdownJob: Failed to check drawdown for connection", [
                        'connection_id' => $connection->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("MonitorDrawdownJob: Job failed", [
                'error' => $e->getMessage(),
            ]);
            // Don't throw - will run again next minute
        }
    }
}

