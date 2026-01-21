<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\User;

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
        return view('dex-analytics-addon::user.analytics.index');
    }

    public function trader(string $wallet)
    {
        $watchlist = DB::table('dex_trader_watchlist')
            ->where('wallet_address', $wallet)
            ->where('assigned_user_id', auth()->id())
            ->first();

        if (!$watchlist) {
            return redirect()->route('user.dex-analytics.analytics.index');
        }

        $metrics = $this->computationService->computeMetricsForWatchlist((int) $watchlist->id);
        $heatmap = $this->visualizationService->buildPnlHeatmap((int) $watchlist->id);

        return view('dex-analytics-addon::user.analytics.trader', compact('watchlist', 'metrics', 'heatmap'));
    }
}
