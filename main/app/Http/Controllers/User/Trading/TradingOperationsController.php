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

        if ($data['tradingManagementEnabled'] && $data['activeTab'] === 'trading-bots') {
            $data['bots'] = $this->tradingService->getTradingBots(Auth::id());
        }

        return Inertia::render('User/TradingOperations', $data);
    }
}
