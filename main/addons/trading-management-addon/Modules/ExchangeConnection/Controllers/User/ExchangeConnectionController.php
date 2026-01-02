<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class ExchangeConnectionController extends Controller
{
    public function create()
    {
        $title = 'Create Data Connection';
        return view('trading-management::user.exchange-connections.create', compact('title'));
    }

    public function edit($id)
    {
        $title = 'Edit Data Connection';
        return view('trading-management::user.exchange-connections.edit', compact('title'));
    }

    public function update(Request $request, $id)
    {
        // Logic from closure
        return back()->with('notify', NotificationHelper::success(__('Connection updated successfully.'), 'Success'));
    }
}
