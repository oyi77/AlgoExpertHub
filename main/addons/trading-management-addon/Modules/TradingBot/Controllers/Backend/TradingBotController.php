<?php

namespace Addons\TradingManagement\Modules\TradingBot\Controllers\Backend;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
     * Display a listing of all trading bots (admin + users)
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Trading Bots';
        
        $filters = [
            'is_active' => $request->get('is_active'),
            'is_paper_trading' => $request->get('is_paper_trading'),
            'user_id' => $request->get('user_id'),
            'admin_id' => $request->get('admin_id'),
            'search' => $request->get('search'),
            'per_page' => 20,
        ];

        // Admin can see all bots (user + admin owned)
        $data['bots'] = TradingBot::with(['user', 'admin', 'exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
            ->when($filters['is_active'] !== null, function ($query) use ($filters) {
                return $query->where('is_active', $filters['is_active']);
            })
            ->when($filters['is_paper_trading'] !== null, function ($query) use ($filters) {
                return $query->where('is_paper_trading', $filters['is_paper_trading']);
            })
            ->when($filters['user_id'], function ($query) use ($filters) {
                return $query->where('user_id', $filters['user_id']);
            })
            ->when($filters['admin_id'], function ($query) use ($filters) {
                return $query->where('admin_id', $filters['admin_id']);
            })
            ->when($filters['search'], function ($query) use ($filters) {
                return $query->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page']);

        // Statistics
        $allBots = TradingBot::withTrashed()->get();
        $data['stats'] = [
            'total' => $allBots->count(),
            'active' => $allBots->where('is_active', true)->count(),
            'paper_trading' => $allBots->where('is_paper_trading', true)->count(),
            'user_bots' => $allBots->whereNotNull('user_id')->count(),
            'admin_bots' => $allBots->whereNotNull('admin_id')->count(),
            'total_profit' => $allBots->sum('total_profit'),
        ];

        // Get available users and admins for filtering
        $data['users'] = \App\Models\User::select('id', 'username', 'email')->orderBy('username')->get();
        $data['admins'] = \App\Models\Admin::select('id', 'username', 'email')->orderBy('username')->get();

        return view('trading-management::backend.trading-bots.index', $data);
    }

    /**
     * Show the form for creating a new trading bot (admin-owned)
     */
    public function create(): View
    {
        $data['title'] = 'Create Trading Bot';
        $data['bot'] = null;
        
        $data['connections'] = $this->botService->getAvailableConnections();
        $data['presets'] = $this->botService->getAvailablePresets();
        $data['filterStrategies'] = $this->botService->getAvailableFilterStrategies();
        $data['aiProfiles'] = $this->botService->getAvailableAiProfiles();
        $data['expertAdvisors'] = $this->botService->getAvailableExpertAdvisors();
        // Data connections are unified - use exchange connections with data_fetching_enabled
        $data['dataConnections'] = $this->botService->getAvailableDataConnectionsForCreate();

        return view('trading-management::backend.trading-bots.create', $data);
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
            'trading_mode' => 'required|in:SIGNAL_BASED,MARKET_STREAM_BASED',
            'data_connection_id' => 'nullable|exists:execution_connections,id',
            'streaming_symbols' => 'nullable|array',
            'streaming_symbols.*' => 'nullable|string',
            'streaming_symbols_manual' => 'nullable|string', // Manual entry fallback
            'streaming_timeframes' => 'nullable|array',
            'market_analysis_interval' => 'nullable|integer|min:10',
            'position_monitoring_interval' => 'nullable|integer|min:1',
            'is_paper_trading' => 'boolean',
        ]);

        // Process streaming symbols (filter empty values)
        // If manual entry is provided and streaming_symbols is empty, parse manual entry
        if (empty($validated['streaming_symbols']) && !empty($validated['streaming_symbols_manual'])) {
            $manualText = trim($validated['streaming_symbols_manual']);
            // Parse: split by newline or comma, trim each
            $validated['streaming_symbols'] = array_filter(
                array_map('trim', preg_split('/[\n,]+/', $manualText)),
                function($s) { return !empty($s); }
            );
        }
        
        if (isset($validated['streaming_symbols']) && is_array($validated['streaming_symbols'])) {
            $validated['streaming_symbols'] = array_filter(array_map('trim', $validated['streaming_symbols']));
        }
        
        // Remove manual entry from validated (not stored in DB)
        unset($validated['streaming_symbols_manual']);

        $validated['admin_id'] = auth()->guard('admin')->id();
        $validated['is_active'] = $request->get('is_active', true);

        try {
            $bot = $this->botService->create($validated);
            
            return redirect()
                ->route('admin.trading-management.trading-bots.show', $bot->id)
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
        $bot = TradingBot::with(['user', 'admin', 'exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
            ->findOrFail($id);

        $data['title'] = 'Trading Bot: ' . $bot->name;
        $data['bot'] = $bot;

        // Get execution logs for this bot through exchange connection
        // Since execution_logs doesn't have trading_bot_id, we query through the bot's exchange connection
        if ($bot->exchange_connection_id) {
            $data['executions'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::where('connection_id', $bot->exchange_connection_id)
                ->with(['signal', 'executionConnection'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        } else {
            $data['executions'] = \Illuminate\Pagination\Paginator::empty();
        }

        // Get monitoring data
        $data['workerStatus'] = $this->monitoringService->getWorkerStatus($bot);
        $data['botMetrics'] = $this->monitoringService->getBotMetrics($bot);
        $data['openPositions'] = $this->monitoringService->getOpenPositions($bot);
        $data['positionStats'] = $this->monitoringService->calculatePositionStats($bot);
        $data['queueStats'] = $this->monitoringService->getQueueStats($bot->id);

        return view('trading-management::backend.trading-bots.show', $data);
    }

    /**
     * Show the form for editing the specified trading bot
     */
    public function edit($id): View
    {
        $bot = TradingBot::findOrFail($id);
        
        $data['title'] = 'Edit Trading Bot';
        $data['bot'] = $bot;
        
        $data['connections'] = $this->botService->getAvailableConnections();
        $data['presets'] = $this->botService->getAvailablePresets();
        $data['filterStrategies'] = $this->botService->getAvailableFilterStrategies();
        $data['aiProfiles'] = $this->botService->getAvailableAiProfiles();
        $data['dataConnections'] = $bot->exchangeConnection ? $this->botService->getAvailableDataConnections($bot) : collect();

        return view('trading-management::backend.trading-bots.edit', $data);
    }

    /**
     * Update the specified trading bot
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $bot = TradingBot::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exchange_connection_id' => 'required|exists:execution_connections,id',
            'trading_preset_id' => 'required|exists:trading_presets,id',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'trading_mode' => 'required|in:SIGNAL_BASED,MARKET_STREAM_BASED',
            'data_connection_id' => 'nullable|exists:execution_connections,id',
            'streaming_symbols' => 'nullable|array',
            'streaming_symbols.*' => 'nullable|string',
            'streaming_symbols_manual' => 'nullable|string', // Manual entry fallback
            'streaming_timeframes' => 'nullable|array',
            'market_analysis_interval' => 'nullable|integer|min:10',
            'position_monitoring_interval' => 'nullable|integer|min:1',
            'is_paper_trading' => 'boolean',
        ]);

        // Process streaming symbols (filter empty values)
        // If manual entry is provided and streaming_symbols is empty, parse manual entry
        if (empty($validated['streaming_symbols']) && !empty($validated['streaming_symbols_manual'])) {
            $manualText = trim($validated['streaming_symbols_manual']);
            // Parse: split by newline or comma, trim each
            $validated['streaming_symbols'] = array_filter(
                array_map('trim', preg_split('/[\n,]+/', $manualText)),
                function($s) { return !empty($s); }
            );
        }
        
        if (isset($validated['streaming_symbols']) && is_array($validated['streaming_symbols'])) {
            $validated['streaming_symbols'] = array_filter(array_map('trim', $validated['streaming_symbols']));
        }
        
        // Remove manual entry from validated (not stored in DB)
        unset($validated['streaming_symbols_manual']);

        try {
            $this->botService->update($bot, $validated);
            
            return redirect()
                ->route('admin.trading-management.trading-bots.show', $bot->id)
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
        $bot = TradingBot::findOrFail($id);

        try {
            $botName = $bot->name;
            $this->botService->delete($bot);
            
            Log::info('Trading bot deleted', [
                'bot_id' => $id,
                'bot_name' => $botName,
                'deleted_by' => auth()->guard('admin')->id(),
            ]);
            
            return redirect()
                ->route('admin.trading-management.trading-bots.index')
                ->with('success', "Trading bot '{$botName}' deleted successfully!");
        } catch (\Exception $e) {
            Log::error('Failed to delete trading bot', [
                'bot_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->back()
                ->with('error', 'Failed to delete trading bot: ' . $e->getMessage());
        }
    }

    /**
     * Transfer ownership of trading bot to a user
     */
    public function transferOwnership(Request $request, $id): RedirectResponse
    {
        $bot = TradingBot::findOrFail($id);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = \App\Models\User::findOrFail($validated['user_id']);
            
            // Update ownership - ensure all ownership-related fields are updated
            $updateData = [
                'user_id' => $user->id,
                'admin_id' => null,
                'is_admin_owned' => false,
                'created_by_user_id' => $user->id, // Also update created_by_user_id for consistency
            ];
            
            // Ensure bot is not marked as template/default template so it appears in user panel
            if (Schema::hasColumn($bot->getTable(), 'is_default_template')) {
                $updateData['is_default_template'] = false;
            }
            if (Schema::hasColumn($bot->getTable(), 'is_template')) {
                $updateData['is_template'] = false;
            }
            
            $bot->update($updateData);

            Log::info('Trading bot ownership transferred', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'new_user_id' => $user->id,
                'new_user_email' => $user->email,
                'transferred_by' => auth()->guard('admin')->id(),
            ]);

            return redirect()
                ->route('admin.trading-management.trading-bots.index')
                ->with('success', "Trading bot '{$bot->name}' ownership transferred to {$user->username} ({$user->email}) successfully.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to transfer trading bot ownership', [
                'bot_id' => $bot->id,
                'user_id' => $validated['user_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to transfer ownership. Please try again.')
                ->withInput();
        }
    }

    /**
     * Toggle bot active status
     */
    public function toggleActive($id): RedirectResponse
    {
        $bot = TradingBot::findOrFail($id);

        try {
            $this->botService->toggleActive($bot);
            
            return redirect()
                ->back()
                ->with('success', 'Trading bot status updated!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to update bot status: ' . $e->getMessage());
        }
    }

    /**
     * Start trading bot
     */
    public function start($id): RedirectResponse
    {
        $bot = TradingBot::findOrFail($id);

        try {
            $this->botService->start($bot, null, auth()->guard('admin')->id());
            
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
        $bot = TradingBot::findOrFail($id);

        try {
            // Stop worker first
            $this->workerService->stopWorker($bot);
            
            // Update bot status
            $this->botService->stop($bot, null, auth()->guard('admin')->id());
            
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
        $bot = TradingBot::findOrFail($id);

        try {
            $this->botService->pause($bot, null, auth()->guard('admin')->id());
            
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
     * Resume trading bot
     */
    public function resume($id): RedirectResponse
    {
        $bot = TradingBot::findOrFail($id);

        try {
            $this->botService->resume($bot, null, auth()->guard('admin')->id());
            
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
        $bot = TradingBot::findOrFail($id);
        
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
        $bot = TradingBot::findOrFail($id);
        
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
        $bot = TradingBot::findOrFail($id);
        
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
        $bot = TradingBot::findOrFail($id);
        
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
            $connection = ExchangeConnection::findOrFail($connectionId);
            
            if (!$connection->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection is not active',
                    'symbols' => []
                ], 400);
            }

            // Create adapter directly based on connection type (same logic as ExchangeConnectionService)
            $adapter = null;
            
            if ($connection->connection_type === 'CRYPTO_EXCHANGE') {
                // CCXT: Uses load_markets() - no connection needed
                $adapter = new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter(
                    $connection->credentials,
                    $connection->provider
                );
            } elseif ($connection->provider === 'metaapi') {
                // MetaAPI: Uses REST API with auth token - no explicit connection needed
                $adapter = new \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter(
                    $connection->credentials
                );
            } elseif ($connection->provider === 'mtapi_grpc' || 
                      (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'mtapi_grpc')) {
                // MTAPI gRPC: Requires connect() before getAvailableSymbols()
                $credentials = $connection->credentials;
                $globalSettings = \App\Services\GlobalConfigurationService::get('mtapi_global_settings', []);
                
                if (!empty($globalSettings['base_url'])) {
                    $credentials['base_url'] = $globalSettings['base_url'];
                }
                if (!empty($globalSettings['timeout'])) {
                    $credentials['timeout'] = $globalSettings['timeout'];
                }
                
                $adapter = new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter($credentials);
                
                // Connect before getting symbols (required for gRPC)
                try {
                    $adapter->connect($credentials);
                } catch (\Exception $e) {
                    \Log::warning('Failed to connect MTAPI gRPC for symbols', [
                        'connection_id' => $connectionId,
                        'error' => $e->getMessage()
                    ]);
                    // Continue anyway - getAvailableSymbols will handle the error
                }
            } else {
                // Default: MTAPI REST adapter - uses REST API, no connection needed
                $adapter = new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter($connection->credentials);
            }
            
            if (!$adapter || !method_exists($adapter, 'getAvailableSymbols')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adapter does not support fetching symbols',
                    'symbols' => []
                ], 400);
            }

            // Get symbols - each adapter handles it differently:
            // - CCXT: load_markets() then array_keys()
            // - MetaAPI: REST API GET /users/current/accounts/{accountId}/symbols
            // - MTAPI REST: REST API GET /v1/accounts/{accountId}/symbols
            // - MTAPI gRPC: client->getSymbols() (requires connection)
            $symbols = [];
            try {
                $symbols = $adapter->getAvailableSymbols();
            } catch (\Exception $e) {
                \Log::warning('getAvailableSymbols failed', [
                    'connection_id' => $connectionId,
                    'provider' => $connection->provider,
                    'error' => $e->getMessage()
                ]);
                
                // Return fallback symbols for FX brokers
                if ($connection->connection_type === 'FX_BROKER') {
                    $symbols = ['EURUSD', 'GBPUSD', 'USDJPY', 'USDCHF', 'AUDUSD', 'USDCAD', 'NZDUSD', 'XAUUSD', 'XAUUSDc'];
                }
            } finally {
                // Disconnect MTAPI gRPC if connected
                if ($adapter instanceof \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter) {
                    try {
                        if ($adapter->isConnected()) {
                            $adapter->disconnect();
                        }
                    } catch (\Exception $e) {
                        // Ignore disconnect errors
                    }
                }
            }
            
            // Sort symbols alphabetically
            sort($symbols);

            return response()->json([
                'success' => true,
                'symbols' => $symbols,
                'count' => count($symbols)
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch exchange symbols', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch symbols: ' . $e->getMessage(),
                'symbols' => []
            ], 500);
        }
    }
}
