<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotExecutionLog;
use Addons\TradingManagement\Modules\TradingBot\Events\BotStatusChanged;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $query->where(function ($q) {
            $q->whereNotNull('user_id')
              ->where('is_default_template', false);
        });

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
            $query->where('user_id', Auth::id());
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

        if (!$bot->tradingPreset) {
            return ['valid' => false, 'message' => 'Trading preset is required'];
        }

        if ($bot->requiresDataConnection()) {
            if (!$bot->dataConnection) {
                return ['valid' => false, 'message' => 'Data connection is required for MARKET_STREAM_BASED mode'];
            }

            if (empty($bot->getStreamingSymbols())) {
                return ['valid' => false, 'message' => 'Streaming symbols are required for MARKET_STREAM_BASED mode'];
            }

            if (empty($bot->getStreamingTimeframes())) {
                return ['valid' => false, 'message' => 'Streaming timeframes are required for MARKET_STREAM_BASED mode'];
            }

            // Validate data connection type matches exchange connection type
            $exchangeType = $bot->getConnectionType();
            $dataType = $bot->dataConnection->connection_type === 'CRYPTO_EXCHANGE' ? 'crypto' : 'fx';
            
            if ($exchangeType !== $dataType) {
                return ['valid' => false, 'message' => 'Data connection type must match exchange connection type'];
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
