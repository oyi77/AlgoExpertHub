<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TradingOperationsController extends Controller
{
    protected $tradingService;

    public function __construct(\App\Services\TradingService $tradingService)
    {
        $this->tradingService = $tradingService;
    }

    /**
     * Display unified Trading Operations page with tabs
     */
    public function index(Request $request)
    {
        $data['title'] = __('Trading Operations');
        $data['activeTab'] = $request->get('tab', 'trading-bots');
        
        $data['tradingManagementEnabled'] = $this->tradingService->isTradingManagementEnabled();

        if ($data['tradingManagementEnabled'] && $data['activeTab'] === 'trading-bots') {
            $data['bots'] = $this->tradingService->getTradingBots(Auth::id());
        }

        return view(Helper::themeView('user.trading.operations'), $data);
    }

    // ========== BETA METHOD ==========

    public function betaIndex(Request $request)
    {
        $data['title'] = __('Trading Operations');
        $data['activeTab'] = $request->get('tab', 'trading-bots');

        $data['tradingManagementEnabled'] = $this->tradingService->isTradingManagementEnabled();

        if ($data['tradingManagementEnabled']) {
            // Trading Bots tab
            if ($data['activeTab'] === 'trading-bots') {
                $data['bots'] = $this->tradingService->getTradingBots(Auth::id());
            }

            // Connections tab
            if ($data['activeTab'] === 'connections') {
                try {
                    if (class_exists(\Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class)) {
                        $data['connections'] = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('user_id', Auth::id())
                            ->orWhere(function($query) {
                                $query->where('is_admin_owned', true)
                                      ->whereHas('assignedUsers', function($q) {
                                          $q->where('id', Auth::id());
                                      });
                            })
                            ->orderBy('created_at', 'desc')
                            ->paginate(20, ['*'], 'connections_page');
                    } else {
                        $data['connections'] = collect([]);
                    }
                } catch (\Exception $e) {
                    $data['connections'] = collect([]);
                }
            }

            // Open Positions tab
            if ($data['activeTab'] === 'open-positions') {
                try {
                    if (class_exists(\Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class)) {
                        $data['positions'] = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::where('user_id', Auth::id())
                            ->where('status', 'open')
                            ->orderBy('opened_at', 'desc')
                            ->paginate(20, ['*'], 'positions_page');
                    } else {
                        $data['positions'] = collect([]);
                    }
                } catch (\Exception $e) {
                    $data['positions'] = collect([]);
                }
            }
        }

        return Inertia::render('User/TradingOperations', $data);
    }
}
