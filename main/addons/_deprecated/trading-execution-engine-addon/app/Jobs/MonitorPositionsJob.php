<?php

namespace Addons\TradingExecutionEngine\App\Jobs;

use Addons\TradingExecutionEngine\App\Services\PositionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorPositionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(PositionService $positionService): void
    {
        try {
            $positionService->monitorPositions();
        } catch (\Exception $e) {
            Log::error("Position monitoring job error", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

