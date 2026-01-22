<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\Backend;

use Addons\DexAnalyticsAddon\App\Services\DexAiIntelligenceService;
use Addons\DexAnalyticsAddon\App\Services\DexAnalyticsComputationService;
use Addons\DexAnalyticsAddon\App\Services\DexThemeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiInsightsController extends Controller
{
    public function __construct(
        private readonly DexAiIntelligenceService $aiService,
        private readonly DexAnalyticsComputationService $computationService,
        private readonly DexThemeService $themeService
    ) {
    }

    public function index()
    {
        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/AiInsights', [
                'clusters' => [],
                'crowdedTrades' => [],
            ]);
        }

        return view('dex-analytics-addon::backend.ai-insights.index');
    }

    public function analyze(Request $request)
    {
        $watchlistId = (int) $request->input('watchlist_id');
        $metrics = $this->computationService->computeMetricsForWatchlist($watchlistId);

        $result = $this->aiService->generateInsightsForTrader($watchlistId, $metrics, $request->input('connection_id'));

        return response()->json($result);
    }

    public function clustering()
    {
        $traders = DB::table('dex_trader_watchlist')->get(['id', 'wallet_address', 'platform']);
        $clusters = $this->aiService->clusterBehaviors($traders->toArray());

        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/AiInsights', [
                'clusters' => $clusters,
                'crowdedTrades' => [],
            ]);
        }

        return view('dex-analytics-addon::backend.ai-insights.clustering', compact('clusters'));
    }

    public function crowdedTrades()
    {
        $positions = DB::table('dex_position_snapshots')
            ->selectRaw('symbol, COUNT(DISTINCT wallet_address) as traders, SUM(size) as total_size')
            ->groupBy('symbol')
            ->orderByDesc('traders')
            ->get();

        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/AiInsights', [
                'clusters' => [],
                'crowdedTrades' => $positions->toArray(),
            ]);
        }

        return view('dex-analytics-addon::backend.ai-insights.crowded-trades', compact('positions'));
    }

    public function regime()
    {
        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/AiInsights', [
                'clusters' => [],
                'crowdedTrades' => [],
            ]);
        }

        return view('dex-analytics-addon::backend.ai-insights.regime');
    }
}
