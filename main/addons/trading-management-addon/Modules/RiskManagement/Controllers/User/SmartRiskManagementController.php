<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class SmartRiskManagementController extends Controller
{
    public function dashboard()
    {
        $title = 'Smart Risk Management Dashboard';
        return view('trading-management::user.smart-risk.dashboard', compact('title'));
    }

    public function adjustments()
    {
        $title = 'SRM Adjustments';
        return view('trading-management::user.smart-risk.adjustments.index', compact('title'));
    }

    public function insights()
    {
        $title = 'SRM Insights';
        return view('trading-management::user.smart-risk.insights.index', compact('title'));
    }

    public function updateSettings(Request $request)
    {
        // Logic from closure
        return back()->with('notify', NotificationHelper::success(__('Smart Risk settings updated successfully.'), 'Success'));
    }
}
