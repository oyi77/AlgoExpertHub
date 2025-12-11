<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Trading Bots
 *
 * Endpoints for managing automated trading bots.
 */
class TradingBotApiController extends Controller
{
    protected $botService;
    protected $workerService;
    protected $monitoringService;

    public function __construct()
    {
        if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService::class)) {
            $this->botService = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService::class);
            $this->workerService = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService::class);
            $this->monitoringService = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService::class);
        }
    }

    /**
     * Get Trading Bots
     *
     * Retrieve user's trading bots with statistics.
     *
     * @queryParam is_active boolean Filter by active status. Example: true
     * @queryParam is_paper_trading boolean Filter by paper trading. Example: false
     * @queryParam search string Search by name. Example: My Bot
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "bots": [...],
     *     "stats": {...}
     *   }
     * }
     */
    public function index(Request $request)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $filters = [
            'is_active' => $request->get('is_active'),
            'is_paper_trading' => $request->get('is_paper_trading'),
            'search' => $request->get('search'),
            'per_page' => $request->get('per_page', 15),
        ];

        $bots = $this->botService->getBots($filters);
        
        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $allBots = $TradingBot::forUser(Auth::id())->get();
        
        $stats = [
            'total' => $allBots->count(),
            'active' => $allBots->where('is_active', true)->count(),
            'paper_trading' => $allBots->where('is_paper_trading', true)->count(),
            'total_profit' => $allBots->sum('total_profit'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'bots' => $bots,
                'stats' => $stats
            ]
        ]);
    }

    /**
     * Create Trading Bot
     *
     * Create a new trading bot.
     *
     * @bodyParam name string required Bot name. Example: My Trading Bot
     * @bodyParam description string Bot description.
     * @bodyParam exchange_connection_id int required Exchange connection ID. Example: 1
     * @bodyParam trading_preset_id int required Trading preset ID. Example: 1
     * @bodyParam filter_strategy_id int Filter strategy ID. Example: 1
     * @bodyParam ai_model_profile_id int AI model profile ID. Example: 1
     * @bodyParam expert_advisor_id int Expert advisor ID. Example: 1
     * @bodyParam trading_mode string required Trading mode: SIGNAL_BASED or MARKET_STREAM_BASED. Example: SIGNAL_BASED
     * @bodyParam data_connection_id int Data connection ID (for MARKET_STREAM_BASED). Example: 1
     * @bodyParam streaming_symbols array Symbols to stream (for MARKET_STREAM_BASED). Example: ["BTCUSDT", "ETHUSDT"]
     * @bodyParam streaming_timeframes array Timeframes to stream. Example: ["1m", "5m"]
     * @bodyParam market_analysis_interval int Market analysis interval in seconds. Example: 60
     * @bodyParam position_monitoring_interval int Position monitoring interval in seconds. Example: 5
     * @bodyParam is_paper_trading boolean Paper trading mode. Example: true
     * @response 201 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function store(Request $request)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exchange_connection_id' => 'required|exists:execution_connections,id',
            'trading_preset_id' => 'required|exists:trading_presets,id',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'expert_advisor_id' => 'nullable|exists:expert_advisors,id',
            'trading_mode' => 'required|in:SIGNAL_BASED,MARKET_STREAM_BASED',
            'data_connection_id' => 'nullable|exists:execution_connections,id',
            'streaming_symbols' => 'nullable|array',
            'streaming_symbols.*' => 'nullable|string',
            'streaming_timeframes' => 'nullable|array',
            'streaming_timeframes.*' => 'nullable|string',
            'market_analysis_interval' => 'nullable|integer|min:10',
            'position_monitoring_interval' => 'nullable|integer|min:1',
            'is_paper_trading' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        
        // Auto-fill data_connection_id if MARKET_STREAM_BASED
        if ($validated['trading_mode'] === 'MARKET_STREAM_BASED' && empty($validated['data_connection_id'])) {
            $validated['data_connection_id'] = $validated['exchange_connection_id'];
        }

        $validated['is_paper_trading'] = $validated['is_paper_trading'] ?? true;

        try {
            $bot = $this->botService->create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Trading bot created successfully',
                'data' => $bot
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create trading bot: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get Trading Bot
     *
     * Retrieve a specific trading bot with monitoring data.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function show($id)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile', 'expertAdvisor'])
            ->forUser(Auth::id())
            ->findOrFail($id);

        $data = [
            'bot' => $bot,
            'worker_status' => $this->monitoringService->getWorkerStatus($bot),
            'metrics' => $this->monitoringService->getBotMetrics($bot),
            'open_positions' => $this->monitoringService->getOpenPositions($bot),
            'position_stats' => $this->monitoringService->calculatePositionStats($bot),
            'queue_stats' => $this->monitoringService->getQueueStats($bot->id),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Update Trading Bot
     *
     * Update a trading bot.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @bodyParam name string Bot name.
     * @bodyParam description string Bot description.
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function update(Request $request, $id)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'exchange_connection_id' => 'sometimes|exists:execution_connections,id',
            'trading_preset_id' => 'sometimes|exists:trading_presets,id',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'expert_advisor_id' => 'nullable|exists:expert_advisors,id',
            'trading_mode' => 'sometimes|in:SIGNAL_BASED,MARKET_STREAM_BASED',
            'data_connection_id' => 'nullable|exists:execution_connections,id',
            'streaming_symbols' => 'nullable|array',
            'streaming_timeframes' => 'nullable|array',
            'market_analysis_interval' => 'nullable|integer|min:10',
            'position_monitoring_interval' => 'nullable|integer|min:1',
            'is_paper_trading' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->botService->update($bot, $validator->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Trading bot updated successfully',
                'data' => $bot->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update trading bot: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete Trading Bot
     *
     * Delete a trading bot.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Trading bot deleted successfully"
     * }
     */
    public function destroy($id)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);

        try {
            $this->botService->delete($bot);
            
            return response()->json([
                'success' => true,
                'message' => 'Trading bot deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete trading bot: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Start Trading Bot
     *
     * Start a trading bot.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Trading bot started successfully"
     * }
     */
    public function start($id)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);

        try {
            $this->botService->start($bot, Auth::id(), null);
            $this->workerService->startWorker($bot);
            
            return response()->json([
                'success' => true,
                'message' => 'Trading bot started successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start bot: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Stop Trading Bot
     *
     * Stop a trading bot.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Trading bot stopped successfully"
     * }
     */
    public function stop($id)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);

        try {
            $this->workerService->stopWorker($bot);
            $this->botService->stop($bot, Auth::id(), null);
            
            return response()->json([
                'success' => true,
                'message' => 'Trading bot stopped successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop bot: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Pause Trading Bot
     *
     * Pause a trading bot.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Trading bot paused successfully"
     * }
     */
    public function pause($id)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);

        try {
            $this->botService->pause($bot, Auth::id(), null);
            
            return response()->json([
                'success' => true,
                'message' => 'Trading bot paused successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause bot: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Resume Trading Bot
     *
     * Resume a paused trading bot.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Trading bot resumed successfully"
     * }
     */
    public function resume($id)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);

        try {
            $this->botService->resume($bot, Auth::id(), null);
            $this->workerService->startWorker($bot);
            
            return response()->json([
                'success' => true,
                'message' => 'Trading bot resumed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume bot: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Restart Trading Bot
     *
     * Restart a trading bot.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Trading bot restarted successfully"
     * }
     */
    public function restart($id)
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);

        try {
            if ($bot->isRunning() || $bot->isPaused()) {
                $this->workerService->stopWorker($bot);
            }

            $this->botService->restart($bot, Auth::id(), null);
            $pid = $this->workerService->startWorker($bot);
            
            return response()->json([
                'success' => true,
                'message' => 'Trading bot restarted successfully',
                'data' => ['worker_pid' => $pid]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restart bot: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get Bot Worker Status
     *
     * Get real-time worker status and metrics.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "worker_status": {...},
     *     "metrics": {...}
     *   }
     * }
     */
    public function workerStatus($id)
    {
        if (!$this->monitoringService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'worker_status' => $this->monitoringService->getWorkerStatus($bot),
                'metrics' => $this->monitoringService->getBotMetrics($bot),
            ]
        ]);
    }

    /**
     * Get Bot Positions
     *
     * Get bot's open positions and statistics.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "positions": [...],
     *     "stats": {...}
     *   }
     * }
     */
    public function positions($id)
    {
        if (!$this->monitoringService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'positions' => $this->monitoringService->getOpenPositions($bot),
                'stats' => $this->monitoringService->calculatePositionStats($bot),
            ]
        ]);
    }

    /**
     * Get Bot Logs
     *
     * Get bot execution logs.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @queryParam limit int Number of logs to retrieve. Example: 50
     * @queryParam level string Filter by log level. Example: error
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function logs($id, Request $request)
    {
        if (!$this->monitoringService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);
        
        $limit = $request->get('limit', 50);
        $level = $request->get('level');
        
        return response()->json([
            'success' => true,
            'data' => $this->monitoringService->getBotLogs($bot->id, $limit, $level)
        ]);
    }

    /**
     * Get Bot Metrics
     *
     * Get bot performance metrics and queue statistics.
     *
     * @urlParam id int required Bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "metrics": {...},
     *     "queue_stats": {...}
     *   }
     * }
     */
    public function metrics($id)
    {
        if (!$this->monitoringService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        $TradingBot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class;
        $bot = $TradingBot::forUser(Auth::id())->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $this->monitoringService->getBotMetrics($bot),
                'queue_stats' => $this->monitoringService->getQueueStats($bot->id),
            ]
        ]);
    }

    /**
     * Get Available Options
     *
     * Get available connections, presets, strategies, etc. for bot creation.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "connections": [...],
     *     "presets": [...],
     *     "filter_strategies": [...],
     *     "ai_profiles": [...],
     *     "expert_advisors": [...]
     *   }
     * }
     */
    public function getAvailableOptions()
    {
        if (!$this->botService) {
            return response()->json([
                'success' => false,
                'message' => 'Trading bot addon not available'
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'connections' => $this->botService->getAvailableConnections(),
                'presets' => $this->botService->getAvailablePresets(),
                'filter_strategies' => $this->botService->getAvailableFilterStrategies(),
                'ai_profiles' => $this->botService->getAvailableAiProfiles(),
                'expert_advisors' => $this->botService->getAvailableExpertAdvisors(),
            ]
        ]);
    }
}
