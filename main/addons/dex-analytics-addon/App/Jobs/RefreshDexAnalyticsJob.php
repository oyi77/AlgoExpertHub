<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Jobs;

use Addons\DexAnalyticsAddon\App\Services\DexAnalyticsComputationService;
use Addons\DexAnalyticsAddon\App\Services\DexLeaderboardService;
use Addons\DexAnalyticsAddon\App\Services\DexAiIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshDexAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct()
    {
    }

    public function handle(
        DexAnalyticsComputationService $computationService,
        DexLeaderboardService $leaderboardService,
        DexAiIntelligenceService $aiService
    ): void {
        try {
            Log::info('DEX Analytics: Starting analytics refresh');

            $computationService->computeAllMetrics();

            $leaderboardService->refreshLeaderboards();

            $aiService->generateInsights();

            Log::info('DEX Analytics: Analytics refresh completed');

        } catch (\Exception $e) {
            Log::error('DEX Analytics: Analytics refresh job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DEX Analytics: Analytics refresh job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
