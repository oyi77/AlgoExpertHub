<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\User;

use Addons\DexAnalyticsAddon\App\Services\DexLeaderboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(private readonly DexLeaderboardService $leaderboardService)
    {
    }

    public function index(Request $request)
    {
        $metricKey = $request->query('metric', 'total_pnl');
        $platform = $request->query('platform');
        $leaderboard = $this->leaderboardService->buildLeaderboard($metricKey, $platform);

        return view('dex-analytics-addon::user.leaderboards.index', compact('leaderboard', 'metricKey', 'platform'));
    }
}
