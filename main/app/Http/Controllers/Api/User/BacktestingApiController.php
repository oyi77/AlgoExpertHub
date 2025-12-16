<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Backtesting & Copy Trading
 *
 * Endpoints for backtesting strategies and copy trading.
 */
class BacktestingApiController extends Controller
{
    /**
     * List Backtests
     */
    public function index()
    {
        if (!class_exists(\Addons\TradingManagement\Modules\Backtesting\Models\Backtest::class)) {
            return response()->json(['success' => false, 'message' => 'Backtesting module not available'], 503);
        }

        $backtests = \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $backtests]);
    }

    /**
     * Run Backtest
     */
    public function run(Request $request)
    {
        if (!class_exists(\Addons\TradingManagement\Modules\Backtesting\Models\Backtest::class)) {
            return response()->json(['success' => false, 'message' => 'Backtesting module not available'], 503);
        }

        $validator = Validator::make($request->all(), [
            'strategy_id' => 'required|integer',
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'initial_balance' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $backtest = \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::create([
            'user_id' => Auth::id(),
            'strategy_id' => $request->strategy_id,
            'symbol' => $request->symbol,
            'timeframe' => $request->timeframe,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'initial_balance' => $request->initial_balance,
            'status' => 'PENDING',
        ]);

        // Dispatch backtest job
        // dispatch(new RunBacktestJob($backtest));

        return response()->json([
            'success' => true,
            'message' => 'Backtest started',
            'data' => $backtest
        ], 201);
    }

    /**
     * Get Backtest Results
     */
    public function show($id)
    {
        if (!class_exists(\Addons\TradingManagement\Modules\Backtesting\Models\Backtest::class)) {
            return response()->json(['success' => false, 'message' => 'Backtesting module not available'], 503);
        }

        $backtest = \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::where('user_id', Auth::id())
            ->with('results')
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $backtest]);
    }

    /**
     * List Available Traders (Copy Trading)
     */
    public function traders()
    {
        if (!class_exists(\Addons\TradingManagement\Modules\CopyTrading\Models\Trader::class)) {
            return response()->json(['success' => false, 'message' => 'Copy trading module not available'], 503);
        }

        $traders = \Addons\TradingManagement\Modules\CopyTrading\Models\Trader::where('is_public', true)
            ->withCount('followers')
            ->with('statistics')
            ->get();

        return response()->json(['success' => true, 'data' => $traders]);
    }

    /**
     * Follow Trader
     */
    public function followTrader(Request $request, $traderId)
    {
        if (!class_exists(\Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class)) {
            return response()->json(['success' => false, 'message' => 'Copy trading module not available'], 503);
        }

        $validator = Validator::make($request->all(), [
            'connection_id' => 'required|integer',
            'copy_percentage' => 'required|numeric|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $subscription = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::create([
            'user_id' => Auth::id(),
            'trader_id' => $traderId,
            'connection_id' => $request->connection_id,
            'copy_percentage' => $request->copy_percentage,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Now following trader',
            'data' => $subscription
        ], 201);
    }

    /**
     * Unfollow Trader
     */
    public function unfollowTrader($traderId)
    {
        if (!class_exists(\Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class)) {
            return response()->json(['success' => false, 'message' => 'Copy trading module not available'], 503);
        }

        \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::where('user_id', Auth::id())
            ->where('trader_id', $traderId)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Unfollowed trader']);
    }

    /**
     * My Copy Trading Subscriptions
     */
    public function mySubscriptions()
    {
        if (!class_exists(\Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class)) {
            return response()->json(['success' => false, 'message' => 'Copy trading module not available'], 503);
        }

        $subscriptions = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::where('user_id', Auth::id())
            ->with(['trader', 'connection'])
            ->get();

        return response()->json(['success' => true, 'data' => $subscriptions]);
    }
}
