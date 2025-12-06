<?php

namespace Addons\CopyTrading\App\Http\Controllers\User;

use Addons\CopyTrading\App\Http\Controllers\Controller;
use Addons\CopyTrading\App\Models\CopyTradingSetting;
use Addons\CopyTrading\App\Models\CopyTradingSubscription;
use Addons\CopyTrading\App\Services\CopyTradingAnalyticsService;
use App\Helpers\Helper\Helper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TraderController extends Controller
{
    protected CopyTradingAnalyticsService $analyticsService;

    public function __construct(CopyTradingAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Browse all active traders.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = CopyTradingSetting::enabled()
            ->with(['user'])
            ->whereHas('user');

        // Filter by performance if requested
        if ($request->has('filter')) {
            // Add filtering logic here
        }

        $traders = $query->latest()->paginate(Helper::pagination());

        // Get stats for each trader
        foreach ($traders as $trader) {
            $trader->stats = $this->analyticsService->getTraderStats($trader->user_id);
            $trader->is_following = CopyTradingSubscription::where('trader_id', $trader->user_id)
                ->where('follower_id', $user->id)
                ->where('is_active', true)
                ->exists();
        }

        $data['title'] = 'Browse Traders';
        $data['traders'] = $traders;

        return view('copy-trading::user.traders.index', $data);
    }

    /**
     * Show trader profile.
     */
    public function show(int $traderId): View
    {
        $user = auth()->user();
        $trader = User::findOrFail($traderId);

        $setting = CopyTradingSetting::byUser($traderId)->enabled()->first();
        
        if (!$setting) {
            abort(404, 'Trader not found or copy trading not enabled');
        }

        $stats = $this->analyticsService->getTraderStats($traderId);
        $isFollowing = CopyTradingSubscription::where('trader_id', $traderId)
            ->where('follower_id', $user->id)
            ->where('is_active', true)
            ->exists();

        $data['title'] = 'Trader Profile';
        $data['trader'] = $trader;
        $data['setting'] = $setting;
        $data['stats'] = $stats;
        $data['is_following'] = $isFollowing;

        return view('copy-trading::user.traders.show', $data);
    }
}

