<?php

namespace Addons\TradingManagement\Modules\Marketplace\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\Marketplace\Services\LeaderboardService;
use Addons\TradingManagement\Modules\Marketplace\Models\TraderProfile;
use Illuminate\Http\Request;

class TraderMarketplaceController extends Controller
{
    protected $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    public function index(Request $request)
    {
        $traders = TraderProfile::with('user')
            ->when($request->verified, fn($q) => $q->verified())
            ->when($request->search, function($q, $search) {
                $q->where('display_name', 'like', "%{$search}%");
            })
            ->orderBy('total_profit_percent', 'desc')
            ->paginate(20);

        return view('trading-management::marketplace.backend.traders.index', compact('traders'));
    }

    public function show($id)
    {
        $trader = TraderProfile::with(['user', 'ratings', 'leaderboardEntries'])->findOrFail($id);

        return view('trading-management::marketplace.backend.traders.show', compact('trader'));
    }

    public function verify($id)
    {
        $trader = TraderProfile::findOrFail($id);
        $trader->update(['verified' => !$trader->verified]);

        return redirect()->back()->with('success', 'Trader verification status updated');
    }

    public function destroy($id)
    {
        $trader = TraderProfile::findOrFail($id);
        $trader->delete();

        return redirect()->route('admin.marketplace.traders.index')->with('success', 'Trader profile deleted');
    }

    public function recalculateLeaderboard()
    {
        $results = $this->leaderboardService->updateAllTimeframes();

        return redirect()->back()->with('success', 'Leaderboard recalculated: ' . json_encode($results));
    }
}

