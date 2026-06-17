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
            // v2 cache key due to logic change (date filtering)
            $data['signalGraph'] = Cache::remember('udash:signalGraph:v2:' . $userId, $ttl, function () use ($startDate, $userId) {
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
        // v3 cache keys for logic change (int keys)

        $paymentMap = Cache::remember('udash:paymentAgg:v3:' . $userId, $ttl, function () use ($startDate, $userId) {
            return Payment::where('status', 1)
                ->where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(amount) as total, MONTH(created_at) as month_num')
                ->groupBy('month_num')
                ->pluck('total', 'month_num');
        });

        $withdrawMap = Cache::remember('udash:withdrawAgg:v3:' . $userId, $ttl, function () use ($startDate, $userId) {
            return Withdraw::where('status', 1)
                ->where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(withdraw_amount) as total, MONTH(created_at) as month_num')
                ->groupBy('month_num')
                ->pluck('total', 'month_num');
        });

        $depositMap = Cache::remember('udash:depositAgg:v3:' . $userId, $ttl, function () use ($startDate, $userId) {
            return Deposit::where('status', 1)
                ->where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(amount) as total, MONTH(created_at) as month_num')
                ->groupBy('month_num')
                ->pluck('total', 'month_num');
        });

        // Convert signal graph to map locally for easier lookup
        $signalMap = collect([]);
        if (isset($data['signalGraph'])) {
            $signalMap = $data['signalGraph']->pluck('total', 'month');
        }

        // Construct the 12-month window
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::today()->startOfMonth()->subMonth($i);
            $monthName = $month->monthName;
            $monthNum = $month->month;
            array_push($months, $monthName);

            // O(1) lookup instead of array_search loop
            // Use monthNum (int) for maps that use MONTH()
            $totalAmount->push($paymentMap[$monthNum] ?? 0);
            $withdrawTotalAmount->push($withdrawMap[$monthNum] ?? 0);
            $depositTotalAmount->push($depositMap[$monthNum] ?? 0);
            // Use monthName (string) for signalMap which uses MONTHNAME()
            $signalGrapTotal->push($signalMap[$monthName] ?? 0);
        }

        $data['totalAmount'] = $totalAmount;
        $data['withdrawTotalAmount'] = $withdrawTotalAmount;
        $data['depositTotalAmount'] = $depositTotalAmount;
        $data['signalGrapTotal'] = $signalGrapTotal;
        $data['months'] = $months;


        return $data;
    }
}
