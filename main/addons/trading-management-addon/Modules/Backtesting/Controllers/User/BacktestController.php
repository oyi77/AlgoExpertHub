<?php

namespace Addons\TradingManagement\Modules\Backtesting\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Addons\TradingManagement\Modules\Backtesting\Jobs\RunBacktestJob;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BacktestController extends Controller
{
    /**
     * Display a listing of user's backtests
     */
    public function index(Request $request)
    {
        $data['title'] = __('Backtesting Center');
        
        $query = Backtest::where('user_id', Auth::id())
            ->with(['preset', 'filterStrategy', 'aiModelProfile', 'result'])
            ->latest();
        
        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        $data['backtests'] = $query->paginate(20);
        $data['activeFilter'] = $request->get('status', 'all');
        
        return view('trading-management::user.backtesting.index', $data);
    }

    /**
     * Show the form for creating a new backtest
     */
    public function create()
    {
        $data['title'] = __('Create Backtest');
        
        // Get user's available resources
        $data['presets'] = TradingPreset::where(function($query) {
                $query->where('created_by_user_id', Auth::id())
                      ->orWhereNull('created_by_user_id');
            })
            ->orderBy('name')
            ->get();
        
        $data['filterStrategies'] = FilterStrategy::where('created_by_user_id', Auth::id())
            ->orderBy('name')
            ->get();
        
        $data['aiProfiles'] = AiModelProfile::where('created_by_user_id', Auth::id())
            ->orderBy('name')
            ->get();
        
        // Available symbols (can be fetched from exchange connections or hardcoded)
        $data['symbols'] = [
            'BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT', 'DOGEUSDT',
            'XRPUSDT', 'DOTUSDT', 'UNIUSDT', 'LINKUSDT', 'LTCUSDT',
            'SOLUSDT', 'MATICUSDT', 'AVAXUSDT', 'ATOMUSDT', 'NEARUSDT'
        ];
        
        $data['timeframes'] = [
            '5m' => '5 Minutes',
            '15m' => '15 Minutes',
            '30m' => '30 Minutes',
            '1h' => '1 Hour',
            '4h' => '4 Hours',
            '1d' => '1 Day',
        ];
        
        return view('trading-management::user.backtesting.create', $data);
    }

    /**
     * Store a newly created backtest
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'symbol' => 'required|string',
            'timeframe' => 'required|string|in:5m,15m,30m,1h,4h,1d',
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date|before_or_equal:today',
            'initial_balance' => 'required|numeric|min:100|max:1000000',
            'preset_id' => 'required|exists:trading_presets,id',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Check date range (max 2 years)
            $start = \Carbon\Carbon::parse($request->start_date);
            $end = \Carbon\Carbon::parse($request->end_date);
            
            if ($start->diffInDays($end) > 730) {
                throw new \Exception('Date range cannot exceed 2 years');
            }

            $backtest = Backtest::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'description' => $request->description,
                'symbol' => $request->symbol,
                'timeframe' => $request->timeframe,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'initial_balance' => $request->initial_balance,
                'preset_id' => $request->preset_id,
                'filter_strategy_id' => $request->filter_strategy_id,
                'ai_model_profile_id' => $request->ai_model_profile_id,
                'status' => 'pending',
                'progress_percent' => 0,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backtest created successfully',
                    'redirect' => route('user.backtesting.show', $backtest->id)
                ]);
            }

            return redirect()->route('user.backtesting.show', $backtest->id)
                ->with('notify', NotificationHelper::success('Backtest created successfully. Click "Run Backtest" to start.', 'Success'));
                
        } catch (\Exception $e) {
            \Log::error('Backtest creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('notify', NotificationHelper::error('Failed to create backtest: ' . $e->getMessage(), 'Error'))
                ->withInput();
        }
    }

    /**
     * Display the specified backtest results
     */
    public function show($id)
    {
        $backtest = Backtest::where('user_id', Auth::id())
            ->with(['preset', 'filterStrategy', 'aiModelProfile', 'result'])
            ->findOrFail($id);
        
        $data['title'] = $backtest->name;
        $data['backtest'] = $backtest;
        
        return view('trading-management::user.backtesting.show', $data);
    }

    /**
     * Remove the specified backtest
     */
    public function destroy($id)
    {
        try {
            $backtest = Backtest::where('user_id', Auth::id())->findOrFail($id);
            
            // Don't allow deletion of running backtests
            if ($backtest->isRunning()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a running backtest'
                ], 400);
            }
            
            $backtest->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Backtest deleted successfully',
                'redirect' => route('user.backtesting.index')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backtest: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run the backtest (dispatch to queue)
     */
    public function run($id)
    {
        try {
            $backtest = Backtest::where('user_id', Auth::id())->findOrFail($id);
            
            // Check if already running or completed
            if ($backtest->isRunning()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backtest is already running'
                ], 400);
            }
            
            // Dispatch job to queue
            RunBacktestJob::dispatch($backtest);
            
            return response()->json([
                'success' => true,
                'message' => 'Backtest started successfully. This may take a few minutes.',
                'backtest_id' => $backtest->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to run backtest', [
                'backtest_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start backtest: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backtest status (for AJAX polling)
     */
    public function status($id)
    {
        try {
            $backtest = Backtest::where('user_id', Auth::id())
                ->select('id', 'status', 'progress_percent', 'error_message')
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'status' => $backtest->status,
                'progress' => $backtest->progress_percent,
                'error_message' => $backtest->error_message,
                'is_completed' => $backtest->isCompleted(),
                'is_running' => $backtest->isRunning()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get status'
            ], 500);
        }
    }
}
