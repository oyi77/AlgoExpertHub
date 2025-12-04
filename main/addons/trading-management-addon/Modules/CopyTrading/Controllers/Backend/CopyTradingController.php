<?php

namespace Addons\TradingManagement\Modules\CopyTrading\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription;
use Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingExecution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CopyTradingController extends Controller
{
    public function index()
    {
        $title = 'Copy Trading';
        $stats = [
            'total_subscriptions' => CopyTradingSubscription::count(),
            'active_subscriptions' => CopyTradingSubscription::where('is_active', true)->count(),
            'total_traders' => CopyTradingSubscription::distinct('trader_id')->count('trader_id'),
            'total_followers' => CopyTradingSubscription::distinct('follower_id')->count('follower_id'),
            'total_executions' => CopyTradingExecution::count(),
            'executions_today' => CopyTradingExecution::whereDate('created_at', today())->count(),
        ];

        return view('trading-management::backend.trading-management.copy-trading.index', compact('title', 'stats'));
    }

    public function subscriptions(Request $request)
    {
        $title = 'Copy Trading Subscriptions';
        $query = CopyTradingSubscription::with(['trader', 'follower', 'executionConnection', 'preset']);

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('trader_id')) {
            $query->where('trader_id', $request->trader_id);
        }

        if ($request->filled('follower_id')) {
            $query->where('follower_id', $request->follower_id);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(20);
        $traders = User::whereIn('id', CopyTradingSubscription::distinct('trader_id')->pluck('trader_id'))->get();
        $followers = User::whereIn('id', CopyTradingSubscription::distinct('follower_id')->pluck('follower_id'))->get();

        return view('trading-management::backend.trading-management.copy-trading.subscriptions', compact('title', 'subscriptions', 'traders', 'followers'));
    }

    public function traders()
    {
        $title = 'Traders';
        $traders = CopyTradingSubscription::select('trader_id')
            ->selectRaw('COUNT(DISTINCT follower_id) as follower_count')
            ->selectRaw('COUNT(*) as total_subscriptions')
            ->with('trader')
            ->groupBy('trader_id')
            ->orderBy('follower_count', 'desc')
            ->paginate(20);

        return view('trading-management::backend.trading-management.copy-trading.traders', compact('title', 'traders'));
    }

    public function followers()
    {
        $title = 'Followers';
        $followers = CopyTradingSubscription::select('follower_id')
            ->selectRaw('COUNT(DISTINCT trader_id) as following_count')
            ->selectRaw('COUNT(*) as total_subscriptions')
            ->with('follower')
            ->groupBy('follower_id')
            ->orderBy('following_count', 'desc')
            ->paginate(20);

        return view('trading-management::backend.trading-management.copy-trading.followers', compact('title', 'followers'));
    }

    public function executions(Request $request)
    {
        $title = 'Copy Trading Executions';
        $query = CopyTradingExecution::with(['subscription.trader', 'subscription.follower', 'originalPosition']);

        if ($request->filled('subscription_id')) {
            $query->where('subscription_id', $request->subscription_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $executions = $query->orderBy('created_at', 'desc')->paginate(50);

        $stats = [
            'total' => CopyTradingExecution::count(),
            'success' => CopyTradingExecution::where('status', 'success')->count(),
            'failed' => CopyTradingExecution::where('status', 'failed')->count(),
            'pending' => CopyTradingExecution::where('status', 'pending')->count(),
        ];

        return view('trading-management::backend.trading-management.copy-trading.executions', compact('title', 'executions', 'stats'));
    }

    public function analytics(Request $request)
    {
        $title = 'Copy Trading Analytics';
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $metrics = [
            'total_subscriptions' => CopyTradingSubscription::whereBetween('subscribed_at', [$dateFrom, $dateTo])->count(),
            'active_subscriptions' => CopyTradingSubscription::where('is_active', true)->count(),
            'total_executions' => CopyTradingExecution::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'success_rate' => $this->calculateSuccessRate($dateFrom, $dateTo),
        ];

        $topTraders = CopyTradingSubscription::select('trader_id')
            ->selectRaw('COUNT(DISTINCT follower_id) as follower_count')
            ->with('trader')
            ->groupBy('trader_id')
            ->orderBy('follower_count', 'desc')
            ->limit(10)
            ->get();

        $dailyExecutions = CopyTradingExecution::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('trading-management::backend.trading-management.copy-trading.analytics', compact(
            'title',
            'metrics',
            'topTraders',
            'dailyExecutions',
            'dateFrom',
            'dateTo'
        ));
    }

    public function toggleSubscription(CopyTradingSubscription $subscription)
    {
        $subscription->update([
            'is_active' => !$subscription->is_active,
            'unsubscribed_at' => !$subscription->is_active ? now() : null,
        ]);

        return redirect()->back()->with('success', 'Subscription status updated');
    }

    public function destroySubscription(CopyTradingSubscription $subscription)
    {
        $subscription->delete();
        return redirect()->back()->with('success', 'Subscription deleted');
    }

    protected function calculateSuccessRate($dateFrom, $dateTo)
    {
        $total = CopyTradingExecution::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $success = CopyTradingExecution::where('status', 'success')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        return $total > 0 ? ($success / $total) * 100 : 0;
    }
}
