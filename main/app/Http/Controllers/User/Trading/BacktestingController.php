<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BacktestingController extends Controller
{
    /**
     * Display unified Backtesting page with tabs
     */
    public function index(Request $request)
    {
        $data['title'] = __('Backtesting');
        $data['activeTab'] = $request->get('tab', 'create');
        
        // Check if addon is enabled
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon')
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'backtesting');

        if ($data['tradingManagementEnabled']) {
            try {
                // Create Backtest tab
                if ($data['activeTab'] === 'create') {
                    // Load available presets, strategies, etc. for backtest creation
                    if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
                        try {
                            $data['presets'] = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where(function($query) {
                                    $query->where('created_by_user_id', Auth::id())
                                          ->orWhereNull('created_by_user_id');
                                })
                                ->get();
                        } catch (\Exception $e) {
                            \Log::error('Backtesting: Error loading presets', ['error' => $e->getMessage()]);
                            $data['presets'] = collect([]);
                        }
                    } else {
                        $data['presets'] = collect([]);
                    }
                }

                // Results tab
                if ($data['activeTab'] === 'results') {
                    // Load backtest results (if backtesting module exists)
                    $data['results'] = collect([]); // Placeholder
                }

                // Performance Reports tab
                if ($data['activeTab'] === 'reports') {
                    // Load performance reports
                    $data['reports'] = collect([]); // Placeholder
                }
            } catch (\Exception $e) {
                \Log::error('Backtesting: General error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        }

        return view(Helper::themeView('user.trading.backtesting'), $data);
    }

    /**
     * Store a new backtest
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'preset_id' => 'required|exists:trading_presets,id',
            'symbol' => 'required|string|max:50',
            'timeframe' => 'required|string|in:1m,5m,15m,1h,4h,1d',
            'start_date' => 'required|date|before_or_equal:today|before:end_date',
            'end_date' => 'required|date|before_or_equal:today|after:start_date',
            'initial_balance' => 'required|numeric|min:100',
            'description' => 'nullable|string|max:1000',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
        ]);

        try {
            if (!class_exists(\Addons\TradingManagement\Modules\Backtesting\Models\Backtest::class)) {
                return back()->with('error', __('Backtesting module is not available.'));
            }

            $backtest = \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'description' => $request->description,
                'preset_id' => $request->preset_id,
                'filter_strategy_id' => $request->filter_strategy_id,
                'ai_model_profile_id' => $request->ai_model_profile_id,
                'symbol' => strtoupper($request->symbol),
                'timeframe' => $request->timeframe,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'initial_balance' => $request->initial_balance,
                'status' => 'pending',
            ]);

            // Dispatch job to run backtest in background
            if (class_exists(\Addons\TradingManagement\Modules\Backtesting\Jobs\RunBacktestJob::class)) {
                \Addons\TradingManagement\Modules\Backtesting\Jobs\RunBacktestJob::dispatch($backtest);
            } else {
                // Fallback: Run synchronously if job doesn't exist
                \Log::warning('RunBacktestJob not found, running backtest synchronously');
                try {
                    $engine = app(\Addons\TradingManagement\Modules\Backtesting\Services\BacktestEngine::class);
                    $engine->run($backtest);
                } catch (\Exception $e) {
                    \Log::error('Backtest execution error', ['error' => $e->getMessage()]);
                    $backtest->markAsFailed($e->getMessage());
                    return back()->with('error', __('Backtest started but failed: ') . $e->getMessage());
                }
            }

            return redirect()->route('user.trading.backtesting.index', ['tab' => 'results'])
                ->with('success', __('Backtest created successfully and is running in the background.'));
        } catch (\Exception $e) {
            \Log::error('Backtest creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with('error', __('Failed to create backtest: ') . $e->getMessage());
        }
    }

    /**
     * Show backtest details
     */
    public function show($id)
    {
        try {
            if (!class_exists(\Addons\TradingManagement\Modules\Backtesting\Models\Backtest::class)) {
                abort(404);
            }

            $backtest = \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::where('user_id', Auth::id())
                ->with('result', 'preset', 'filterStrategy', 'aiModelProfile')
                ->findOrFail($id);

            $data['title'] = __('Backtest Details') . ' - ' . $backtest->name;
            $data['backtest'] = $backtest;

            return view(Helper::themeView('user.trading.backtesting.show'), $data);
        } catch (\Exception $e) {
            \Log::error('Backtest show error', ['error' => $e->getMessage()]);
            return redirect()->route('user.trading.backtesting.index', ['tab' => 'results'])
                ->with('error', __('Backtest not found.'));
        }
    }
}
