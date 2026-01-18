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

        // Initialize TTL with default value
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $ttlMap = $perf['cache']['ttl_map'] ?? [];
        $ttl = (int)($ttlMap['dashboard.user'] ?? 300);

        $cachedTotals = Cache::remember('udash:totals:' . auth()->id(), $ttl, function () use ($user) {
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

        // Start date for 12 months history
        $startDate = Carbon::today()->startOfMonth()->subMonth(11);

        if ($data['currentPlan'] != null) {
            $data['signalGraph'] = Cache::remember('udash:signalGraph:' . $user->id, $ttl, function () use ($user, $startDate) {
                return UserSignal::where('user_id', $user->id)
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
        $monthMap = array();

        $totalAmount = collect([]);

        $withdrawTotalAmount = collect([]);
        $depositTotalAmount = collect([]);
        $signalGrapTotal = collect([]);

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::today()->startOfMonth()->subMonth($i);
            $monthName = $month->monthName;
            array_push($months, $monthName);
            $monthMap[$monthName] = count($months) - 1;

            $totalAmount->push(0);
            $withdrawTotalAmount->push(0);
            $depositTotalAmount->push(0);
            $signalGrapTotal->push(0);
        }

        $payment = Cache::remember('udash:paymentAgg:' . $user->id, $ttl, function () use ($user, $startDate) {
            return Payment::where('status', 1)
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(amount) as total, MONTHNAME(created_at) as month')
                ->groupBy('month')
                ->get();
        });

        $withdraw = Cache::remember('udash:withdrawAgg:' . $user->id, $ttl, function () use ($user, $startDate) {
            return Withdraw::where('status', 1)
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(withdraw_amount) as total, MONTHNAME(created_at) as month')
                ->groupBy('month')
                ->get();
        });

        $deposit = Cache::remember('udash:depositAgg:' . $user->id, $ttl, function () use ($user, $startDate) {
            return Deposit::where('status', 1)
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(amount) as total, MONTHNAME(created_at) as month')
                ->groupBy('month')
                ->get();
        });

        foreach ($payment as $pay) {
            if (isset($monthMap[$pay->month])) {
                $totalAmount[$monthMap[$pay->month]] = $pay->total;
            }
        }

        foreach ($withdraw as $with) {
            if (isset($monthMap[$with->month])) {
                $withdrawTotalAmount[$monthMap[$with->month]] = $with->total;
            }
        }

        foreach ($deposit as $depo) {
            if (isset($monthMap[$depo->month])) {
                $depositTotalAmount[$monthMap[$depo->month]] = $depo->total;
            }
        }


        $graphs = $data['signalGraph'] ?? [];


        foreach ($graphs as $sig) {
            if (isset($monthMap[$sig->month])) {
                $signalGrapTotal[$monthMap[$sig->month]] = $sig->total;
            }
        }

        $data['totalAmount'] = $totalAmount;
        $data['withdrawTotalAmount'] = $withdrawTotalAmount;
        $data['depositTotalAmount'] = $depositTotalAmount;
        $data['signalGrapTotal'] = $signalGrapTotal;
        $data['months'] = $months;


        return $data;
    }
}
