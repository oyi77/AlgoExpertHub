<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class WatchlistController extends Controller
{
    public function index()
    {
        $watchlist = DB::table('dex_trader_watchlist')
            ->where('assigned_user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('dex-analytics-addon::user.watchlist.index', compact('watchlist'));
    }
}
