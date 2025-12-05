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
     * Show copy trading dashboard.
     */
    public function index(): View
    {
        try {
            // Check if trading execution engine is required and available
            if (!\App\Support\AddonRegistry::active('trading-execution-engine-addon')) {
                return view('copy-trading::backend.dashboard', [
                    'title' => 'Copy Trading Dashboard',
                    'error' => 'Trading execution engine is required for copy trading. Please enable it first.',
                    'stats' => [
                        'total_traders' => 0,
                        'total_subscriptions' => 0,
                        'total_executions' => 0,
                        'active_followers' => 0,
                    ],
                ]);
            }

            $stats = $this->analyticsService->getSystemStats();

            return view('copy-trading::backend.dashboard', [
                'title' => 'Copy Trading Dashboard',
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('Fatal error in copy trading dashboard', ['error' => $e->getMessage()]);
            return view('copy-trading::backend.dashboard', [
                'title' => 'Copy Trading Dashboard',
                'error' => 'An error occurred while loading dashboard. Please check the logs.',
                'stats' => [
                    'total_traders' => 0,
                    'total_subscriptions' => 0,
                    'total_executions' => 0,
                    'active_followers' => 0,
                ],
            ]);
        }
    }

    /**
     * Show copy trading analytics.
     */
    public function analytics(): View
    {
        try {
            // Check if trading execution engine is required and available
            if (!\App\Support\AddonRegistry::active('trading-execution-engine-addon')) {
                return view('copy-trading::backend.analytics', [
                    'title' => 'Copy Trading Analytics',
                    'error' => 'Trading execution engine is required for copy trading. Please enable it first.',
                    'chartData' => [],
                    'topTraders' => collect([]),
                ]);
            }

            $chartData = $this->analyticsService->getExecutionChartData();
            $topTraders = $this->analyticsService->getTopTraders(10);

            return view('copy-trading::backend.analytics', [
                'title' => 'Copy Trading Analytics',
                'chartData' => $chartData,
                'topTraders' => $topTraders,
            ]);
        } catch (\Exception $e) {
            \Log::error('Fatal error in copy trading analytics', ['error' => $e->getMessage()]);
            return view('copy-trading::backend.analytics', [
                'title' => 'Copy Trading Analytics',
                'error' => 'An error occurred while loading analytics. Please check the logs.',
                'chartData' => [],
                'topTraders' => collect([]),
            ]);
        }
    }

    /**
     * Show all subscriptions.
     */
    public function subscriptions(Request $request): View
    {
        try {
            // Check if trading execution engine is required and available
            if (!\App\Support\AddonRegistry::active('trading-execution-engine-addon')) {
                $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                    collect([]), 
                    0, 
                    Helper::pagination(), 
                    1,
                    ['path' => request()->url(), 'pageName' => 'page']
                );
                return view('copy-trading::backend.subscriptions.index', [
                    'title' => 'Manage Subscriptions',
                    'subscriptions' => $emptyPaginator,
                    'error' => 'Trading execution engine is required for copy trading. Please enable it first.',
                ]);
            }

            $query = \Addons\CopyTrading\App\Models\CopyTradingSubscription::with(['trader', 'follower', 'connection']);

            if ($request->has('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            $subscriptions = $query->latest()->paginate(Helper::pagination());

            return view('copy-trading::backend.subscriptions.index', [
                'title' => 'Manage Subscriptions',
                'subscriptions' => $subscriptions,
            ]);
        } catch (\Exception $e) {
            \Log::error('Fatal error in subscriptions index', ['error' => $e->getMessage()]);
            $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]), 
                0, 
                Helper::pagination(), 
                1,
                ['path' => request()->url(), 'pageName' => 'page']
            );
            return view('copy-trading::backend.subscriptions.index', [
                'title' => 'Manage Subscriptions',
                'subscriptions' => $emptyPaginator,
                'error' => 'An error occurred while loading subscriptions. Please check the logs.',
            ]);
        }
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

