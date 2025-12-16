<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Jobs;

use Addons\TradingManagement\Modules\PositionMonitoring\Services\AnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * UpdateAnalyticsJob
 * 
 * Scheduled job that runs daily to calculate analytics for all active connections.
 * Processes yesterday's closed positions and stores aggregated metrics.
 */
class UpdateAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 minutes

    /**
     * The date to calculate analytics for (defaults to yesterday)
     */
    protected $date;

    /**
     * Create a new job instance.
     * 
     * @param Carbon|null $date Date to calculate analytics for (defaults to yesterday)
     */
    public function __construct(Carbon $date = null)
    {
        $this->date = $date ?? Carbon::yesterday();
    }

    /**
     * Execute the job.
     * 
     * @param AnalyticsService $analyticsService
     * @return void
     */
    public function handle(AnalyticsService $analyticsService): void
    {
        Log::info('UpdateAnalyticsJob: Starting analytics calculation', [
            'date' => $this->date->toDateString(),
        ]);

        try {
            // Update analytics for all active connections
            $analyticsService->updateAllAnalytics($this->date);

            Log::info('UpdateAnalyticsJob: Analytics calculation completed', [
                'date' => $this->date->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::error('UpdateAnalyticsJob: Failed to calculate analytics', [
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     * 
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateAnalyticsJob: Job failed after all retries', [
            'date' => $this->date->toDateString(),
            'error' => $exception->getMessage(),
        ]);
    }
}

