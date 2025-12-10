<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotExecutionLog;
use Addons\TradingManagement\Modules\TradingBot\Events\BotStatusChanged;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Addons\TradingManagement\Modules\ExpertAdvisor\Models\ExpertAdvisor;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * TradingBotService
 * 
 * CRUD operations for trading bots
 * Coinrule-like bot builder service
 */
class TradingBotService
{
    /**
     * Create a new trading bot
     * 
     * @param array $data
     * @return TradingBot
     */
    public function create(array $data): TradingBot
    {
        // Validate relationships exist
        $this->validateRelationships($data);

        // Set ownership
        if (Auth::guard('admin')->check()) {
            $data['admin_id'] = Auth::guard('admin')->id();
        } else {
            $data['user_id'] = Auth::id();
        }

        return DB::transaction(function () use ($data) {
            $bot = TradingBot::create($data);
            
            // Log creation
            \Log::info('Trading bot created', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
                'user_id' => $bot->user_id,
                'admin_id' => $bot->admin_id,
            ]);

            return $bot;
        });
    }

    /**
     * Update trading bot
     * 
     * @param TradingBot $bot
     * @param array $data
     * @return TradingBot
     */
    public function update(TradingBot $bot, array $data): TradingBot
    {
        // Validate relationships if changed
        if (isset($data['exchange_connection_id']) || 
            isset($data['trading_preset_id']) || 
            isset($data['filter_strategy_id']) || 
            isset($data['ai_model_profile_id'])) {
            $this->validateRelationships($data, $bot);
        }

        return DB::transaction(function () use ($bot, $data) {
            $bot->update($data);
            
            \Log::info('Trading bot updated', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
            ]);

            return $bot->fresh();
        });
    }

    /**
     * Delete trading bot
     * 
     * @param TradingBot $bot
     * @return bool
     */
    public function delete(TradingBot $bot): bool
    {
        return DB::transaction(function () use ($bot) {
            // Stop worker if running
            if ($bot->isRunning() && $bot->worker_pid) {
                try {
                    $workerService = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService::class);
                    $workerService->stopWorker($bot);
                } catch (\Exception $e) {
                    Log::warning('Failed to stop worker before delete', [
                        'bot_id' => $bot->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Close all open positions (optional - could be configurable)
            // For now, we'll just mark bot as deleted (soft delete)
            // Positions remain open for user to manage manually

            \Log::info('Trading bot deleted', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
            ]);

            return $bot->delete();
        });
    }

    /**
     * Toggle bot active status
     * 
     * @param TradingBot $bot
     * @return TradingBot
     */
    public function toggleActive(TradingBot $bot): TradingBot
    {
        $bot->update(['is_active' => !$bot->is_active]);
        
        \Log::info('Trading bot toggled', [
            'bot_id' => $bot->id,
            'is_active' => $bot->is_active,
        ]);

        return $bot->fresh();
    }

    /**
     * Get bots for current user/admin (excludes templates)
     * 
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getBots(array $filters = [])
    {
        try {
            $query = TradingBot::with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile']);

            // Filter by ownership
            if (Auth::guard('admin')->check()) {
                $adminId = Auth::guard('admin')->id();
                $query->where(function ($q) use ($adminId) {
                    $q->where('admin_id', $adminId)
                      ->orWhereNull('admin_id'); // Show all if super admin
                });
            } else {
                $query->where('user_id', Auth::id());
            }

            // Exclude templates (only show user bots)
            // Check if column exists before using it (table might be sp_trading_bots or trading_bots)
            $tableName = (new TradingBot())->getTable();
            if (Schema::hasColumn($tableName, 'is_default_template')) {
                $query->where(function ($q) {
                    $q->whereNotNull('user_id')
                      ->where('is_default_template', false);
                });
            } elseif (Schema::hasColumn($tableName, 'is_template')) {
                // Use is_template column if it exists
                $query->where(function ($q) {
                    $q->whereNotNull('user_id')
                      ->where('is_template', false);
                });
            } else {
                // Fallback: just filter by user_id if neither column exists
                $query->whereNotNull('user_id');
            }

            // Apply filters
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            if (isset($filters['is_paper_trading'])) {
                $query->where('is_paper_trading', $filters['is_paper_trading']);
            }

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('description', 'like', '%' . $filters['search'] . '%');
                });
            }

            return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
        } catch (\Exception $e) {
            \Log::error('TradingBotService::getBots error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'filters' => $filters
            ]);
            
            // Return empty paginator on error
            $perPage = $filters['per_page'] ?? 15;
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                $perPage,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }
    }

    /**
     * Get prebuilt bot templates for marketplace
     * 
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPrebuiltTemplates(array $filters = [])
    {
        $query = TradingBot::with(['tradingPreset', 'filterStrategy', 'aiModelProfile'])
            ->where(function ($q) {
                $q->where('is_default_template', true)
                  ->orWhereNull('created_by_user_id');
            })
            ->where('visibility', 'PUBLIC_MARKETPLACE');
        
        // Filter by market type
        if (isset($filters['connection_type'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('suggested_connection_type', $filters['connection_type'])
                  ->orWhere('suggested_connection_type', 'both');
            });
        }
        
        // Filter by tags
        if (isset($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }
        
        // Search
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        return $query->orderBy('name')->paginate($filters['per_page'] ?? 12);
    }

    /**
     * Clone a template for user
     * 
     * @param int $templateId
     * @param int $userId
     * @param int $connectionId
     * @param array $options
     * @return TradingBot
     * @throws \Exception
     */
    public function cloneTemplate(int $templateId, int $userId, int $connectionId, array $options = [])
    {
        $user = \App\Models\User::findOrFail($userId);
        $template = TradingBot::findOrFail($templateId);
        
        // Validate template is clonable
        if (!$template->isTemplate()) {
            throw new \Exception('This bot is not a template');
        }
        
        // Clone using model method
        return $template->cloneForUser($user, $connectionId, $options);
    }

    /**
     * Validate relationships exist
     * 
     * @param array $data
     * @param TradingBot|null $bot
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function validateRelationships(array $data, ?TradingBot $bot = null): void
    {
        // Validate exchange connection
        if (isset($data['exchange_connection_id'])) {
            $connectionId = $data['exchange_connection_id'];
        } else {
            $connectionId = $bot?->exchange_connection_id;
        }

        if ($connectionId) {
            ExchangeConnection::findOrFail($connectionId);
        }

        // Validate trading preset
        if (isset($data['trading_preset_id'])) {
            $presetId = $data['trading_preset_id'];
        } else {
            $presetId = $bot?->trading_preset_id;
        }

        if ($presetId) {
            TradingPreset::findOrFail($presetId);
        }

        // Validate filter strategy (optional)
        if (isset($data['filter_strategy_id'])) {
            $filterId = $data['filter_strategy_id'];
        } else {
            $filterId = $bot?->filter_strategy_id;
        }

        if ($filterId) {
            FilterStrategy::findOrFail($filterId);
        }

        // Validate AI model profile (optional)
        if (isset($data['ai_model_profile_id'])) {
            $aiProfileId = $data['ai_model_profile_id'];
        } else {
            $aiProfileId = $bot?->ai_model_profile_id;
        }

        if ($aiProfileId) {
            AiModelProfile::findOrFail($aiProfileId);
        }

        // Validate expert advisor (optional)
        if (isset($data['expert_advisor_id'])) {
            $eaId = $data['expert_advisor_id'];
        } else {
            $eaId = $bot?->expert_advisor_id;
        }

        if ($eaId) {
            ExpertAdvisor::findOrFail($eaId);
        }
    }

    /**
     * Get available connections for current user/admin
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableConnections()
    {
        $query = ExchangeConnection::where('is_active', true);

        if (Auth::guard('admin')->check()) {
            $adminId = Auth::guard('admin')->id();
            $query->where(function ($q) use ($adminId) {
                $q->where('admin_id', $adminId)
                  ->orWhere('is_admin_owned', true); // Admin can use admin-owned connections
            });
        } else {
            // Users can only see their own connections (not admin-owned)
            $query->where('user_id', Auth::id())
                  ->where('is_admin_owned', false)
                  ->whereNull('admin_id'); // Ensure admin_id is null for user-owned connections
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get available presets for current user/admin
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailablePresets()
    {
        $query = TradingPreset::where('enabled', true);

        if (Auth::guard('admin')->check()) {
            // Admin can see all presets
        } else {
            // Users see their own + public presets
            $query->where(function ($q) {
                $q->where('created_by_user_id', Auth::id())
                  ->orWhere('visibility', 'PUBLIC_MARKETPLACE');
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get available filter strategies for current user/admin
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableFilterStrategies()
    {
        $query = FilterStrategy::where('enabled', true);

        if (Auth::guard('admin')->check()) {
            // Admin can see all
        } else {
            // Users see their own + public
            $query->where(function ($q) {
                $q->where('created_by_user_id', Auth::id())
                  ->orWhere('visibility', 'PUBLIC_MARKETPLACE');
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get available AI model profiles for current user/admin
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableAiProfiles()
    {
        $query = AiModelProfile::where('enabled', true);

        if (Auth::guard('admin')->check()) {
            // Admin can see all
        } else {
            // Users see their own + public
            $query->where(function ($q) {
                $q->where('created_by_user_id', Auth::id())
                  ->orWhere('visibility', 'PUBLIC_MARKETPLACE');
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get available expert advisors for current user/admin
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableExpertAdvisors()
    {
        try {
            // Check if table exists
            if (!Schema::hasTable('expert_advisors')) {
                Log::warning('expert_advisors table does not exist. Migration may not have been run.', [
                    'hint' => 'Run: php artisan migrate --path=addons/trading-management-addon/database/migrations/2025_12_06_100001_create_expert_advisors_table.php'
                ]);
                return collect([]);
            }

            $query = ExpertAdvisor::where('status', 'active');

            if (Auth::guard('admin')->check()) {
                $adminId = Auth::guard('admin')->id();
                $query->where(function ($q) use ($adminId) {
                    $q->where('admin_id', $adminId)
                      ->orWhere('is_admin_owned', true);
                });
            } else {
                // Users see their own + public
                $query->where(function ($q) {
                    $q->where('user_id', Auth::id())
                      ->orWhere('visibility', 'public');
                });
            }

            return $query->orderBy('name')->get();
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle table not found or other database errors gracefully
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Base table or view not found')) {
                Log::warning('expert_advisors table does not exist. Migration may not have been run.', [
                    'error' => $e->getMessage(),
                    'hint' => 'Run: php artisan migrate --path=addons/trading-management-addon/database/migrations/2025_12_06_100001_create_expert_advisors_table.php'
                ]);
                return collect([]);
            }
            throw $e; // Re-throw if it's a different database error
        } catch (\Throwable $e) {
            Log::error('Failed to get available expert advisors', [
                'error' => $e->getMessage(),
            ]);
            return collect([]); // Return empty collection on any error
        }
    }

    /**
     * Get available data connections for creating a bot
     * Since connections are unified, show all active exchange connections
     * (data_fetching_enabled is optional - unified connections can be used for both)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableDataConnectionsForCreate()
    {
        $query = ExchangeConnection::where('is_active', true);
        
        // Since connections are unified, we show all active connections
        // The data_fetching_enabled flag is optional - if set, prefer those, but show all active ones
        // This allows users to use any connection for data fetching

        if (Auth::guard('admin')->check()) {
            $adminId = Auth::guard('admin')->id();
            $query->where(function ($q) use ($adminId) {
                $q->where('admin_id', $adminId)
                  ->orWhere('is_admin_owned', true);
            });
        } else {
            // Users can only see their own connections (not admin-owned)
            $query->where('user_id', Auth::id())
                  ->where('is_admin_owned', false)
                  ->whereNull('admin_id'); // Ensure admin_id is null for user-owned connections
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get available data connections for bot (matching connection type)
     * 
     * @param TradingBot $bot
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableDataConnections(TradingBot $bot)
    {
        $connectionType = $bot->getConnectionType();
        
        if (!$connectionType) {
            return collect();
        }

        $query = ExchangeConnection::where('is_active', true)
            ->where('data_fetching_enabled', true);

        // Match connection type
        if ($connectionType === 'crypto') {
            $query->where('connection_type', 'CRYPTO_EXCHANGE');
        } else {
            $query->where('connection_type', 'FX_BROKER');
        }

        if (Auth::guard('admin')->check()) {
            $adminId = Auth::guard('admin')->id();
            $query->where(function ($q) use ($adminId) {
                $q->where('admin_id', $adminId)
                  ->orWhere('is_admin_owned', true);
            });
        } else {
            $query->where('user_id', Auth::id());
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Validate bot can be started
     * 
     * @param TradingBot $bot
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateForStart(TradingBot $bot): array
    {
        if (!$bot->exchangeConnection) {
            return ['valid' => false, 'message' => 'Exchange connection is required'];
        }

        // Verify exchange connection is stabilized
        $connectionService = app(ExchangeConnectionService::class);
        if (!$connectionService->isStabilized($bot->exchangeConnection)) {
            return ['valid' => false, 'message' => 'Exchange connection must be stabilized before starting bot. Please test the connection first.'];
        }

        if (!$bot->exchangeConnection->is_active) {
            return ['valid' => false, 'message' => 'Exchange connection must be active'];
        }

        if (!$bot->tradingPreset) {
            return ['valid' => false, 'message' => 'Trading preset is required'];
        }

        if ($bot->requiresDataConnection()) {
            // For MARKET_STREAM_BASED mode, we need a data connection
            // If no separate data connection is set, use exchange connection as fallback
            $dataConn = $bot->dataConnection;
            
            // Fallback: use exchange connection as data connection if compatible
            if (!$dataConn && $bot->exchangeConnection) {
                // Auto-assign exchange connection as data connection
                $bot->update(['data_connection_id' => $bot->exchange_connection_id]);
                $bot->refresh();
                $dataConn = $bot->dataConnection;
                Log::info('Auto-assigned exchange connection as data connection', [
                    'bot_id' => $bot->id,
                    'connection_id' => $bot->exchange_connection_id
                ]);
            }
            
            if (!$dataConn) {
                return [
                    'valid' => false, 
                    'message' => 'Data connection is required for MARKET_STREAM_BASED mode. Please edit the bot and assign a data connection, or ensure the exchange connection supports data streaming.'
                ];
            }

            if (!$dataConn->is_active) {
                return ['valid' => false, 'message' => 'Data connection must be active'];
            }

            // Get streaming symbols - use defaults if not configured
            $streamingSymbols = $bot->getStreamingSymbols();
            if (empty($streamingSymbols)) {
                // Try to get default symbols from exchange connection
                $defaultSymbols = null;
                if ($dataConn) {
                    try {
                        // Try different methods to get symbols
                        if (method_exists($dataConn, 'getAvailableSymbols')) {
                            $availableSymbols = $dataConn->getAvailableSymbols();
                            if (!empty($availableSymbols) && is_array($availableSymbols)) {
                                // Use first 5 symbols as default
                                $defaultSymbols = array_slice($availableSymbols, 0, 5);
                            }
                        } elseif (isset($dataConn->config['default_symbols']) && is_array($dataConn->config['default_symbols'])) {
                            $defaultSymbols = array_slice($dataConn->config['default_symbols'], 0, 5);
                        } elseif ($dataConn->exchange_name) {
                            // Use common symbols based on exchange type
                            if ($dataConn->connection_type === 'CRYPTO_EXCHANGE') {
                                $defaultSymbols = ['BTC/USDT', 'ETH/USDT', 'BNB/USDT'];
                            } else {
                                // FX broker - use common pairs
                                $defaultSymbols = ['EUR/USD', 'GBP/USD', 'USD/JPY'];
                            }
                        }
                        
                        if ($defaultSymbols) {
                            $bot->update(['streaming_symbols' => $defaultSymbols]);
                            $streamingSymbols = $defaultSymbols;
                            Log::info('Auto-assigned default streaming symbols', [
                                'bot_id' => $bot->id,
                                'symbols' => $defaultSymbols
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to get available symbols from connection', [
                            'bot_id' => $bot->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                if (empty($streamingSymbols)) {
                    return [
                        'valid' => false, 
                        'message' => 'Streaming symbols are required for MARKET_STREAM_BASED mode. Please edit the bot and configure streaming symbols in the "Market Stream Configuration" section.'
                    ];
                }
            }

            // Get streaming timeframes - use defaults if not configured
            $streamingTimeframes = $bot->getStreamingTimeframes();
            if (empty($streamingTimeframes)) {
                // Use default timeframes
                $defaultTimeframes = ['1h', '4h', '1d'];
                $bot->update(['streaming_timeframes' => $defaultTimeframes]);
                $streamingTimeframes = $defaultTimeframes;
                Log::info('Auto-assigned default streaming timeframes', [
                    'bot_id' => $bot->id,
                    'timeframes' => $defaultTimeframes
                ]);
            }

            // Validate data connection type matches exchange connection type
            $exchangeType = $bot->getConnectionType();
            $dataType = $dataConn->connection_type === 'CRYPTO_EXCHANGE' ? 'crypto' : 'fx';
            
            if ($exchangeType !== $dataType) {
                return [
                    'valid' => false, 
                    'message' => 'Data connection type must match exchange connection type. Exchange: ' . $exchangeType . ', Data: ' . $dataType
                ];
            }
        }

        // Validate expert advisor if assigned
        if ($bot->expert_advisor_id) {
            if (!$bot->expertAdvisor) {
                return ['valid' => false, 'message' => 'Expert advisor not found'];
            }

            if (!$bot->expertAdvisor->isActive()) {
                return ['valid' => false, 'message' => 'Expert advisor must be active'];
            }

            if (!$bot->expertAdvisor->fileExists()) {
                return ['valid' => false, 'message' => 'Expert advisor file not found'];
            }
        }

        if (!$bot->is_active) {
            return ['valid' => false, 'message' => 'Bot must be enabled (is_active) before starting'];
        }

        return ['valid' => true, 'message' => 'Bot is ready to start'];
    }

    /**
     * Start trading bot
     * 
     * @param TradingBot $bot
     * @param int|null $executedByUserId
     * @param int|null $executedByAdminId
     * @return TradingBot
     * @throws \Exception
     */
    public function start(TradingBot $bot, ?int $executedByUserId = null, ?int $executedByAdminId = null): TradingBot
    {
        // Validate
        $validation = $this->validateForStart($bot);
        if (!$validation['valid']) {
            throw new \Exception($validation['message']);
        }

        if ($bot->isRunning()) {
            throw new \Exception('Bot is already running');
        }

        return DB::transaction(function () use ($bot, $executedByUserId, $executedByAdminId) {
            $oldStatus = $bot->status;
            
            // Update status
            $bot->update([
                'status' => 'running',
                'last_started_at' => now(),
            ]);

            // Log execution
            TradingBotExecutionLog::create([
                'bot_id' => $bot->id,
                'action' => 'start',
                'executed_at' => now(),
                'executed_by_user_id' => $executedByUserId,
                'executed_by_admin_id' => $executedByAdminId,
                'notes' => 'Bot started',
            ]);

            // Fire event
            event(new BotStatusChanged($bot->fresh(), $oldStatus, 'running', $executedByUserId, $executedByAdminId));

            Log::info('Trading bot started', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
            ]);

            return $bot->fresh();
        });
    }

    /**
     * Stop trading bot
     * 
     * @param TradingBot $bot
     * @param int|null $executedByUserId
     * @param int|null $executedByAdminId
     * @return TradingBot
     */
    public function stop(TradingBot $bot, ?int $executedByUserId = null, ?int $executedByAdminId = null): TradingBot
    {
        return DB::transaction(function () use ($bot, $executedByUserId, $executedByAdminId) {
            $oldStatus = $bot->status;
            
            // Update status
            $bot->update([
                'status' => 'stopped',
                'last_stopped_at' => now(),
                'worker_pid' => null,
            ]);

            // Log execution
            TradingBotExecutionLog::create([
                'bot_id' => $bot->id,
                'action' => 'stop',
                'executed_at' => now(),
                'executed_by_user_id' => $executedByUserId,
                'executed_by_admin_id' => $executedByAdminId,
                'notes' => 'Bot stopped',
            ]);

            // Fire event
            event(new BotStatusChanged($bot->fresh(), $oldStatus, 'stopped', $executedByUserId, $executedByAdminId));

            Log::info('Trading bot stopped', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
            ]);

            return $bot->fresh();
        });
    }

    /**
     * Pause trading bot
     * 
     * @param TradingBot $bot
     * @param int|null $executedByUserId
     * @param int|null $executedByAdminId
     * @return TradingBot
     */
    public function pause(TradingBot $bot, ?int $executedByUserId = null, ?int $executedByAdminId = null): TradingBot
    {
        if (!$bot->isRunning()) {
            throw new \Exception('Bot must be running to pause');
        }

        return DB::transaction(function () use ($bot, $executedByUserId, $executedByAdminId) {
            $oldStatus = $bot->status;
            
            // Update status
            $bot->update([
                'status' => 'paused',
                'last_paused_at' => now(),
            ]);

            // Log execution
            TradingBotExecutionLog::create([
                'bot_id' => $bot->id,
                'action' => 'pause',
                'executed_at' => now(),
                'executed_by_user_id' => $executedByUserId,
                'executed_by_admin_id' => $executedByAdminId,
                'notes' => 'Bot paused',
            ]);

            // Fire event
            event(new BotStatusChanged($bot->fresh(), $oldStatus, 'paused', $executedByUserId, $executedByAdminId));

            Log::info('Trading bot paused', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
            ]);

            return $bot->fresh();
        });
    }

    /**
     * Resume paused trading bot
     * 
     * @param TradingBot $bot
     * @param int|null $executedByUserId
     * @param int|null $executedByAdminId
     * @return TradingBot
     */
    public function resume(TradingBot $bot, ?int $executedByUserId = null, ?int $executedByAdminId = null): TradingBot
    {
        if (!$bot->isPaused()) {
            throw new \Exception('Bot must be paused to resume');
        }

        return DB::transaction(function () use ($bot, $executedByUserId, $executedByAdminId) {
            $oldStatus = $bot->status;
            
            // Update status
            $bot->update([
                'status' => 'running',
                'last_started_at' => now(),
            ]);

            // Log execution
            TradingBotExecutionLog::create([
                'bot_id' => $bot->id,
                'action' => 'resume',
                'executed_at' => now(),
                'executed_by_user_id' => $executedByUserId,
                'executed_by_admin_id' => $executedByAdminId,
                'notes' => 'Bot resumed',
            ]);

            // Fire event
            event(new BotStatusChanged($bot->fresh(), $oldStatus, 'running', $executedByUserId, $executedByAdminId));

            Log::info('Trading bot resumed', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
            ]);

            return $bot->fresh();
        });
    }
}
