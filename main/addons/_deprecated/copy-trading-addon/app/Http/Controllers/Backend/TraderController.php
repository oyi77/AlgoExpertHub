<?php

namespace Addons\CopyTrading\App\Http\Controllers\Backend;

use Addons\CopyTrading\App\Http\Controllers\Controller;
use Addons\CopyTrading\App\Models\CopyTradingSetting;
use Addons\CopyTrading\App\Models\CopyTradingSubscription;
use Addons\CopyTrading\App\Services\CopyTradingAnalyticsService;
use App\Helpers\Helper\Helper;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TraderController extends Controller
{
    protected CopyTradingAnalyticsService $analyticsService;

    public function __construct(CopyTradingAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * List all traders (users and admins).
     */
    public function index(Request $request): View
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
                return view('copy-trading::backend.traders.index', [
                    'title' => 'Manage Traders',
                    'traders' => $emptyPaginator,
                    'error' => 'Trading execution engine is required for copy trading. Please enable it first.',
                ]);
            }

            try {
                $query = CopyTradingSetting::enabled()
                    ->with(['user', 'admin']);

                if ($request->type === 'admin') {
                    $query->adminOwned();
                } elseif ($request->type === 'user') {
                    $query->userOwned();
                }

                $traders = $query->latest()->paginate(Helper::pagination());

                // Get stats for each trader
                foreach ($traders as $trader) {
                    try {
                        if ($trader->is_admin_owned) {
                            $trader->stats = $this->analyticsService->getTraderStats(null, $trader->admin_id);
                            $admin = $trader->admin;
                            $trader->name = $admin ? ($admin->name ?? $admin->username ?? $admin->email ?? 'Admin #' . $trader->admin_id) : 'Admin #' . $trader->admin_id;
                            $trader->type = 'admin';
                        } else {
                            $trader->stats = $this->analyticsService->getTraderStats($trader->user_id);
                            $user = $trader->user;
                            $trader->name = $user ? ($user->username ?? $user->email ?? 'User #' . $trader->user_id) : 'User #' . $trader->user_id;
                            $trader->type = 'user';
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error getting trader stats', ['trader_id' => $trader->id, 'error' => $e->getMessage()]);
                        $trader->stats = [
                            'win_rate' => 0,
                            'total_pnl' => 0,
                            'follower_count' => 0,
                            'total_copied_trades' => 0,
                        ];
                        $trader->name = $trader->name ?? 'Unknown';
                        $trader->type = $trader->is_admin_owned ? 'admin' : 'user';
                    }
                }

                return view('copy-trading::backend.traders.index', [
                    'title' => 'Manage Traders',
                    'traders' => $traders,
                ]);
            } catch (\Exception $e) {
                \Log::error('Error loading traders', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                    collect([]), 
                    0, 
                    Helper::pagination(), 
                    1,
                    ['path' => request()->url(), 'pageName' => 'page']
                );
                return view('copy-trading::backend.traders.index', [
                    'title' => 'Manage Traders',
                    'traders' => $emptyPaginator,
                    'error' => 'Unable to load traders. Please check the logs for details.',
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Fatal error in traders index', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]), 
                0, 
                Helper::pagination(), 
                1,
                ['path' => request()->url(), 'pageName' => 'page']
            );
            return view('copy-trading::backend.traders.index', [
                'title' => 'Manage Traders',
                'traders' => $emptyPaginator,
                'error' => 'An error occurred while loading traders. Please check the logs.',
            ]);
        }
    }

    /**
     * Show trader details.
     */
    public function show(int $id): View
    {
        $setting = CopyTradingSetting::findOrFail($id);
        
        if ($setting->is_admin_owned) {
            $trader = Admin::findOrFail($setting->admin_id);
            $stats = $this->analyticsService->getTraderStats(null, $setting->admin_id);
            $traderType = 'admin';
            $trader->name = $trader->name ?? $trader->username ?? $trader->email ?? 'Admin #' . $setting->admin_id;
        } else {
            $trader = User::findOrFail($setting->user_id);
            $stats = $this->analyticsService->getTraderStats($setting->user_id);
            $traderType = 'user';
            $trader->name = $trader->username ?? $trader->email ?? 'User #' . $setting->user_id;
        }

        $subscriptions = CopyTradingSubscription::where(function($query) use ($setting) {
            if ($setting->is_admin_owned) {
                // For admin, we'd need to track differently
                // For now, return empty
            } else {
                $query->where('trader_id', $setting->user_id);
            }
        });
        
        // Only eager load connection if it exists
        if (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            $subscriptions = $subscriptions->with(['follower', 'connection']);
        } else {
            $subscriptions = $subscriptions->with(['follower']);
        }
        
        $subscriptions = $subscriptions->get();

        $data['title'] = 'Trader Details';
        $data['setting'] = $setting;
        $data['trader'] = $trader;
        $data['trader_type'] = $traderType;
        $data['stats'] = $stats;
        $data['subscriptions'] = $subscriptions;

        return view('copy-trading::backend.traders.show', $data);
    }

    /**
     * Enable/disable a trader.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $setting = CopyTradingSetting::findOrFail($id);
        $setting->update(['is_enabled' => !$setting->is_enabled]);

        $status = $setting->is_enabled ? 'enabled' : 'disabled';
        return redirect()->back()
            ->with('success', "Trader {$status} successfully");
    }
}

