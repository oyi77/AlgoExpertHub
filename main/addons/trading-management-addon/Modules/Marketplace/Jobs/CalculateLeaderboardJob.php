<?php

namespace Addons\TradingManagement\Modules\Marketplace\Jobs;

use Addons\TradingManagement\Modules\Marketplace\Services\LeaderboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateLeaderboardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function handle(LeaderboardService $leaderboardService)
    {
        try {
            Log::info('CalculateLeaderboardJob: Starting');

            $results = $leaderboardService->updateAllTimeframes();

            Log::info('CalculateLeaderboardJob: Completed', $results);

        } catch (\Exception $e) {
            Log::error('CalculateLeaderboardJob: Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}


