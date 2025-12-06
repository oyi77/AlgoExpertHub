<?php

namespace Addons\TradingExecutionEngine\App\Jobs;

use Addons\TradingExecutionEngine\App\Services\AnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(AnalyticsService $analyticsService): void
    {
        try {
            $analyticsService->updateAllAnalytics();
        } catch (\Exception $e) {
            Log::error("Analytics update job error", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

