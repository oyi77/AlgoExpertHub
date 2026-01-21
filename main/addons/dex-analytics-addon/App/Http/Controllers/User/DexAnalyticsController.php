<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DexAnalyticsController extends Controller
{
    public function dashboard()
    {
        $userId = auth()->id();
        $watchlist = DB::table('dex_trader_watchlist')
            ->where('assigned_user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total_traders' => $watchlist->count(),
            'total_pnl' => (float) DB::table('dex_pnl_records')
                ->whereIn('watchlist_id', $watchlist->pluck('id'))
                ->sum('realized_pnl'),
        ];

        return view('dex-analytics-addon::user.dashboard', compact('watchlist', 'stats'));
    }
}
