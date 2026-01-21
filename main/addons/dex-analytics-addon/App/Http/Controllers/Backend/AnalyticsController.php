<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\Backend;

use Addons\DexAnalyticsAddon\App\Services\DexAnalyticsComputationService;
use Addons\DexAnalyticsAddon\App\Services\DexVisualizationService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly DexAnalyticsComputationService $computationService,
        private readonly DexVisualizationService $visualizationService
    ) {
    }

    public function index()
    {
        return view('dex-analytics-addon::backend.analytics.index');
    }

    public function trader(string $wallet)
    {
        $watchlist = DB::table('dex_trader_watchlist')->where('wallet_address', $wallet)->first();
        $metrics = $watchlist ? $this->computationService->computeMetricsForWatchlist((int) $watchlist->id) : [];
        $heatmap = $watchlist ? $this->visualizationService->buildPnlHeatmap((int) $watchlist->id) : [];

        return view('dex-analytics-addon::backend.analytics.trader', compact('watchlist', 'metrics', 'heatmap'));
    }

    public function performance()
    {
        return view('dex-analytics-addon::backend.analytics.performance');
    }

    public function pnl()
    {
        return view('dex-analytics-addon::backend.analytics.pnl');
    }

    public function positions()
    {
        return view('dex-analytics-addon::backend.analytics.positions');
    }

    public function funding()
    {
        return view('dex-analytics-addon::backend.analytics.funding');
    }

    public function liquidations()
    {
        return view('dex-analytics-addon::backend.analytics.liquidations');
    }
}
