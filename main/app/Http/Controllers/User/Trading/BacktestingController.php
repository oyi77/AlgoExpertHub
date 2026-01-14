<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
                    // Load available currency pairs and timeframes
                    try {
                        $data['currencyPairs'] = \App\Models\CurrencyPair::where('status', 1)->get();
                        $data['timeframes'] = \App\Models\TimeFrame::where('status', 1)->get();
                    } catch (\Exception $e) {
                        \Log::error('Backtesting: Error loading pairs/timeframes', ['error' => $e->getMessage()]);
                        $data['currencyPairs'] = collect([]);
                        $data['timeframes'] = collect([]);
                    }
                }

                // Results tab
                if ($data['activeTab'] === 'results') {
                    // Load backtest results
                    try {
                        $data['backtests'] = \App\Models\Backtest::where('user_id', Auth::id())
                            ->orderBy('created_at', 'desc')
                            ->paginate(20);
                    } catch (\Exception $e) {
                        \Log::error('Backtesting: Error loading backtests', ['error' => $e->getMessage()]);
                        $data['backtests'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
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
            'symbol' => 'required|string|max:50',
            'timeframe' => 'required|string',
            'start_date' => 'required|date|before_or_equal:today|before:end_date',
            'end_date' => 'required|date|before_or_equal:today|after:start_date',
            'initial_balance' => 'required|numeric|min:100',
        ]);

        try {
            $backtest = \App\Models\Backtest::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'symbol' => strtoupper($request->symbol),
                'timeframe' => $request->timeframe,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'initial_balance' => $request->initial_balance,
                'status' => 'pending',
            ]);

            // Dispatch job to run backtest in background
            \App\Jobs\RunBacktestJob::dispatch($backtest);

            return redirect()->route('user.trading.backtesting.index', ['tab' => 'results'])
                ->with('notify', NotificationHelper::success(__('Backtest created successfully and is running in the background.')));
        } catch (\Exception $e) {
            \Log::error('Backtest creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with('notify', NotificationHelper::error(__('Failed to create backtest: ') . $e->getMessage()));
        }
    }

    /**
     * Show backtest details
     */
    public function show($id)
    {
        try {
            $backtest = \App\Models\Backtest::where('user_id', Auth::id())
                ->with('trades')
                ->findOrFail($id);

            $data['title'] = __('Backtest Details') . ' - ' . $backtest->name;
            $data['backtest'] = $backtest;
            $data['trades'] = $backtest->trades()->orderBy('entry_time', 'asc')->paginate(50); // Changed to asc for equity curve calculation

            return view(Helper::themeView('user.trading.backtesting.show'), $data);
        } catch (\Exception $e) {
            \Log::error('Backtest show error', ['error' => $e->getMessage()]);
            return redirect()->route('user.trading.backtesting.index', ['tab' => 'results'])
                ->with('notify', NotificationHelper::error(__('Backtest not found.')));
        }
    }

    /**
     * Export backtest trades to CSV
     */
    public function export($id)
    {
        try {
            $backtest = \App\Models\Backtest::where('user_id', Auth::id())
                ->findOrFail($id);

            $trades = $backtest->trades()->orderBy('entry_time', 'asc')->get();

            $filename = 'backtest_' . $backtest->id . '_trades_' . date('Y-m-d') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($trades) {
                $file = fopen('php://output', 'w');
                
                // Header row
                fputcsv($file, [
                    'Entry Time',
                    'Exit Time',
                    'Direction',
                    'Entry Price',
                    'Exit Price',
                    'Quantity',
                    'Profit/Loss',
                    'Profit/Loss %',
                    'Status'
                ]);

                // Data rows
                foreach ($trades as $trade) {
                    fputcsv($file, [
                        $trade->entry_time->format('Y-m-d H:i:s'),
                        $trade->exit_time ? $trade->exit_time->format('Y-m-d H:i:s') : '',
                        strtoupper($trade->direction),
                        $trade->entry_price,
                        $trade->exit_price ?? '',
                        $trade->quantity,
                        $trade->profit_loss,
                        $trade->profit_loss_percent,
                        $trade->status,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return redirect()->route('user.trading.backtesting.show', $id)
                ->with('notify', NotificationHelper::error(__('Failed to export trades.')));
        }
    }

    // ========== BETA METHOD ==========

    public function betaIndex(Request $request)
    {
        $data['title'] = __('Backtesting');
        $data['activeTab'] = $request->get('tab', 'create');
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon')
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'backtesting');

        if ($data['tradingManagementEnabled']) {
            try {
                if ($data['activeTab'] === 'create') {
                    try {
                        $data['currencyPairs'] = \App\Models\CurrencyPair::where('status', 1)->get();
                        $data['timeframes'] = \App\Models\TimeFrame::where('status', 1)->get();
                    } catch (\Exception $e) {
                        $data['currencyPairs'] = collect([]);
                        $data['timeframes'] = collect([]);
                    }
                }

                if ($data['activeTab'] === 'results') {
                    try {
                        $data['backtests'] = \App\Models\Backtest::where('user_id', Auth::id())->orderBy('created_at', 'desc')->paginate(20);
                    } catch (\Exception $e) {
                        $data['backtests'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                }

                if ($data['activeTab'] === 'reports') {
                    $data['reports'] = collect([]);
                }
            } catch (\Exception $e) {
                // Silently fail
            }
        }

        return Inertia::render('User/Backtesting', $data);
    }
}
