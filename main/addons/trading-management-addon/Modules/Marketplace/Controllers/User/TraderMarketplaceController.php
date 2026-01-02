<?php

namespace Addons\TradingManagement\Modules\Marketplace\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Addons\TradingManagement\Modules\Marketplace\Services\LeaderboardService;
use Addons\TradingManagement\Modules\Marketplace\Models\{TraderProfile, TraderRating};
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
        $timeframe = $request->get('timeframe', 'all_time');
        
        $leaderboard = $this->leaderboardService->getLeaderboard($timeframe, 100);

        $topPerformers = TraderProfile::public()
            ->verified()
            ->topPerformers()
            ->limit(10)
            ->get();

        return view('trading-management::marketplace.user.traders.index', compact('leaderboard', 'topPerformers', 'timeframe'));
    }

    public function show($id)
    {
        $trader = TraderProfile::with(['user', 'ratings' => fn($q) => $q->recent()->limit(10), 'leaderboardEntries'])
            ->findOrFail($id);

        if (!$trader->is_public) {
            abort(404);
        }

        $userRating = TraderRating::where('trader_id', $id)
            ->where('follower_id', auth()->id())
            ->first();

        $isFollowing = false;
        $subscriptionClass = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class;
        if (class_exists($subscriptionClass)) {
            $isFollowing = $subscriptionClass::where('trader_id', $trader->user_id)
                ->where('follower_id', auth()->id())
                ->where('is_active', true)
                ->exists();
        }

        return view('trading-management::marketplace.user.traders.show', compact('trader', 'userRating', 'isFollowing'));
    }

    public function follow($id, Request $request)
    {
        $trader = TraderProfile::findOrFail($id);

        if (!$trader->canAcceptFollower()) {
            return redirect()->back()->with('notify', NotificationHelper::error('Trader is not accepting followers', 'Error'));
        }

        // Create copy trading subscription
        $subscriptionClass = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class;
        if (class_exists($subscriptionClass)) {
            $subscriptionClass::create([
                'trader_id' => $trader->user_id,
                'follower_id' => auth()->id(),
                'copy_mode' => 'easy',
                'risk_multiplier' => $request->get('risk_multiplier', 1.0),
                'is_active' => true,
                'subscribed_at' => now(),
            ]);

            $trader->increment('total_followers');

            return redirect()->back()->with('notify', NotificationHelper::success('You are now following this trader', 'Success'));
        }

        return redirect()->back()->with('notify', NotificationHelper::error('Copy trading addon not available', 'Error'));
    }

    public function unfollow($id)
    {
        $trader = TraderProfile::findOrFail($id);

        $subscriptionClass = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class;
        if (class_exists($subscriptionClass)) {
            $subscriptionClass::where('trader_id', $trader->user_id)
                ->where('follower_id', auth()->id())
                ->where('is_active', true)
                ->update(['is_active' => false, 'unsubscribed_at' => now()]);

            $trader->decrement('total_followers');

            return redirect()->back()->with('notify', NotificationHelper::success('You have unfollowed this trader', 'Success'));
        }

        return redirect()->back()->with('notify', NotificationHelper::error('Copy trading addon not available', 'Error'));
    }

    public function rate($id, Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $trader = TraderProfile::findOrFail($id);

        TraderRating::updateOrCreate(
            [
                'trader_id' => $id,
                'follower_id' => auth()->id(),
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review,
                'verified_follower' => $this->isVerifiedFollower($trader),
            ]
        );

        return redirect()->back()->with('notify', NotificationHelper::success('Rating submitted successfully', 'Success'));
    }

    protected function isVerifiedFollower($trader): bool
    {
        $subscriptionClass = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class;
        if (!class_exists($subscriptionClass)) {
            return false;
        }

        return $subscriptionClass::where('trader_id', $trader->user_id)
            ->where('follower_id', auth()->id())
            ->exists();
    }
}

