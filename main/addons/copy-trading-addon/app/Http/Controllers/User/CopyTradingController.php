<?php

namespace Addons\CopyTrading\App\Http\Controllers\User;

use Addons\CopyTrading\App\Http\Controllers\Controller;
use Addons\CopyTrading\App\Services\CopyTradingService;
use Addons\CopyTrading\App\Services\CopyTradingAnalyticsService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CopyTradingController extends Controller
{
    protected CopyTradingService $copyTradingService;
    protected CopyTradingAnalyticsService $analyticsService;

    public function __construct(CopyTradingService $copyTradingService, CopyTradingAnalyticsService $analyticsService)
    {
        $this->copyTradingService = $copyTradingService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Show copy trading settings.
     */
    public function settings(): View
    {
        $user = auth()->user();
        $setting = $this->copyTradingService->getOrCreateSettings($user->id);
        $stats = $this->analyticsService->getTraderStats($user->id);

        $data['title'] = 'Copy Trading Settings';
        $data['setting'] = $setting;
        $data['stats'] = $stats;

        return view('copy-trading::user.settings', $data);
    }

    /**
     * Update copy trading settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'is_enabled' => 'sometimes|boolean',
            'min_followers_balance' => 'nullable|numeric|min:0',
            'max_copiers' => 'nullable|integer|min:1',
            'risk_multiplier_default' => 'nullable|numeric|min:0.1|max:10',
            'allow_manual_trades' => 'sometimes|boolean',
            'allow_auto_trades' => 'sometimes|boolean',
        ]);

        $data = $request->only([
            'is_enabled',
            'min_followers_balance',
            'max_copiers',
            'risk_multiplier_default',
            'allow_manual_trades',
            'allow_auto_trades',
        ]);

        if (isset($data['is_enabled']) && $data['is_enabled']) {
            $this->copyTradingService->enableCopyTrading($user->id, $data);
        } elseif (isset($data['is_enabled']) && !$data['is_enabled']) {
            $this->copyTradingService->disableCopyTrading($user->id);
        } else {
            $this->copyTradingService->updateSettings($user->id, $data);
        }

        return redirect()->route('user.copy-trading.settings')
            ->with('success', 'Settings updated successfully');
    }
}

