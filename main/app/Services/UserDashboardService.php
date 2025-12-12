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

        $data['currentPlan'] = $user->currentplan()->first();

        // Initialize TTL with default value
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $ttlMap = $perf['cache']['ttl_map'] ?? [];
        $ttl = (int)($ttlMap['dashboard.user'] ?? 300);

        if ($data['currentPlan'] != null) {
            $data['signalGraph'] = Cache::remember('udash:signalGraph:' . auth()->id(), $ttl, function () {
                return UserSignal::where('user_id', auth()->id())
                    ->selectRaw('COUNT(*) as total, MONTHNAME(created_at) as month')
                    ->groupBy('month')
                    ->get();
            });
        }


        $data['totalbalance'] = $user->balance;
        $data['totalDeposit'] = $user->deposits()->where('status', 1)->sum('amount');
        $data['totalWithdraw'] = $user->withdraws()->where('status', 1)->sum('withdraw_amount');
        $data['totalPayments'] = $user->payments()->where('status', 1)->sum('amount');
        $data['totalSupportTickets'] = $user->tickets()->count();
        $data['user'] = $user;
        $data['transactions'] = $user->transactions()->latest()->with('user')->limit(3)->get();

        $data['signals'] = DashboardSignal::where('user_id', $user->id)->latest()->with('signal.market', 'signal.pair', 'signal.time', 'user')->paginate(Helper::pagination());


        $months = array();

        $totalAmount = collect([]);

        $withdrawTotalAmount = collect([]);
        $depositTotalAmount = collect([]);
        $signalGrapTotal = collect([]);

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::today()->startOfMonth()->subMonth($i);
            array_push($months, $month->monthName);

            $totalAmount->push(0);
            $withdrawTotalAmount->push(0);
            $depositTotalAmount->push(0);
            $signalGrapTotal->push(0);
        }

        $payment = Cache::remember('udash:paymentAgg:' . auth()->id(), $ttl, function () {
            return Payment::where('status', 1)
                ->where('user_id', auth()->id())
                ->whereYear('created_at', now()->year)
                ->selectRaw('SUM(amount) as total, MONTHNAME(created_at) as month')
                ->groupBy('month')
                ->get();
        });

        $withdraw = Cache::remember('udash:withdrawAgg:' . auth()->id(), $ttl, function () {
            return Withdraw::where('status', 1)
                ->where('user_id', auth()->id())
                ->selectRaw('SUM(withdraw_amount) as total, MONTHNAME(created_at) as month')
                ->groupBy('month')
                ->get();
        });

        $deposit = Cache::remember('udash:depositAgg:' . auth()->id(), $ttl, function () {
            return Deposit::where('status', 1)
                ->where('user_id', auth()->id())
                ->selectRaw('SUM(amount) as total, MONTHNAME(created_at) as month')
                ->groupBy('month')
                ->get();
        });

        foreach ($payment as $pay) {
            $result = array_search($pay->month, $months);
            if ($result !== false) {
                $totalAmount[$result] = $pay->total;
            }
        }

        foreach ($withdraw as $with) {
            $result = array_search($with->month, $months);
            if ($result !== false) {
                $withdrawTotalAmount[$result] = $with->total;
            }
        }

        foreach ($deposit as $depo) {
            $result = array_search($depo->month, $months);
            if ($result !== false) {
                $depositTotalAmount[$result] = $depo->total;
            }
        }


        $graphs = $data['signalGraph'] ?? [];


        foreach ($graphs as $sig) {
            $result = array_search($sig->month, $months);
            if ($result !== false) {
                $signalGrapTotal[$result] = $sig->total;
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
