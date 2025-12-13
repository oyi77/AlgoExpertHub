<?php

namespace Addons\TradingManagement\Modules\TradingBot\Controllers\User;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TradingBotController extends Controller
{
    protected TradingBotService $botService;
    protected TradingBotWorkerService $workerService;
    protected TradingBotMonitoringService $monitoringService;

    public function __construct(
        TradingBotService $botService, 
        TradingBotWorkerService $workerService,
        TradingBotMonitoringService $monitoringService
    ) {
        $this->botService = $botService;
        $this->workerService = $workerService;
        $this->monitoringService = $monitoringService;
    }

    /**
     * Display a listing of user's trading bots
     */
    public function index(Request $request): View
    {
        try {
            $data['title'] = 'My Trading Bots';
            
            // Check if table exists
            if (!Schema::hasTable('trading_bots')) {
                \Log::warning('Trading bots table does not exist');
                return view('trading-management::user.trading-bots.index', [
                    'title' => $data['title'],
                    'bots' => \Illuminate\Pagination\Paginator::empty(),
                    'stats' => [
                        'total' => 0,
                        'active' => 0,
                        'paper_trading' => 0,
                        'total_profit' => 0,
                    ]
                ]);
            }
            
            $filters = [
                'is_active' => $request->get('is_active'),
                'is_paper_trading' => $request->get('is_paper_trading'),
                'search' => $request->get('search'),
                'per_page' => 15,
            ];

            $data['bots'] = $this->botService->getBots($filters);
            
            // Statistics
            $allBots = TradingBot::forUser(auth()->id())->get();
            $data['stats'] = [
                'total' => $allBots->count(),
                'active' => $allBots->where('is_active', true)->count(),
                'paper_trading' => $allBots->where('is_paper_trading', true)->count(),
                'total_profit' => $allBots->sum('total_profit'),
            ];

            return view('trading-management::user.trading-bots.index', $data);
        } catch (\Exception $e) {
            \Log::error('Trading bots index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return view('trading-management::user.trading-bots.index', [
                'title' => 'My Trading Bots',
                'bots' => \Illuminate\Pagination\Paginator::empty(),
                'stats' => [
                    'total' => 0,
                    'active' => 0,
                    'paper_trading' => 0,
                    'total_profit' => 0,
                ]
            ]);
        }
    }

    /**
     * Show the form for creating a new trading bot
     */
    public function create(): View
    {
        $data['title'] = 'Create Trading Bot';
        $data['bot'] = null;
        
        // Get available options
        $data['connections'] = $this->botService->getAvailableConnections();
        $data['presets'] = $this->botService->getAvailablePresets();
        $data['filterStrategies'] = $this->botService->getAvailableFilterStrategies();
        $data['aiProfiles'] = $this->botService->getAvailableAiProfiles();
        $data['expertAdvisors'] = $this->botService->getAvailableExpertAdvisors();

        return view('trading-management::user.trading-bots.create', $data);
    }

    /**
     * Store a newly created trading bot
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exchange_connection_id' => 'required|exists:execution_connections,id',
            'trading_preset_id' => 'required|exists:trading_presets,id',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'expert_advisor_id' => 'nullable|exists:expert_advisors,id',
            'trading_mode' => 'required|in:SIGNAL_BASED,MARKET_STREAM_BASED',
            'data_connection_id' => 'nullable|exists:execution_connections,id', // Changed to execution_connections (unified connection)
            'streaming_symbols' => 'nullable|array',
            'streaming_symbols.*' => 'nullable|string',
            'streaming_symbols_manual' => 'nullable|string', // Manual entry fallback
            'streaming_timeframes' => 'nullable|array',
            'streaming_timeframes.*' => 'nullable|string',
            'market_analysis_interval' => 'nullable|integer|min:10',
            'position_monitoring_interval' => 'nullable|integer|min:1',
            'is_paper_trading' => 'boolean',
        ]);

        // Auto-fill data_connection_id from exchange_connection_id if not provided and MARKET_STREAM_BASED
        if (isset($validated['trading_mode']) && $validated['trading_mode'] === 'MARKET_STREAM_BASED') {
            if (empty($validated['data_connection_id']) && !empty($validated['exchange_connection_id'])) {
                $validated['data_connection_id'] = $validated['exchange_connection_id'];
            }
        }

        // Process streaming_symbols and streaming_timeframes
        if (isset($validated['streaming_symbols']) && is_array($validated['streaming_symbols'])) {
            $validated['streaming_symbols'] = array_filter(array_map('trim', $validated['streaming_symbols']));
        }
        
        // If manual entry is provided and streaming_symbols is empty, parse manual entry
        if (empty($validated['streaming_symbols']) && !empty($validated['streaming_symbols_manual'])) {
            $manualText = trim($validated['streaming_symbols_manual']);
            $validated['streaming_symbols'] = array_filter(
                array_map('trim', preg_split('/[\r\n,]+/', $manualText))
            );
        }
        // Ensure symbols are unique and clean
        if (isset($validated['streaming_symbols']) && is_array($validated['streaming_symbols'])) {
            $validated['streaming_symbols'] = array_unique(array_filter(array_map('strtoupper', array_map('trim', $validated['streaming_symbols']))));
        }
        unset($validated['streaming_symbols_manual']); // Remove manual field after processing
        
        if (isset($validated['streaming_timeframes']) && is_array($validated['streaming_timeframes'])) {
            $validated['streaming_timeframes'] = array_filter(array_map('trim', $validated['streaming_timeframes']));
        }

        $validated['is_paper_trading'] = $validated['is_paper_trading'] ?? true; // Default to paper trading for demo

        try {
            $bot = $this->botService->create($validated);
            
            return redirect()
                ->route('user.trading-management.trading-bots.show', $bot->id)
                ->with('success', 'Trading bot created successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create trading bot: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified trading bot
     */
    public function show($id): View
    {
        $bot = TradingBot::with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile', 'expertAdvisor'])
            ->forUser(auth()->id())
            ->findOrFail($id);

        $data['title'] = $bot->name;
        $data['bot'] = $bot;

        // Get execution logs for this bot through exchange connection
        if ($bot->exchange_connection_id) {
            try {
                $data['executions'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::where('connection_id', $bot->exchange_connection_id)
                    ->with(['signal', 'executionConnection'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            } catch (\Exception $e) {
                \Log::warning('Failed to load executions for bot', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage()
                ]);
                $data['executions'] = \Illuminate\Pagination\Paginator::empty();
            }
        } else {
            $data['executions'] = \Illuminate\Pagination\Paginator::empty();
        }

        // Get monitoring data
        try {
            $data['workerStatus'] = $this->monitoringService->getWorkerStatus($bot);
            $data['botMetrics'] = $this->monitoringService->getBotMetrics($bot);
            $data['openPositions'] = $this->monitoringService->getOpenPositions($bot);
            $data['positionStats'] = $this->monitoringService->calculatePositionStats($bot);
            $data['queueStats'] = $this->monitoringService->getQueueStats($bot->id);
        } catch (\Exception $e) {
            \Log::error('Failed to load monitoring data for bot', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage()
            ]);
            // Provide default values
            $data['workerStatus'] = ['status' => 'stopped', 'is_running' => false];
            $data['botMetrics'] = [];
            $data['openPositions'] = [];
            $data['positionStats'] = [];
            $data['queueStats'] = [];
        }

        return view('trading-management::user.trading-bots.show', $data);
    }

    /**
     * Show the form for editing the specified trading bot
     */
    public function edit($id): View
    {
        $bot = TradingBot::forUser(auth()->id())
            ->with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile', 'expertAdvisor', 'dataConnection'])
            ->findOrFail($id);

        $data['title'] = 'Edit Trading Bot';
        $data['bot'] = $bot;
        
        // Get available options
        $data['connections'] = $this->botService->getAvailableConnections();
        $data['presets'] = $this->botService->getAvailablePresets();
        $data['filterStrategies'] = $this->botService->getAvailableFilterStrategies();
        $data['aiProfiles'] = $this->botService->getAvailableAiProfiles();
        $data['expertAdvisors'] = $this->botService->getAvailableExpertAdvisors();
        
        // Get data connections for MARKET_STREAM_BASED mode
        try {
            $data['dataConnections'] = \Addons\TradingManagement\Modules\DataProvider\Models\DataConnection::where('user_id', auth()->id())
                ->where('status', 'active')
                ->get();
        } catch (\Exception $e) {
            $data['dataConnections'] = collect([]);
        }

        return view('trading-management::user.trading-bots.edit', $data);
    }

    /**
     * Update the specified trading bot
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exchange_connection_id' => 'required|exists:execution_connections,id',
            'trading_preset_id' => 'required|exists:trading_presets,id',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'expert_advisor_id' => 'nullable|exists:expert_advisors,id',
            'trading_mode' => 'required|in:SIGNAL_BASED,MARKET_STREAM_BASED',
            'data_connection_id' => 'nullable|exists:execution_connections,id', // Changed to execution_connections (unified connection)
            'streaming_symbols' => 'nullable|array',
            'streaming_symbols.*' => 'nullable|string',
            'streaming_symbols_manual' => 'nullable|string', // Manual entry fallback
            'streaming_timeframes' => 'nullable|array',
            'streaming_timeframes.*' => 'nullable|string',
            'market_analysis_interval' => 'nullable|integer|min:10',
            'position_monitoring_interval' => 'nullable|integer|min:1',
            'is_paper_trading' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle checkbox values - with hidden inputs, value will always be present
        // Convert to boolean (0 = false, 1 = true)
        $validated['is_paper_trading'] = $request->boolean('is_paper_trading', false);
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active', false);
        }

        // Auto-fill data_connection_id from exchange_connection_id if not provided and MARKET_STREAM_BASED
        if (isset($validated['trading_mode']) && $validated['trading_mode'] === 'MARKET_STREAM_BASED') {
            if (empty($validated['data_connection_id']) && !empty($validated['exchange_connection_id'])) {
                $validated['data_connection_id'] = $validated['exchange_connection_id'];
            }
        }

        // Process streaming_symbols and streaming_timeframes similar to backend controller
        if (isset($validated['streaming_symbols']) && is_array($validated['streaming_symbols'])) {
            $validated['streaming_symbols'] = array_filter(array_map('trim', $validated['streaming_symbols']));
        }
        
        // If manual entry is provided and streaming_symbols is empty, parse manual entry
        if (empty($validated['streaming_symbols']) && !empty($validated['streaming_symbols_manual'])) {
            $manualText = trim($validated['streaming_symbols_manual']);
            $validated['streaming_symbols'] = array_filter(
                array_map('trim', preg_split('/[\r\n,]+/', $manualText))
            );
        }
        // Ensure symbols are unique and clean
        if (isset($validated['streaming_symbols']) && is_array($validated['streaming_symbols'])) {
            $validated['streaming_symbols'] = array_unique(array_filter(array_map('strtoupper', array_map('trim', $validated['streaming_symbols']))));
        }
        unset($validated['streaming_symbols_manual']); // Remove manual field after processing
        
        if (isset($validated['streaming_timeframes']) && is_array($validated['streaming_timeframes'])) {
            $validated['streaming_timeframes'] = array_filter(array_map('trim', $validated['streaming_timeframes']));
        }

        try {
            $this->botService->update($bot, $validated);
            
            return redirect()
                ->route('user.trading-management.trading-bots.show', $bot->id)
                ->with('success', 'Trading bot updated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update trading bot: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified trading bot
     */
    public function destroy($id): RedirectResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        try {
            $this->botService->delete($bot);
            
            return redirect()
                ->route('user.trading-management.trading-bots.index')
                ->with('success', 'Trading bot deleted successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete trading bot: ' . $e->getMessage());
        }
    }

    /**
     * Toggle bot active status
     */
    public function toggleActive($id): RedirectResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        try {
            $this->botService->toggleActive($bot);
            
            $status = $bot->fresh()->is_active ? 'activated' : 'deactivated';
            
            return redirect()
                ->back()
                ->with('success', "Trading bot {$status} successfully!");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to toggle bot status: ' . $e->getMessage());
        }
    }

    /**
     * Browse prebuilt bot templates (marketplace)
     */
    public function marketplace(Request $request): View
    {
        $data['title'] = 'Bot Templates Marketplace';

        $filters = [
            'connection_type' => $request->get('type'),
            'tags' => $request->get('tags', []),
            'search' => $request->get('search'),
            'per_page' => 12,
        ];

        $data['templates'] = $this->botService->getPrebuiltTemplates($filters);
        $data['filters'] = $filters;

        return view('trading-management::user.trading-bots.marketplace', $data);
    }

    /**
     * Show clone template form
     */
    public function clone(TradingBot $template): View
    {
        // Validate template
        if (!$template->isTemplate()) {
            abort(404, 'This bot is not a template');
        }

        if (!$template->canBeClonedBy(auth()->user())) {
            abort(403, 'You do not have permission to clone this template');
        }

        $data['title'] = 'Clone Bot Template: ' . $template->name;
        $data['template'] = $template;

        // Get user's connections (filtered by template's suggested type)
        $connections = $this->botService->getAvailableConnections();
        if ($template->suggested_connection_type) {
            $connections = $connections->filter(function ($conn) use ($template) {
                if ($template->suggested_connection_type === 'both') {
                    return true;
                }
                // Map connection_type enum to suggested type
                $connType = null;
                if ($conn->connection_type === 'CRYPTO_EXCHANGE') {
                    $connType = 'crypto';
                } elseif ($conn->connection_type === 'FX_BROKER') {
                    $connType = 'fx';
                }
                return $connType === $template->suggested_connection_type;
            });
        }
        $data['connections'] = $connections;

        if ($connections->isEmpty()) {
            $data['error'] = 'You need to create an exchange connection first.';
        }

        return view('trading-management::user.trading-bots.clone', $data);
    }

    /**
     * Process clone template
     */
    public function storeClone(Request $request, TradingBot $template): RedirectResponse
    {
        // Validate template
        if (!$template->isTemplate()) {
            return redirect()->route('user.trading-management.trading-bots.marketplace')
                ->with('error', 'This bot is not a template');
        }

        $request->validate([
            'exchange_connection_id' => 'required|exists:execution_connections,id',
            'name' => 'nullable|string|max:255',
            'is_paper_trading' => 'boolean',
        ]);

        try {
            $bot = $this->botService->cloneTemplate(
                $template->id,
                auth()->id(),
                $request->exchange_connection_id,
                [
                    'name' => $request->name,
                    'is_paper_trading' => $request->boolean('is_paper_trading', true),
                ]
            );

            return redirect()
                ->route('user.trading-management.trading-bots.show', $bot->id)
                ->with('success', 'Bot cloned successfully! You can now activate it when ready.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to clone bot: ' . $e->getMessage());
        }
    }

    /**
     * Start trading bot
     */
    public function start($id): RedirectResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        try {
            $this->botService->start($bot, auth()->id(), null);
            
            // Start worker process
            $this->workerService->startWorker($bot);
            
            return redirect()
                ->back()
                ->with('success', 'Trading bot started successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to start bot: ' . $e->getMessage());
        }
    }

    /**
     * Stop trading bot
     */
    public function stop($id): RedirectResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        try {
            // Stop worker first
            $this->workerService->stopWorker($bot);
            
            // Update bot status
            $this->botService->stop($bot, auth()->id(), null);
            
            return redirect()
                ->back()
                ->with('success', 'Trading bot stopped successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to stop bot: ' . $e->getMessage());
        }
    }

    /**
     * Pause trading bot
     */
    public function pause($id): RedirectResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        try {
            $this->botService->pause($bot, auth()->id(), null);
            
            return redirect()
                ->back()
                ->with('success', 'Trading bot paused successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to pause bot: ' . $e->getMessage());
        }
    }

    /**
     * Restart trading bot
     */
    public function restart($id): RedirectResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        try {
            \Log::info('Restarting trading bot (user)', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'user_id' => auth()->id(),
                'current_status' => $bot->status,
            ]);

            // Stop worker if running
            if ($bot->isRunning() || $bot->isPaused()) {
                $this->workerService->stopWorker($bot);
            }

            // Restart via service (stop then start)
            $this->botService->restart($bot, auth()->id(), null);
            
            // Start worker process
            $pid = $this->workerService->startWorker($bot);
            
            \Log::info('Trading bot restarted successfully (user)', [
                'bot_id' => $bot->id,
                'user_id' => auth()->id(),
                'worker_pid' => $pid,
            ]);
            
            return redirect()
                ->back()
                ->with('success', 'Trading bot restarted successfully! Worker PID: ' . $pid);
        } catch (\Exception $e) {
            \Log::error('Failed to restart trading bot (user)', [
                'bot_id' => $bot->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return redirect()
                ->back()
                ->with('error', 'Failed to restart bot: ' . $e->getMessage());
        }
    }

    /**
     * Resume trading bot
     */
    public function resume($id): RedirectResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);

        try {
            $this->botService->resume($bot, auth()->id(), null);
            
            // Restart worker process
            $this->workerService->startWorker($bot);
            
            return redirect()
                ->back()
                ->with('success', 'Trading bot resumed successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to resume bot: ' . $e->getMessage());
        }
    }

    /**
     * Get worker status (AJAX)
     */
    public function workerStatus($id): JsonResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);
        
        $workerStatus = $this->monitoringService->getWorkerStatus($bot);
        $metrics = $this->monitoringService->getBotMetrics($bot);
        
        return response()->json([
            'success' => true,
            'worker_status' => $workerStatus,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Get positions (AJAX)
     */
    public function positions($id): JsonResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);
        
        $openPositions = $this->monitoringService->getOpenPositions($bot);
        $positionStats = $this->monitoringService->calculatePositionStats($bot);
        
        return response()->json([
            'success' => true,
            'positions' => $openPositions,
            'stats' => $positionStats,
        ]);
    }

    /**
     * Get logs (AJAX)
     */
    public function logs($id, Request $request): JsonResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);
        
        $limit = $request->get('limit', 50);
        $level = $request->get('level');
        
        $logs = $this->monitoringService->getBotLogs($bot->id, $limit, $level);
        
        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Get metrics (AJAX)
     */
    public function metrics($id): JsonResponse
    {
        $bot = TradingBot::forUser(auth()->id())->findOrFail($id);
        
        $metrics = $this->monitoringService->getBotMetrics($bot);
        $queueStats = $this->monitoringService->getQueueStats($bot->id);
        
        return response()->json([
            'success' => true,
            'metrics' => $metrics,
            'queue_stats' => $queueStats,
        ]);
    }

    /**
     * Get available symbols from exchange connection (AJAX)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getExchangeSymbols(Request $request): JsonResponse
    {
        $connectionId = $request->get('connection_id');
        
        if (!$connectionId) {
            return response()->json([
                'success' => false,
                'message' => 'Connection ID is required',
                'symbols' => []
            ], 400);
        }

        try {
            // Only allow user to access their own connections
            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $connectionId)
                ->where('user_id', auth()->id())
                ->where('is_admin_owned', false)
                ->firstOrFail();
            
            // For getting symbols, we don't need connection to be fully active
            // Getting symbols is a read-only operation - just need valid credentials
            // Only block if connection has an error status
            if ($connection->status === 'error') {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection has an error. Please fix the connection before getting symbols.',
                    'symbols' => [],
                    'connection_status' => $connection->status,
                    'connection_error' => $connection->last_error
                ], 400);
            }
            
            // Warn if connection is not active, but still allow getting symbols
            if (!$connection->is_active) {
                \Log::info('Getting symbols from inactive connection', [
                    'connection_id' => $connectionId,
                    'status' => $connection->status,
                    'is_active' => $connection->is_active
                ]);
            }

            // Use the same logic as backend controller
            $backendController = new \Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController(
                $this->botService,
                $this->workerService,
                $this->monitoringService
            );
            
            return $backendController->getExchangeSymbols($request);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection not found or you do not have permission to access it',
                'symbols' => []
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to get exchange symbols (user)', [
                'connection_id' => $connectionId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load symbols: ' . $e->getMessage(),
                'symbols' => []
            ], 500);
        }
    }
}
