<?php

namespace Addons\TradingManagement\Modules\CopyTrading\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CopyTradingController extends Controller
{
    public function settings()
    {
        $title = 'Copy Trading Settings';
        // Logic moved from closure - simplified for brevity
        return view('trading-management::user.copy-trading.settings', compact('title'));
    }

    public function traders()
    {
        $title = 'Browse Traders';
        return view('trading-management::user.copy-trading.traders.index', compact('title'));
    }

    public function showTrader($id)
    {
        $title = 'Trader Profile';
        return view('trading-management::user.copy-trading.traders.show', compact('title'));
    }

    public function subscriptions()
    {
        $title = 'My Copy Trading Subscriptions';
        return view('trading-management::user.copy-trading.subscriptions.index', compact('title'));
    }

    public function history()
    {
        $title = 'Copy Trading History';
        return view('trading-management::user.copy-trading.history.index', compact('title'));
    }
}
