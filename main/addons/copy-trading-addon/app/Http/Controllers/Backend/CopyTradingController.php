<?php

namespace Addons\CopyTrading\App\Http\Controllers\Backend;

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
     * Show copy trading settings for admin.
     */
    public function settings(): View
    {
        try {
            $admin = auth()->guard('admin')->user();
            
            if (!$admin) {
                abort(403, 'Unauthorized');
            }

            // Check if trading execution engine is required and available
            if (!\App\Support\AddonRegistry::active('trading-execution-engine-addon')) {
                return view('copy-trading::backend.settings', [
                    'title' => 'Copy Trading Settings',
                    'setting' => null,
                    'stats' => [
                        'is_enabled' => false,
                        'follower_count' => 0,
                        'total_copied_trades' => 0,
                    ],
                    'error' => 'Trading execution engine is required for copy trading. Please enable it first.',
                ]);
            }

            try {
                $setting = $this->copyTradingService->getOrCreateSettings(null, $admin->id);
                $stats = $this->analyticsService->getTraderStats(null, $admin->id);
            } catch (\Exception $e) {
                \Log::error('Copy trading settings error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return view('copy-trading::backend.settings', [
                    'title' => 'Copy Trading Settings',
                    'setting' => null,
                    'stats' => [
                        'is_enabled' => false,
                        'follower_count' => 0,
                        'total_copied_trades' => 0,
                    ],
                    'error' => 'Unable to load copy trading settings. Please ensure the trading execution engine is enabled.',
                ]);
            }

            return view('copy-trading::backend.settings', [
                'title' => 'Copy Trading Settings',
                'setting' => $setting,
                'stats' => $stats ?? [
                    'is_enabled' => false,
                    'follower_count' => 0,
                    'total_copied_trades' => 0,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Fatal error in copy trading settings', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return view('copy-trading::backend.settings', [
                'title' => 'Copy Trading Settings',
                'setting' => null,
                'stats' => [
                    'is_enabled' => false,
                    'follower_count' => 0,
                    'total_copied_trades' => 0,
                ],
                'error' => 'An error occurred while loading settings. Please check the logs.',
            ]);
        }
    }

    /**
     * Update copy trading settings for admin.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $admin = auth()->guard('admin')->user();
        
        if (!$admin) {
            abort(403, 'Unauthorized');
        }

        // Check if trading execution engine is required and available
        if (!\App\Support\AddonRegistry::active('trading-execution-engine-addon')) {
            return redirect()->route('admin.copy-trading.settings')
                ->with('error', 'Trading execution engine is required for copy trading. Please enable it first.');
        }

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
            $this->copyTradingService->enableCopyTrading(null, $data, $admin->id);
        } elseif (isset($data['is_enabled']) && !$data['is_enabled']) {
            $this->copyTradingService->disableCopyTrading(null, $admin->id);
        } else {
            $this->copyTradingService->updateSettings(null, $data, $admin->id);
        }

        return redirect()->route('admin.copy-trading.settings')
            ->with('success', 'Settings updated successfully');
    }
}

