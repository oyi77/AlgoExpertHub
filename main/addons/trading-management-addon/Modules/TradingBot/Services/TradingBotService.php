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
use Addons\TradingManagement\Shared\Helpers\CredentialRedactionHelper;
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
            // Redact any credentials from data before logging
            $logData = CredentialRedactionHelper::redact($data);
            
            $bot = TradingBot::create($data);
            
            // Log creation with redacted data
            \Log::info('Trading bot created', CredentialRedactionHelper::redactLogContext([
                'bot_id' => $bot->id,
                'name' => $bot->name,
                'user_id' => $bot->user_id,
                'admin_id' => $bot->admin_id,
                'exchange_connection_id' => $bot->exchange_connection_id,
                'trading_mode' => $bot->trading_mode,
            ]));

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
     * Validate relationships exist and are compatible
     * 
     * Validates all relationships required for bot creation/update:
     * - Exchange connection (type, status, active)
     * - Trading preset (enabled, configured)
     * - Filter strategy (optional)
     * - AI model profile (optional)
     * - Expert advisor (optional)
     * 
     * @param array $data Bot data array containing relationship IDs
     * @param TradingBot|null $bot Optional existing bot for update operations
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If relationship not found
     * @throws \Exception If validation fails (inactive connection, disabled preset, etc.)
     */
    protected function validateRelationships(array $data, ?TradingBot $bot = null): void
    {
        // Validate exchange connection
        if (isset($data['exchange_connection_id'])) {
            $connectionId = $data['exchange_connection_id'];
        } else {
            $connectionId = $bot?->exchange_connection_id;
        }

        $connection = null;
        if ($connectionId) {
            $connection = ExchangeConnection::findOrFail($connectionId);
            
            // Validate connection ownership (for users)
            if (!Auth::guard('admin')->check()) {
                $userId = Auth::id();
                if ($connection->is_admin_owned || $connection->user_id !== $userId) {
                    throw new \Exception('The selected exchange connection does not belong to you. Please select your own connection or create a new one.');
                }
            }
            
            // Validate connection is active
            if (!$connection->is_active) {
                throw new \Exception('The selected exchange connection is not active. Please activate the connection first or select an active connection.');
            }
            
            // Validate connection status
            if ($connection->status !== 'active') {
                throw new \Exception('The selected exchange connection is not ready. Please ensure the connection is tested and active. Current status: ' . $connection->status);
            }
            
            // For crypto exchanges, validate credentials are present and valid
            if ($connection->connection_type === 'CRYPTO_EXCHANGE') {
                $credentials = $connection->credentials;
                $exchangeName = strtolower($connection->exchange_name ?? $connection->provider ?? '');
                
                // Check for required credentials
                if (empty($credentials['api_key']) || empty($credentials['api_secret'])) {
                    throw new \Exception('The selected exchange connection is missing required API credentials. Please update the connection with valid API key and secret.');
                }
                
                // Check for passphrase requirement
                $requiresPassphrase = in_array($exchangeName, ['okx', 'kucoin', 'coinbasepro', 'coinbase']);
                if ($requiresPassphrase && empty($credentials['api_passphrase'])) {
                    throw new \Exception('The selected exchange connection requires an API passphrase. Please update the connection with a valid passphrase for ' . strtoupper($exchangeName) . '.');
                }
            }
            
            // Validate connection type compatibility
            $this->validateConnectionType($connection, $bot);
        }

        // Validate trading preset
        if (isset($data['trading_preset_id'])) {
            $presetId = $data['trading_preset_id'];
        } else {
            $presetId = $bot?->trading_preset_id;
        }

        $preset = null;
        if ($presetId) {
            $preset = TradingPreset::findOrFail($presetId);
            
            // Validate preset is enabled
            $this->validatePreset($preset, $bot);
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
     * Validate connection type compatibility with bot
     * 
     * @param ExchangeConnection $connection
     * @param TradingBot|null $bot
     * @return void
     * @throws \Exception If connection type is incompatible
     */
    protected function validateConnectionType(ExchangeConnection $connection, ?TradingBot $bot = null): void
    {
        // If bot exists and has a connection type requirement, validate it
        if ($bot && method_exists($bot, 'getConnectionType')) {
            $botType = $bot->getConnectionType();
            $connectionType = $connection->connection_type === 'CRYPTO_EXCHANGE' ? 'crypto' : 'fx';
            
            if ($botType && $botType !== $connectionType) {
                throw new \Exception("Connection type mismatch. Bot requires {$botType} connection but selected connection is {$connectionType}.");
            }
        }
        
        // Additional validation: ensure connection can execute trades
        if (!$connection->canExecuteTrades()) {
            throw new \Exception('The selected exchange connection does not have trade execution enabled. Please select a connection with execution enabled.');
        }
    }

    /**
     * Validate trading preset is enabled and accessible
     * 
     * @param TradingPreset $preset
     * @param TradingBot|null $bot
     * @return void
     * @throws \Exception If preset is not enabled or accessible
     */
    protected function validatePreset(TradingPreset $preset, ?TradingBot $bot = null): void
    {
        if (!$preset->enabled) {
            throw new \Exception('The selected trading preset is not enabled. Please select an enabled preset.');
        }
        
        // Validate preset visibility for users
        if (!Auth::guard('admin')->check()) {
            $userId = Auth::id();
            $isOwnPreset = $preset->created_by_user_id === $userId;
            $isPublic = $preset->visibility === 'PUBLIC_MARKETPLACE';
            
            if (!$isOwnPreset && !$isPublic) {
                throw new \Exception('The selected trading preset is not accessible. Please select your own preset or a public preset.');
            }
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
            $userId = Auth::id();
            if (!$userId) {
                Log::warning('getAvailableConnections: No authenticated user ID');
                return collect([]);
            }
            
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('is_admin_owned', false);
            });
            // Don't require admin_id to be null - user_id is the primary identifier for user-owned connections
            
            Log::debug('getAvailableConnections: Filtering for user', [
                'user_id' => $userId,
                'query_sql' => $query->toSql(),
                'query_bindings' => $query->getBindings(),
            ]);
        }

        $connections = $query->orderBy('name')->get();
        
        Log::debug('getAvailableConnections: Result', [
            'count' => $connections->count(),
            'connection_ids' => $connections->pluck('id')->toArray(),
            'connection_names' => $connections->pluck('name')->toArray(),
            'is_admin' => Auth::guard('admin')->check(),
            'user_id' => Auth::id(),
        ]);

        return $connections;
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
            $userId = Auth::id();
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('is_admin_owned', false);
            });
            // Don't require admin_id to be null - user_id is the primary identifier
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
            // Try to auto-stabilize by testing and activating the connection
            $stabilizeResult = $connectionService->stabilize($bot->exchangeConnection, true); // autoActivate = true
            
            if (!$stabilizeResult['success']) {
                return ['valid' => false, 'message' => 'Exchange connection must be stabilized before starting bot. Connection test failed: ' . ($stabilizeResult['message'] ?? 'Unknown error') . '. Please test the connection manually first.'];
            }
            
            // Refresh connection to get updated last_tested_at and is_active
            $bot->exchangeConnection->refresh();
            
            // Check again after stabilization attempt
            if (!$connectionService->isStabilized($bot->exchangeConnection)) {
                return ['valid' => false, 'message' => 'Exchange connection test completed but connection is still not stabilized. Please ensure the connection is active and tested successfully.'];
            }
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
            event(new BotStatusChanged($bot->id, $executedByUserId, 'running', "Bot started from {$oldStatus}"));

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
     * Stops the bot with race condition protection and idempotency checks.
     * Uses row-level locking to prevent concurrent stop/start operations.
     * 
     * @param TradingBot $bot The bot to stop
     * @param int|null $executedByUserId User ID executing the action (for logging)
     * @param int|null $executedByAdminId Admin ID executing the action (for logging)
     * @return TradingBot Updated bot instance
     */
    public function stop(TradingBot $bot, ?int $executedByUserId = null, ?int $executedByAdminId = null): TradingBot
    {
        // Idempotency check: If bot is already stopped, return it
        if ($bot->isStopped()) {
            Log::info('Bot already stopped, skipping stop', [
                'bot_id' => $bot->id,
                'status' => $bot->status,
            ]);
            return $bot;
        }

        return DB::transaction(function () use ($bot, $executedByUserId, $executedByAdminId) {
            // Use lockForUpdate to prevent race conditions (concurrent stop/start operations)
            $bot = TradingBot::lockForUpdate()->findOrFail($bot->id);
            
            // Double-check status after acquiring lock (idempotency)
            if ($bot->isStopped()) {
                Log::info('Bot already stopped after lock, skipping stop', [
                    'bot_id' => $bot->id,
                    'status' => $bot->status,
                ]);
                return $bot;
            }

            // Validate status transition
            if (!$bot->canTransitionTo('stopped')) {
                throw new \Exception("Invalid status transition from '{$bot->status}' to 'stopped'.");
            }

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
            event(new BotStatusChanged($bot->id, $executedByUserId, 'stopped', "Bot stopped from {$oldStatus}"));

            Log::info('Trading bot stopped', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
                'user_id' => $executedByUserId,
                'admin_id' => $executedByAdminId,
                'status' => $bot->status,
                'old_status' => $oldStatus,
            ]);

            return $bot->fresh();
        });
    }

    /**
     * Pause trading bot
     * 
     * Pauses a running bot with race condition protection and status transition validation.
     * Bot must be in 'running' status to be paused.
     * 
     * @param TradingBot $bot The bot to pause
     * @param int|null $executedByUserId User ID executing the action (for logging)
     * @param int|null $executedByAdminId Admin ID executing the action (for logging)
     * @return TradingBot Updated bot instance
     * @throws \Exception If bot is not in a valid state to be paused
     */
    public function pause(TradingBot $bot, ?int $executedByUserId = null, ?int $executedByAdminId = null): TradingBot
    {
        // Idempotency check: If bot is already paused, return it
        if ($bot->isPaused()) {
            Log::info('Bot already paused, skipping pause', [
                'bot_id' => $bot->id,
                'status' => $bot->status,
            ]);
            return $bot;
        }

        return DB::transaction(function () use ($bot, $executedByUserId, $executedByAdminId) {
            // Use lockForUpdate to prevent race conditions (concurrent pause/resume operations)
            $bot = TradingBot::lockForUpdate()->findOrFail($bot->id);
            
            // Double-check status after acquiring lock (idempotency)
            if ($bot->isPaused()) {
                Log::info('Bot already paused after lock, skipping pause', [
                    'bot_id' => $bot->id,
                    'status' => $bot->status,
                ]);
                return $bot;
            }

            // Validate status transition
            if (!$bot->canTransitionTo('paused')) {
                throw new \Exception("Invalid status transition from '{$bot->status}' to 'paused'. Bot must be running to pause.");
            }

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
            event(new BotStatusChanged($bot->id, $executedByUserId, 'paused', "Bot paused from {$oldStatus}"));

            Log::info('Trading bot paused', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
                'user_id' => $executedByUserId,
                'admin_id' => $executedByAdminId,
                'status' => $bot->status,
                'old_status' => $oldStatus,
            ]);

            return $bot->fresh();
        });
    }

    /**
     * Restart trading bot (stop then start)
     * 
     * @param TradingBot $bot
     * @param int|null $executedByUserId
     * @param int|null $executedByAdminId
     * @return TradingBot
     */
    public function restart(TradingBot $bot, ?int $executedByUserId = null, ?int $executedByAdminId = null): TradingBot
    {
        // Stop first
        if ($bot->isRunning() || $bot->isPaused()) {
            $this->stop($bot, $executedByUserId, $executedByAdminId);
            
            // Wait a moment for worker to stop gracefully
            sleep(2);
        }
        
        // Then start
        return $this->start($bot, $executedByUserId, $executedByAdminId);
    }

    /**
     * Resume paused trading bot
     * 
     * Resumes a paused bot with race condition protection and status transition validation.
     * Bot must be in 'paused' status to be resumed.
     * 
     * @param TradingBot $bot The bot to resume
     * @param int|null $executedByUserId User ID executing the action (for logging)
     * @param int|null $executedByAdminId Admin ID executing the action (for logging)
     * @return TradingBot Updated bot instance
     * @throws \Exception If bot is not in a valid state to be resumed
     */
    public function resume(TradingBot $bot, ?int $executedByUserId = null, ?int $executedByAdminId = null): TradingBot
    {
        // Idempotency check: If bot is already running, return it
        if ($bot->isRunning()) {
            Log::info('Bot already running, skipping resume', [
                'bot_id' => $bot->id,
                'status' => $bot->status,
            ]);
            return $bot;
        }

        return DB::transaction(function () use ($bot, $executedByUserId, $executedByAdminId) {
            // Use lockForUpdate to prevent race conditions (concurrent resume/pause operations)
            $bot = TradingBot::lockForUpdate()->findOrFail($bot->id);
            
            // Double-check status after acquiring lock (idempotency)
            if ($bot->isRunning()) {
                Log::info('Bot already running after lock, skipping resume', [
                    'bot_id' => $bot->id,
                    'status' => $bot->status,
                ]);
                return $bot;
            }

            // Validate status transition
            if (!$bot->canTransitionTo('running')) {
                throw new \Exception("Invalid status transition from '{$bot->status}' to 'running'. Bot must be paused to resume.");
            }

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
            event(new BotStatusChanged($bot->id, $executedByUserId, 'running', "Bot resumed from {$oldStatus}"));

            Log::info('Trading bot resumed', [
                'bot_id' => $bot->id,
                'name' => $bot->name,
            ]);

            return $bot->fresh();
        });
    }

    /**
     * Validate bot configuration is complete
     * 
     * @param TradingBot $bot
     * @return array ['valid' => bool, 'message' => string, 'errors' => array]
     */
    public function validateConfiguration(TradingBot $bot): array
    {
        $errors = [];

        // Check exchange connection
        if (!$bot->exchange_connection_id) {
            $errors[] = 'Exchange connection is required';
        } elseif (!$bot->exchangeConnection) {
            $errors[] = 'Exchange connection not found';
        } elseif (!$bot->exchangeConnection->is_active) {
            $errors[] = 'Exchange connection is not active';
        }

        // Check trading preset
        if (!$bot->trading_preset_id) {
            $errors[] = 'Trading preset is required';
        } elseif (!$bot->tradingPreset) {
            $errors[] = 'Trading preset not found';
        }

        // Check data connection for MARKET_STREAM_BASED mode
        if ($bot->trading_mode === 'MARKET_STREAM_BASED') {
            if (!$bot->data_connection_id) {
                $errors[] = 'Data connection is required for market stream based trading';
            } elseif (!$bot->dataConnection) {
                $errors[] = 'Data connection not found';
            } elseif (!$bot->dataConnection->is_active) {
                $errors[] = 'Data connection is not active';
            }

            // Check streaming symbols
            if (empty($bot->streaming_symbols)) {
                $errors[] = 'Streaming symbols are required for market stream based trading';
            }

            // Check streaming timeframes
            if (empty($bot->streaming_timeframes)) {
                $errors[] = 'Streaming timeframes are required for market stream based trading';
            }
        }

        // Check filter strategy (optional but recommended)
        if ($bot->filter_strategy_id && !$bot->filterStrategy) {
            $errors[] = 'Filter strategy not found';
        }

        // Check AI model profile (optional)
        if ($bot->ai_model_profile_id && !$bot->aiModelProfile) {
            $errors[] = 'AI model profile not found';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Configuration is valid' : implode(', ', $errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get bot analysis data
     * 
     * @param TradingBot $bot
     * @param array $filters ['date_from' => string, 'date_to' => string, 'period' => string]
     * @return array
     */
    public function getAnalysis(TradingBot $bot, array $filters = []): array
    {
        // Delegate to BotAnalysisService if available
        if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Services\BotAnalysisService::class)) {
            $analysisService = app(\Addons\TradingManagement\Modules\TradingBot\Services\BotAnalysisService::class);
            return $analysisService->calculateMetrics($bot, $filters);
        }

        // Fallback: Basic analysis from bot statistics
        $positions = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition::forBot($bot->id)->get();
        $closedPositions = $positions->where('status', 'closed');
        $openPositions = $positions->where('status', 'open');

        $totalTrades = $closedPositions->count();
        $winningTrades = $closedPositions->where('profit_loss', '>', 0)->count();
        $losingTrades = $closedPositions->where('profit_loss', '<', 0)->count();
        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

        $totalProfit = $closedPositions->sum('profit_loss');
        $totalLoss = abs($closedPositions->where('profit_loss', '<', 0)->sum('profit_loss'));
        $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : ($totalProfit > 0 ? 999 : 0);

        return [
            'metrics' => [
                'total_trades' => $totalTrades,
                'winning_trades' => $winningTrades,
                'losing_trades' => $losingTrades,
                'win_rate' => round($winRate, 2),
                'total_profit' => round($totalProfit, 2),
                'profit_factor' => round($profitFactor, 2),
            ],
            'positions' => [
                'open' => $openPositions->count(),
                'closed' => $closedPositions->count(),
            ],
        ];
    }

    /**
     * Get execution history for bot
     * 
     * @param TradingBot $bot
     * @param array $filters ['date_from' => string, 'date_to' => string, 'status' => string, 'per_page' => int]
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getExecutions(TradingBot $bot, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Get execution logs from ExecutionLog table via exchange connection
        $query = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::query()
            ->where('connection_id', $bot->exchange_connection_id)
            ->with(['signal', 'executionConnection']);

        // Apply date filters
        if (isset($filters['date_from'])) {
            $query->where('executed_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('executed_at', '<=', $filters['date_to']);
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply symbol filter
        if (isset($filters['symbol'])) {
            $query->where('symbol', $filters['symbol']);
        }

        // Order by executed_at descending
        $query->orderBy('executed_at', 'desc');

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }
}
