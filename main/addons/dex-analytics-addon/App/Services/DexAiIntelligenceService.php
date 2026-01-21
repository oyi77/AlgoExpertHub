<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DexAiIntelligenceService
{
    public function __construct(private readonly AiConnectionService $aiConnectionService)
    {
    }

    public function generateInsights(): void
    {
        $watchlists = DB::table('dex_trader_watchlist')
            ->where('is_active', true)
            ->get(['id']);

        foreach ($watchlists as $watchlist) {
            $metrics = DB::table('dex_analytics_cache')
                ->where('watchlist_id', $watchlist->id)
                ->latest('computed_at')
                ->first();

            if ($metrics) {
                $this->generateInsightsForTrader(
                    (int) $watchlist->id,
                    json_decode($metrics->metric_value, true) ?? []
                );
            }
        }
    }

    public function generateInsightsForTrader(int $watchlistId, array $metrics, ?int $connectionId = null): array
    {
        $connection = $connectionId ?? (int) config('dex-analytics.ai.default_connection_id');
        if (!$connection) {
            return [
                'success' => false,
                'message' => 'No AI connection configured',
            ];
        }

        $prompt = $this->buildPrompt($watchlistId, $metrics);

        try {
            $result = $this->aiConnectionService->execute($connection, $prompt, [
                'model' => config('dex-analytics.ai.model'),
                'temperature' => 0.2,
            ]);

            return [
                'success' => true,
                'data' => $result,
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function clusterBehaviors(array $traders): array
    {
        $clusters = [];

        foreach ($traders as $trader) {
            $bucket = $this->determineClusterBucket(Arr::get($trader, 'metrics', []));
            $clusters[$bucket][] = $trader;
        }

        return $clusters;
    }

    protected function determineClusterBucket(array $metrics): string
    {
        $winRate = (float) Arr::get($metrics, 'win_rate', 0);
        $profitFactor = (float) Arr::get($metrics, 'profit_factor', 0);

        if ($profitFactor >= 2 && $winRate >= 60) {
            return 'consistent_winner';
        }

        if ($profitFactor <= 0.8) {
            return 'underperformer';
        }

        return 'mixed';
    }

    protected function buildPrompt(int $watchlistId, array $metrics): string
    {
        return sprintf(
            'Analyze trader %d metrics and summarize behavior. Metrics: %s',
            $watchlistId,
            json_encode($metrics)
        );
    }
}
