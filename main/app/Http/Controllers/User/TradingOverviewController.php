<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Carbon\Carbon;

/**
 * Trading Overview Controller
 * Displays a grid overview of all trading setups (connections, copy trading, etc.)
 */
class TradingOverviewController extends Controller
{
    /**
     * Display the trading overview page with cards
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Trading Overview';
        $data['cards'] = [];

        $userId = Auth::id();

        // Check if Execution Engine module is enabled in trading-management-addon
        $executionEngineEnabled = \App\Support\AddonRegistry::active('trading-management-addon') 
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'execution');

        if ($executionEngineEnabled && class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
            // Get Execution Connections
            $connections = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::userOwned()
                ->where('user_id', $userId)
                ->with(['preset', 'positions' => function($q) {
                    $q->where('status', 'open');
                }])
                ->get();

            foreach ($connections as $connection) {
                // Calculate P/L for today and this week
                $todayStart = Carbon::today();
                $weekStart = Carbon::now()->startOfWeek();

                $todayPnL = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::where('connection_id', $connection->id)
                    ->where('status', 'closed')
                    ->whereDate('closed_at', '>=', $todayStart)
                    ->sum('pnl');

                $weekPnL = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::where('connection_id', $connection->id)
                    ->where('status', 'closed')
                    ->whereDate('closed_at', '>=', $weekStart)
                    ->sum('pnl');

                $openPositions = $connection->positions()->where('status', 'open')->count();

                $data['cards'][] = [
                    'id' => $connection->id,
                    'type' => 'execution_connection',
                    'name' => $connection->name,
                    'status' => $connection->is_active ? 'running' : 'paused',
                    'broker' => $connection->exchange_name,
                    'preset_name' => $connection->preset->name ?? 'No Preset',
                    'pl_today' => $todayPnL,
                    'pl_week' => $weekPnL,
                    'open_positions' => $openPositions,
                    'details_route' => route('user.execution-connections.show', $connection->id),
                    'toggle_route' => $connection->is_active 
                        ? route('user.execution-connections.deactivate', $connection->id)
                        : route('user.execution-connections.activate', $connection->id),
                    'type_label' => ucfirst($connection->type ?? 'crypto'),
                ];
            }
        }

        // Check if Copy Trading module is enabled in trading-management-addon
        $copyTradingEnabled = \App\Support\AddonRegistry::active('trading-management-addon') 
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'copy_trading');

        if ($copyTradingEnabled && class_exists(\Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class)) {
            // Get Copy Trading Subscriptions
            $subscriptions = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::where('follower_id', $userId)
                ->where('is_active', true)
                ->with(['trader'])
                ->get();

            foreach ($subscriptions as $subscription) {
                // Calculate P/L for copy trading (if available)
                $todayPnL = 0;
                $weekPnL = 0;

                if (class_exists(\Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingExecution::class)) {
                    $todayStart = Carbon::today();
                    $weekStart = Carbon::now()->startOfWeek();

                    $todayPnL = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingExecution::where('subscription_id', $subscription->id)
                        ->whereDate('created_at', '>=', $todayStart)
                        ->sum('pnl') ?? 0;

                    $weekPnL = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingExecution::where('subscription_id', $subscription->id)
                        ->whereDate('created_at', '>=', $weekStart)
                        ->sum('pnl') ?? 0;
                }

                $data['cards'][] = [
                    'id' => $subscription->id,
                    'type' => 'copy_trading',
                    'name' => 'Copy: ' . ($subscription->trader->username ?? 'Unknown'),
                    'status' => $subscription->status === 'active' ? 'running' : 'paused',
                    'broker' => 'Copy Trading',
                    'preset_name' => $subscription->preset->name ?? 'No Preset',
                    'pl_today' => $todayPnL,
                    'pl_week' => $weekPnL,
                    'open_positions' => 0,
                    'details_route' => route('user.copy-trading.subscriptions.show', $subscription->id) ?? '#',
                    'toggle_route' => route('user.copy-trading.subscriptions.toggle', $subscription->id) ?? '#',
                    'type_label' => 'Copy Trading',
                ];
            }
        }

        return view(Helper::theme() . 'user.trading_overview')->with($data);
    }
}

