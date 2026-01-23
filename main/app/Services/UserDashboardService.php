<?php

namespace App\Services;

use App\Helpers\Helper\Helper;
use App\Models\DashboardSignal;
use App\Models\Deposit;
use App\Models\Payment;
use App\Models\UserSignal;
use App\Models\Withdraw;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Cache;
use App\Models\GlobalConfiguration;

class UserDashboardService
{
    public function dashboard()
    {
        $user = auth()->user();
        $userId = $user->id;

        // Initialize TTL with default value
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $ttlMap = $perf['cache']['ttl_map'] ?? [];
        $ttl = (int)($ttlMap['dashboard.user'] ?? 300);

        // Performance: Calculate start date once to limit queries to last 12 months
        $startDate = Carbon::today()->startOfMonth()->subMonths(11);

        $cachedTotals = Cache::remember('udash:totals:' . $userId, $ttl, function () use ($user) {
            return [
                'currentPlan' => $user->currentplan()->first(),
                'totalDeposit' => $user->deposits()->where('status', 1)->sum('amount'),
                'totalWithdraw' => $user->withdraws()->where('status', 1)->sum('withdraw_amount'),
                'totalPayments' => $user->payments()->where('status', 1)->sum('amount'),
                'totalSupportTickets' => $user->tickets()->count(),
                'recentTransactions' => $user->transactions()->latest()->limit(3)->get(),
            ];
        });

        $data['currentPlan'] = $cachedTotals['currentPlan'];
        $data['totalDeposit'] = $cachedTotals['totalDeposit'];
        $data['totalWithdraw'] = $cachedTotals['totalWithdraw'];
        $data['totalPayments'] = $cachedTotals['totalPayments'];
        $data['totalSupportTickets'] = $cachedTotals['totalSupportTickets'];

        if ($data['currentPlan'] != null) {
            // v3 cache key due to logic change (date filtering)
            $data['signalGraph'] = Cache::remember('udash:signalGraph:v3:' . $userId, $ttl, function () use ($startDate, $userId) {
                return UserSignal::where('user_id', $userId)
                    ->where('created_at', '>=', $startDate)
                    ->selectRaw('COUNT(*) as total, MONTHNAME(created_at) as month')
                    ->groupBy('month')
                    ->get();
            });
        }


        $data['totalbalance'] = $user->balance;
        $data['user'] = $user;
        $data['transactions'] = $cachedTotals['recentTransactions'] ?? $user->transactions()->latest()->limit(3)->get();

        $data['signals'] = DashboardSignal::where('user_id', $user->id)->latest()->with('signal.market', 'signal.pair', 'signal.time')->paginate(Helper::pagination());


        $months = array();

        $totalAmount = collect([]);
        $withdrawTotalAmount = collect([]);
        $depositTotalAmount = collect([]);
        $signalGrapTotal = collect([]);

        // Retrieve aggregated data as maps [month => total] for O(1) lookup
        // v3 cache keys for logic change

        $paymentMap = Cache::remember('udash:paymentAgg:v3:' . $userId, $ttl, function () use ($startDate, $userId) {
            return Payment::where('status', 1)
                ->where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(amount) as total, MONTH(created_at) as month')
                ->groupBy('month')
                ->pluck('total', 'month');
        });

        $withdrawMap = Cache::remember('udash:withdrawAgg:v3:' . $userId, $ttl, function () use ($startDate, $userId) {
            return Withdraw::where('status', 1)
                ->where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(withdraw_amount) as total, MONTH(created_at) as month')
                ->groupBy('month')
                ->pluck('total', 'month');
        });

        $depositMap = Cache::remember('udash:depositAgg:v3:' . $userId, $ttl, function () use ($startDate, $userId) {
            return Deposit::where('status', 1)
                ->where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(amount) as total, MONTH(created_at) as month')
                ->groupBy('month')
                ->pluck('total', 'month');
        });

        // Convert signal graph to map locally for easier lookup
        $signalMap = collect([]);
        if (isset($data['signalGraph'])) {
            $signalMap = $data['signalGraph']->pluck('total', 'month');
        }

        // Construct the 12-month window
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::today()->startOfMonth()->subMonth($i);
            array_push($months, $month->monthName);

            // O(1) lookup instead of array_search loop
            $totalAmount->push($paymentMap[$month->month] ?? 0);
            $withdrawTotalAmount->push($withdrawMap[$month->month] ?? 0);
            $depositTotalAmount->push($depositMap[$month->month] ?? 0);
            // Signal map still uses month name keys (API compatibility)
            $signalGrapTotal->push($signalMap[$month->monthName] ?? 0);
        }

        $data['totalAmount'] = $totalAmount;
        $data['withdrawTotalAmount'] = $withdrawTotalAmount;
        $data['depositTotalAmount'] = $depositTotalAmount;
        $data['signalGrapTotal'] = $signalGrapTotal;
        $data['months'] = $months;


        return $data;
    }
}
