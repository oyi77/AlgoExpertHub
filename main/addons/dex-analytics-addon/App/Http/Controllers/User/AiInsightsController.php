<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\User;

use Addons\DexAnalyticsAddon\App\Services\DexAiIntelligenceService;
use Addons\DexAnalyticsAddon\App\Services\DexAnalyticsComputationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiInsightsController extends Controller
{
    public function __construct(
        private readonly DexAiIntelligenceService $aiService,
        private readonly DexAnalyticsComputationService $computationService
    ) {
    }

    public function index()
    {
        return view('dex-analytics-addon::user.ai-insights.index');
    }

    public function analyze(Request $request)
    {
        $watchlistId = (int) $request->input('watchlist_id');
        $watchlist = DB::table('dex_trader_watchlist')
            ->where('id', $watchlistId)
            ->where('assigned_user_id', auth()->id())
            ->first();

        if (!$watchlist) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $metrics = $this->computationService->computeMetricsForWatchlist((int) $watchlist->id);
        $result = $this->aiService->generateInsightsForTrader((int) $watchlist->id, $metrics, $request->input('connection_id'));

        return response()->json($result);
    }
}
