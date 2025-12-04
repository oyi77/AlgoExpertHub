<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     * Get bots for current user/admin
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
}
