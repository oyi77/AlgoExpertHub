<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DexLeaderboardService
{
    public function refreshLeaderboards(): void
    {
        $metrics = ['total_pnl', 'win_rate', 'profit_factor'];
        $platforms = ['gmx', 'hyperliquid', 'aster', 'lighter', 'dydx_v4', null];

        foreach ($metrics as $metric) {
            foreach ($platforms as $platform) {
                $this->buildLeaderboard($metric, $platform);
            }
        }
    }

    public function buildLeaderboard(string $metricKey = 'total_pnl', ?string $platform = null, int $limit = 100): Collection
    {
        $latest = DB::table('dex_analytics_cache')
            ->select('watchlist_id', DB::raw('MAX(computed_at) as computed_at'))
            ->groupBy('watchlist_id');

        $metricsQuery = DB::table('dex_analytics_cache as cache')
            ->joinSub($latest, 'latest', function ($join): void {
                $join->on('cache.watchlist_id', '=', 'latest.watchlist_id')
                    ->on('cache.computed_at', '=', 'latest.computed_at');
            });

        if ($platform) {
            $metricsQuery->where('cache.platform', $platform);
        }

        $metrics = $metricsQuery->get([
            'cache.watchlist_id',
            'cache.wallet_address',
            'cache.platform',
            'cache.metric_key',
            'cache.metric_value',
        ]);

        return $this->shapeLeaderboard($metrics, $metricKey)->take($limit);
    }

    protected function shapeLeaderboard(Collection $metrics, string $metricKey): Collection
    {
        $grouped = $metrics->groupBy('watchlist_id')->map(function (Collection $entries) {
            $metricsMap = $entries->mapWithKeys(function ($entry) {
                return [$entry->metric_key => json_decode($entry->metric_value, true)];
            });

            return [
                'watchlist_id' => $entries->first()->watchlist_id,
                'wallet_address' => $entries->first()->wallet_address,
                'platform' => $entries->first()->platform,
                'metrics' => $metricsMap,
                'confidence_score' => $this->computeConfidence($metricsMap->count()),
            ];
        });

        $sorted = $grouped->sortByDesc(function (array $entry) use ($metricKey) {
            return (float) ($entry['metrics'][$metricKey] ?? 0);
        })->values();

        return $sorted->map(function (array $entry, int $index): array {
            $entry['rank'] = $index + 1;
            $entry['score'] = $entry['metrics'];
            unset($entry['metrics']);
            return $entry;
        });
    }

    protected function computeConfidence(int $metricCount): float
    {
        $maxMetrics = 9;
        $ratio = $maxMetrics > 0 ? $metricCount / $maxMetrics : 0;

        return round(min(1, $ratio) * 100, 2);
    }
}
