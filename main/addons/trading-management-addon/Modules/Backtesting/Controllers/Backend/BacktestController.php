<?php

namespace Addons\TradingManagement\Modules\Backtesting\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
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

    /**
     * Check data availability for symbol/timeframe (AJAX endpoint)
     */
    public function checkDataAvailability(Request $request, MarketDataService $marketDataService)
    {
        $request->validate([
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
        ]);

        $symbol = $request->symbol;
        $timeframe = $request->timeframe;

        $dateRange = $marketDataService->getAvailableDateRange($symbol, $timeframe);
        $availableDates = $marketDataService->getAvailableDates($symbol, $timeframe);

        if (!$dateRange) {
            return response()->json([
                'available' => false,
                'message' => 'No market data available for ' . $symbol . ' on ' . $timeframe . ' timeframe.',
                'date_range' => null,
                'available_dates' => [],
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Data available from ' . $dateRange['min_date'] . ' to ' . $dateRange['max_date'] . ' (' . $dateRange['total_candles'] . ' candles)',
            'date_range' => [
                'min_date' => $dateRange['min_date'],
                'max_date' => $dateRange['max_date'],
                'total_candles' => $dateRange['total_candles'],
            ],
            'available_dates' => $availableDates,
        ]);
    }

    /**
     * Validate date range availability (AJAX endpoint)
     */
    public function validateDateRange(Request $request, MarketDataService $marketDataService)
    {
        $request->validate([
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $availability = $marketDataService->checkDateRangeAvailability(
            $request->symbol,
            $request->timeframe,
            $request->start_date,
            $request->end_date
        );

        return response()->json($availability);
    }

    public function store(Request $request, MarketDataService $marketDataService)
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

        // Check data availability
        $availability = $marketDataService->checkDateRangeAvailability(
            $validated['symbol'],
            $validated['timeframe'],
            $validated['start_date'],
            $validated['end_date']
        );

        if (!$availability['available']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Insufficient data coverage (' . $availability['coverage_percent'] . '%). Data is missing for ' . count($availability['missing_dates']) . ' dates. Please adjust your date range.');
        }

        // Check if date range is within available data
        $dateRange = $marketDataService->getAvailableDateRange($validated['symbol'], $validated['timeframe']);
        if ($dateRange) {
            if ($validated['start_date'] < $dateRange['min_date']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Start date is before available data. Earliest available date: ' . $dateRange['min_date']);
            }
            if ($validated['end_date'] > $dateRange['max_date']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'End date is after available data. Latest available date: ' . $dateRange['max_date']);
            }
        }

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
        $backtest->load(['admin', 'user', 'filterStrategy', 'aiModelProfile', 'preset', 'result']);

        $result = $backtest->result;
        $summary = null;
        $equityCurve = [];
        $tradeDetails = [];
        
        if ($backtest->status === 'completed' && $result) {
            $summary = [
                'total_trades' => $result->total_trades,
                'winning_trades' => $result->winning_trades,
                'losing_trades' => $result->losing_trades,
                'win_rate' => $result->win_rate,
                'total_profit' => $result->total_profit,
                'total_loss' => $result->total_loss,
                'net_profit' => $result->net_profit,
                'final_balance' => $result->final_balance,
                'return_percent' => $result->return_percent,
                'profit_factor' => $result->profit_factor,
                'sharpe_ratio' => $result->sharpe_ratio,
                'max_drawdown' => $result->max_drawdown,
                'max_drawdown_percent' => $result->max_drawdown_percent,
                'avg_win' => $result->avg_win,
                'avg_loss' => $result->avg_loss,
                'largest_win' => $result->largest_win,
                'largest_loss' => $result->largest_loss,
                'consecutive_wins' => $result->consecutive_wins,
                'consecutive_losses' => $result->consecutive_losses,
                'grade' => $result->grade,
            ];
            
            $equityCurve = $result->equity_curve ?? [];
            $tradeDetails = $result->trade_details ?? [];
        }

        return view('trading-management::backend.trading-management.test.backtests.show', compact('title', 'backtest', 'summary', 'equityCurve', 'tradeDetails'));
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

    /**
     * Download historical data for backtesting/ML/AI
     */
    public function downloadData(Request $request)
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:exchange_connections,id',
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
            'format' => 'required|in:csv,json,pandas,mt4',
            'limit' => 'nullable|integer|min:100|max:100000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        try {
            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::findOrFail($validated['connection_id']);
            
            // Fetch data using adapter
            $adapter = app(\Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory::class)
                ->create($connection);
            
            $limit = $validated['limit'] ?? 10000;
            $result = $adapter->fetchCandles($validated['symbol'], $validated['timeframe'], $limit);

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => $result['message']], 400);
            }

            $data = $result['data'];
            $filename = sprintf('%s_%s_%s.%s', 
                $validated['symbol'], 
                $validated['timeframe'], 
                date('Y-m-d'),
                $validated['format'] === 'pandas' ? 'pkl' : $validated['format']
            );

            // Generate file based on format
            $content = $this->formatData($data, $validated['format']);
            
            return response()->streamDownload(function() use ($content) {
                echo $content;
            }, $filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Format data based on export format
     */
    protected function formatData(array $data, string $format): string
    {
        switch ($format) {
            case 'csv':
                $csv = "timestamp,open,high,low,close,volume\n";
                foreach ($data as $candle) {
                    $csv .= sprintf("%s,%s,%s,%s,%s,%s\n",
                        date('Y-m-d H:i:s', $candle['timestamp'] / 1000),
                        $candle['open'],
                        $candle['high'],
                        $candle['low'],
                        $candle['close'],
                        $candle['volume'] ?? 0
                    );
                }
                return $csv;

            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);

            case 'pandas':
                // Export as JSON that pandas can read
                $formatted = [];
                foreach ($data as $candle) {
                    $formatted[] = [
                        'timestamp' => date('Y-m-d H:i:s', $candle['timestamp'] / 1000),
                        'open' => $candle['open'],
                        'high' => $candle['high'],
                        'low' => $candle['low'],
                        'close' => $candle['close'],
                        'volume' => $candle['volume'] ?? 0,
                    ];
                }
                return json_encode($formatted, JSON_PRETTY_PRINT);

            case 'mt4':
                // MT4 HST format (simplified - actual format is binary)
                return json_encode($data, JSON_PRETTY_PRINT);

            default:
                return json_encode($data);
        }
    }

    protected function calculateProfitFactor(Backtest $backtest)
    {
        $totalProfit = $backtest->results->where('pnl', '>', 0)->sum('pnl');
        $totalLoss = abs($backtest->results->where('pnl', '<', 0)->sum('pnl'));

        return $totalLoss > 0 ? $totalProfit / $totalLoss : 0;
    }
}
