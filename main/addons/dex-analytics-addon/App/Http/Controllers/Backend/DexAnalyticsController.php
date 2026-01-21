<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DexAnalyticsController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_traders' => DB::table('dex_trader_watchlist')->count(),
            'active_positions' => DB::table('dex_position_snapshots')->count(),
            'total_pnl' => (float) DB::table('dex_pnl_records')->sum('realized_pnl'),
            'liquidations' => DB::table('dex_liquidation_events')->count(),
        ];

        $recentActivity = DB::table('dex_pnl_records')
            ->orderByDesc('closed_at')
            ->limit(20)
            ->get();

        $platformHealth = collect(config('dex-analytics.platforms', []))
            ->map(fn ($config, $platform) => [
                'platform' => $platform,
                'enabled' => (bool) ($config['enabled'] ?? false),
                'rate_limit' => $config['rate_limit_per_minute'] ?? null,
            ])->values();

        return view('dex-analytics-addon::backend.dashboard', compact('stats', 'recentActivity', 'platformHealth'));
    }
}
