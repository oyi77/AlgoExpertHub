<?php

namespace Addons\TradingManagement\Modules\MarketData\Jobs;

use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Clean Old Market Data Job
 * 
 * Deletes market data older than retention period
 * Runs daily to keep database size manageable
 */
class CleanOldMarketDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $retentionDays;

    public $tries = 1; // Cleanup doesn't need retry
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance
     */
    public function __construct(?int $retentionDays = null)
    {
        $this->retentionDays = $retentionDays ?? config('trading-management.data_provider.retention_days', 365);
    }

    /**
     * Execute the job
     */
    public function handle(MarketDataService $marketDataService)
    {
        \Log::info('Starting market data cleanup', [
            'retention_days' => $this->retentionDays,
        ]);

        try {
            $deleted = $marketDataService->cleanup($this->retentionDays);

            \Log::info('Market data cleanup completed', [
                'retention_days' => $this->retentionDays,
                'rows_deleted' => $deleted,
            ]);

        } catch (\Exception $e) {
            \Log::error('Market data cleanup failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('CleanOldMarketDataJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}

