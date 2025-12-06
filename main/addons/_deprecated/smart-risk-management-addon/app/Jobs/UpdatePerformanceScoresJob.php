<?php

namespace Addons\SmartRiskManagement\App\Jobs;

use Addons\SmartRiskManagement\App\Services\PerformanceScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdatePerformanceScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes

    /**
     * Execute the job.
     */
    public function handle(PerformanceScoreService $performanceScoreService): void
    {
        try {
            Log::info("UpdatePerformanceScoresJob: Starting performance score update");
            
            // Get all unique signal providers from execution logs
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
                Log::warning("UpdatePerformanceScoresJob: Execution Engine not available");
                return;
            }
            
            $providers = \Addons\TradingExecutionEngine\App\Models\ExecutionLog::whereNotNull('signal_provider_id')
                ->whereNotNull('signal_provider_type')
                ->select('signal_provider_id', 'signal_provider_type')
                ->distinct()
                ->get();
            
            $updated = 0;
            foreach ($providers as $provider) {
                try {
                    $performanceScoreService->updatePerformanceScore(
                        $provider->signal_provider_id,
                        $provider->signal_provider_type
                    );
                    $updated++;
                } catch (\Exception $e) {
                    Log::error("UpdatePerformanceScoresJob: Failed to update score for provider", [
                        'signal_provider_id' => $provider->signal_provider_id,
                        'type' => $provider->signal_provider_type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            Log::info("UpdatePerformanceScoresJob: Updated {$updated} signal provider scores");
        } catch (\Exception $e) {
            Log::error("UpdatePerformanceScoresJob: Job failed", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("UpdatePerformanceScoresJob: Job failed permanently", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

