<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Trading
 *
 * Endpoints for trading operations, signals, and execution management.
 */
class TradingController extends Controller
{
    /**
     * Get Multi-Channel Signals
     *
     * Retrieve signals from various sources including auto-created signals.
     *
     * @queryParam tab string Filter by tab: all-signals, signal-sources, channel-forwarding, signal-review, pattern-templates, analytics. Example: all-signals
     * @queryParam page int Page number for pagination. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "signals": [...],
     *     "analytics": {...}
     *   }
     * }
     */
    public function getSignals(Request $request)
    {
        $tab = $request->get('tab', 'all-signals');
        $data = [];

        // Check if addon is enabled
        $multiChannelEnabled = \App\Support\AddonRegistry::active('multi-channel-signal-addon') 
            && \App\Support\AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'user_ui');

        if (!$multiChannelEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'Multi-channel signal addon is not enabled'
            ], 403);
        }

        switch ($tab) {
            case 'all-signals':
                $data['signals'] = \App\Models\Signal::where('auto_created', 1)
                    ->with(['pair', 'time', 'market', 'channelSource'])
                    ->latest()
                    ->paginate(20);
                break;

            case 'signal-sources':
                if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
                    $data['sources'] = \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::where('user_id', Auth::id())
                        ->where('is_admin_owned', false)
                        ->latest()
                        ->paginate(20);
                }
                break;

            case 'channel-forwarding':
                if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
                    $data['channels'] = \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::assignedToUser(Auth::id())
                        ->where('status', 'active')
                        ->with(['assignedUsers', 'assignedPlans', 'signals'])
                        ->latest()
                        ->paginate(20);
                }
                break;

            case 'signal-review':
                $data['reviewSignals'] = \App\Models\Signal::where('auto_created', 1)
                    ->where('is_published', 0)
                    ->with(['pair', 'time', 'market', 'channelSource'])
                    ->latest()
                    ->paginate(20);
                break;

            case 'pattern-templates':
                if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern::class)) {
                    $data['patterns'] = \Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern::where('user_id', Auth::id())
                        ->latest()
                        ->paginate(20);
                }
                break;

            case 'analytics':
                $data['analytics'] = [
                    'total_signals' => \App\Models\Signal::where('auto_created', 1)->count(),
                    'published_signals' => \App\Models\Signal::where('auto_created', 1)->where('is_published', 1)->count(),
                    'draft_signals' => \App\Models\Signal::where('auto_created', 1)->where('is_published', 0)->count(),
                    'active_sources' => class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class) 
                        ? \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::where('user_id', Auth::id())
                            ->where('is_admin_owned', false)
                            ->where('status', 'active')
                            ->count() 
                        : 0,
                ];
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get Execution Operations
     *
     * Retrieve trading execution statistics, logs, and positions.
     *
     * @queryParam type string Type of data: stats, executions, open-positions, closed-positions. Example: stats
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function getExecutions(Request $request)
    {
        $type = $request->get('type', 'stats');
        
        // Check if addon is enabled
        $tradingManagementEnabled = \App\Support\AddonRegistry::active('trading-management-addon');

        if (!$tradingManagementEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'Trading management addon is not enabled'
            ], 403);
        }

        $userConnectionIds = $this->getUserConnectionIds();

        $ExecutionPosition = class_exists(\Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class)
            ? \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class
            : null;
        
        $ExecutionLog = class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class)
            ? \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class
            : null;
        
        $ExecutionConnection = class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)
            ? \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class
            : null;

        $data = [];

        switch ($type) {
            case 'stats':
                $stats = [
                    'active_connections' => 0,
                    'open_positions' => 0,
                    'today_executions' => 0,
                    'today_pnl' => 0,
                ];

                if ($ExecutionConnection && !empty($userConnectionIds)) {
                    $stats['active_connections'] = $ExecutionConnection::whereIn('id', $userConnectionIds)
                        ->where('is_active', 1)
                        ->count();
                }

                if ($ExecutionPosition && !empty($userConnectionIds)) {
                    $stats['open_positions'] = $ExecutionPosition::whereIn('connection_id', $userConnectionIds)
                        ->where('status', 'open')
                        ->count();
                    
                    $stats['today_pnl'] = $ExecutionPosition::whereIn('connection_id', $userConnectionIds)
                        ->where('status', 'closed')
                        ->whereDate('closed_at', today())
                        ->sum('pnl') ?? 0;
                }

                if ($ExecutionLog && !empty($userConnectionIds)) {
                    $stats['today_executions'] = $ExecutionLog::whereIn('connection_id', $userConnectionIds)
                        ->whereDate('created_at', today())
                        ->count();
                }

                $data = $stats;
                break;

            case 'executions':
                if ($ExecutionLog && !empty($userConnectionIds)) {
                    $data['executions'] = $ExecutionLog::whereIn('connection_id', $userConnectionIds)
                        ->with('connection')
                        ->orderBy('created_at', 'desc')
                        ->paginate(20);
                }
                break;

            case 'open-positions':
                if ($ExecutionPosition && !empty($userConnectionIds)) {
                    $data['positions'] = $ExecutionPosition::whereIn('connection_id', $userConnectionIds)
                        ->where('status', 'open')
                        ->with('connection')
                        ->orderBy('created_at', 'desc')
                        ->paginate(20);
                }
                break;

            case 'closed-positions':
                if ($ExecutionPosition && !empty($userConnectionIds)) {
                    $data['positions'] = $ExecutionPosition::whereIn('connection_id', $userConnectionIds)
                        ->where('status', 'closed')
                        ->with('connection')
                        ->orderBy('closed_at', 'desc')
                        ->paginate(20);
                }
                break;

            case 'connections':
                if ($ExecutionConnection && !empty($userConnectionIds)) {
                    $data['connections'] = $ExecutionConnection::whereIn('id', $userConnectionIds)
                        ->where('is_active', 1)
                        ->get();
                }
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Execute Manual Trade
     *
     * Place a manual trade order through a connected exchange/broker.
     *
     * @bodyParam connection_id int required The execution connection ID. Example: 1
     * @bodyParam symbol string required Trading symbol. Example: EURUSD
     * @bodyParam direction string required Trade direction: BUY, SELL, LONG, SHORT. Example: BUY
     * @bodyParam lot_size numeric required Position size. Example: 0.01
     * @bodyParam order_type string required Order type: market or limit. Example: market
     * @bodyParam entry_price numeric Entry price (required for limit orders). Example: 1.0850
     * @bodyParam sl_price numeric Stop loss price. Example: 1.0800
     * @bodyParam tp_price numeric Take profit price. Example: 1.0900
     * @bodyParam notes string Optional trade notes.
     * @response 200 {
     *   "success": true,
     *   "message": "Trade executed successfully",
     *   "data": {
     *     "order_id": "12345",
     *     "symbol": "EURUSD",
     *     "direction": "BUY"
     *   }
     * }
     */
    public function executeTrade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'connection_id' => 'required|exists:execution_connections,id',
            'symbol' => 'required|string',
            'direction' => 'required|in:BUY,SELL,LONG,SHORT',
            'lot_size' => 'required|numeric|min:0.01',
            'order_type' => 'required|in:market,limit',
            'entry_price' => 'nullable|numeric',
            'sl_price' => 'nullable|numeric',
            'tp_price' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Delegate to ExecutionLogController's manualTrade method
        $executionController = new \App\Http\Controllers\User\Trading\ExecutionLogController();
        return $executionController->manualTrade($request);
    }

    /**
     * Get user's connection IDs
     */
    protected function getUserConnectionIds(): array
    {
        $userConnectionIds = [];
        
        if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
            try {
                $userConnectionIds = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('user_id', Auth::id())
                    ->where('is_admin_owned', false)
                    ->pluck('id')
                    ->toArray();
            } catch (\Exception $e) {
                \Log::warning('TradingController: Error loading user execution connections', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $userConnectionIds;
    }
}
