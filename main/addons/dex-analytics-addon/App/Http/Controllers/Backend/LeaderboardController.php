<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\Backend;

use Addons\DexAnalyticsAddon\App\Services\DexLeaderboardService;
use Addons\DexAnalyticsAddon\App\Services\DexThemeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly DexLeaderboardService $leaderboardService,
        private readonly DexThemeService $themeService
    ) {
    }

    public function index(Request $request)
    {
        $metricKey = $request->query('metric', 'total_pnl');
        $platform = $request->query('platform');

        $leaderboard = $this->leaderboardService->buildLeaderboard($metricKey, $platform);

        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/Leaderboards', [
                'leaderboard' => $leaderboard,
                'metricKey' => $metricKey,
                'platform' => $platform,
            ]);
        }

        return view('dex-analytics-addon::backend.leaderboards.index', compact('leaderboard', 'metricKey', 'platform'));
    }

    public function topTraders(Request $request)
    {
        $leaderboard = $this->leaderboardService->buildLeaderboard('total_pnl', $request->query('platform'));

        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/Leaderboards', [
                'leaderboard' => $leaderboard,
                'metricKey' => 'total_pnl',
                'platform' => $request->query('platform'),
            ]);
        }

        return view('dex-analytics-addon::backend.leaderboards.top-traders', compact('leaderboard'));
    }

    public function smartMoney(Request $request)
    {
        $leaderboard = $this->leaderboardService->buildLeaderboard('profit_factor', $request->query('platform'));

        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/Leaderboards', [
                'leaderboard' => $leaderboard,
                'metricKey' => 'profit_factor',
                'platform' => $request->query('platform'),
            ]);
        }

        return view('dex-analytics-addon::backend.leaderboards.smart-money', compact('leaderboard'));
    }

    public function copySuitable(Request $request)
    {
        $leaderboard = $this->leaderboardService->buildLeaderboard('win_rate', $request->query('platform'));

        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/Leaderboards', [
                'leaderboard' => $leaderboard,
                'metricKey' => 'win_rate',
                'platform' => $request->query('platform'),
            ]);
        }

        return view('dex-analytics-addon::backend.leaderboards.copy-suitable', compact('leaderboard'));
    }
}
