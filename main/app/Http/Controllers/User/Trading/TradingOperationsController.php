<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TradingOperationsController extends Controller
{
    /**
     * Display unified Trading Operations page with tabs
     */
    public function index(Request $request)
    {
        $data['title'] = __('Trading Operations');
        $data['activeTab'] = $request->get('tab', 'connections');
        
        // Check if addon is enabled
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon');

        if ($data['tradingManagementEnabled']) {
            // Connections tab
            if ($data['activeTab'] === 'connections') {
                if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
                    $data['connections'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('user_id', Auth::id())
                        ->where('is_admin_owned', false)
                        ->with(['preset', 'user'])
                        ->latest()
                        ->paginate(20, ['*'], 'connections_page');
                }
            }

            // Executions tab
            if ($data['activeTab'] === 'executions') {
                if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class)) {
                    $data['executions'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::whereHas('connection', function($q) {
                            $q->where('user_id', Auth::id());
                        })
                        ->with(['connection', 'signal'])
                        ->latest()
                        ->paginate(20, ['*'], 'executions_page');
                }
            }

            // Open Positions tab
            if ($data['activeTab'] === 'open-positions') {
                if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionPosition::class)) {
                    $data['openPositions'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionPosition::whereHas('connection', function($q) {
                            $q->where('user_id', Auth::id());
                        })
                        ->where('status', 'open')
                        ->with(['connection', 'signal'])
                        ->latest()
                        ->paginate(20, ['*'], 'open_positions_page');
                }
            }

            // Closed Positions tab
            if ($data['activeTab'] === 'closed-positions') {
                if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionPosition::class)) {
                    $data['closedPositions'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionPosition::whereHas('connection', function($q) {
                            $q->where('user_id', Auth::id());
                        })
                        ->where('status', 'closed')
                        ->with(['connection', 'signal'])
                        ->latest()
                        ->paginate(20, ['*'], 'closed_positions_page');
                }
            }

            // Analytics tab
            if ($data['activeTab'] === 'analytics') {
                if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionAnalytic::class)) {
                    $data['analytics'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionAnalytic::whereHas('connection', function($q) {
                            $q->where('user_id', Auth::id());
                        })
                        ->latest()
                        ->paginate(20, ['*'], 'analytics_page');
                }
            }

            // Trading Bots tab
            if ($data['activeTab'] === 'trading-bots') {
                if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class)) {
                    $data['bots'] = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::where('user_id', Auth::id())
                        ->with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
                        ->latest()
                        ->paginate(20, ['*'], 'bots_page');
                }
            }
        }

        return view(Helper::theme() . 'user.trading.operations', $data);
    }
}
