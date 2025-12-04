<?php

namespace Addons\TradingManagement\Modules\Backtesting\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BacktestController extends Controller
{
    public function index()
    {
        $title = 'Trading Test & Backtesting';
        $stats = [
            'total_backtests' => Backtest::count(),
            'completed' => Backtest::where('status', 'completed')->count(),
            'running' => Backtest::where('status', 'running')->count(),
            'pending' => Backtest::where('status', 'pending')->count(),
            'failed' => Backtest::where('status', 'failed')->count(),
        ];

        return view('trading-management::backend.trading-management.test.index', compact('title', 'stats'));
    }

    public function backtests(Request $request)
    {
        $title = 'Backtests';
        $query = Backtest::with(['admin', 'user', 'filterStrategy', 'aiModelProfile', 'preset']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('symbol')) {
            $query->where('symbol', 'like', '%' . $request->symbol . '%');
        }

        $backtests = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('trading-management::backend.trading-management.test.backtests.index', compact('title', 'backtests'));
    }

    public function create()
    {
        $title = 'Create Backtest';
        $filters = FilterStrategy::where('enabled', true)->get();
        $aiModels = AiModelProfile::where('enabled', true)->get();
        $presets = TradingPreset::where('is_default_template', true)->get();

        return view('trading-management::backend.trading-management.test.backtests.create', compact('title', 'filters', 'aiModels', 'presets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'initial_balance' => 'required|numeric|min:100',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'preset_id' => 'nullable|exists:trading_presets,id',
        ]);

        $backtest = Backtest::create([
            ...$validated,
            'admin_id' => auth()->guard('admin')->id(),
            'status' => 'pending',
            'progress_percent' => 0,
        ]);

        return redirect()->route('admin.trading-management.test.backtests.index')
            ->with('success', 'Backtest created and queued for processing');
    }

    public function show(Backtest $backtest)
    {
        $title = 'Backtest Details';
        $backtest->load(['admin', 'user', 'filterStrategy', 'aiModelProfile', 'preset', 'results']);

        $summary = null;
        if ($backtest->status === 'completed' && $backtest->results->isNotEmpty()) {
            $summary = [
                'total_trades' => $backtest->results->count(),
                'winning_trades' => $backtest->results->where('pnl', '>', 0)->count(),
                'losing_trades' => $backtest->results->where('pnl', '<', 0)->count(),
                'total_pnl' => $backtest->results->sum('pnl'),
                'win_rate' => $backtest->results->count() > 0 
                    ? ($backtest->results->where('pnl', '>', 0)->count() / $backtest->results->count()) * 100 
                    : 0,
                'avg_win' => $backtest->results->where('pnl', '>', 0)->avg('pnl') ?? 0,
                'avg_loss' => $backtest->results->where('pnl', '<', 0)->avg('pnl') ?? 0,
                'profit_factor' => $this->calculateProfitFactor($backtest),
            ];
        }

        return view('trading-management::backend.trading-management.test.backtests.show', compact('title', 'backtest', 'summary'));
    }

    public function destroy(Backtest $backtest)
    {
        $backtest->delete();
        return redirect()->route('admin.trading-management.test.backtests.index')
            ->with('success', 'Backtest deleted successfully');
    }

    public function results(Request $request)
    {
        $title = 'Backtest Results';
        $query = BacktestResult::with(['backtest']);

        if ($request->filled('backtest_id')) {
            $query->where('backtest_id', $request->backtest_id);
        }

        $results = $query->orderBy('entry_time', 'desc')->paginate(50);
        $backtests = Backtest::where('status', 'completed')->get();

        return view('trading-management::backend.trading-management.test.results.index', compact('title', 'results', 'backtests'));
    }

    protected function calculateProfitFactor(Backtest $backtest)
    {
        $totalProfit = $backtest->results->where('pnl', '>', 0)->sum('pnl');
        $totalLoss = abs($backtest->results->where('pnl', '<', 0)->sum('pnl'));

        return $totalLoss > 0 ? $totalProfit / $totalLoss : 0;
    }
}
