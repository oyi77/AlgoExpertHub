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
                // Try ExchangeConnection first (new unified model), fallback to ExecutionConnection
                $connectionModel = null;
                if (class_exists(\Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class)) {
                    $connectionModel = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class;
                } elseif (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
                    $connectionModel = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class;
                }
                
                if ($connectionModel) {
                    try {
                        $data['connections'] = $connectionModel::where('user_id', Auth::id())
                            ->where('is_admin_owned', false)
                            ->with(['preset', 'user'])
                            ->latest()
                            ->paginate(20, ['*'], 'connections_page');
                    } catch (\Exception $e) {
                        \Log::error('TradingOperations: Error loading connections', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $data['connections'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                }
            }

            // Trading Bots tab
            if ($data['activeTab'] === 'trading-bots') {
                if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class)) {
                    try {
                        $data['bots'] = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::where('user_id', Auth::id())
                            ->with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
                            ->latest()
                            ->paginate(20, ['*'], 'bots_page');
                    } catch (\Exception $e) {
                        \Log::error('TradingOperations: Error loading trading bots', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $data['bots'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                }
            }
        }

        return view(Helper::themeView('user.trading.operations'), $data);
    }
}
