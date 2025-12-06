<?php

namespace Addons\TradingPresetAddon\App\Models;

use App\Models\User;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradingPreset extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'trading_presets';

    public $searchable = ['name', 'description', 'symbol'];

    protected $fillable = [
        // Identity & Market
        'name',
        'description',
        'symbol',
        'timeframe',
        'enabled',
        'tags',
        
        // Position & Risk
        'position_size_mode',
        'fixed_lot',
        'risk_per_trade_pct',
        'max_positions',
        'max_positions_per_symbol',
        
        // Dynamic Equity
        'equity_dynamic_mode',
        'equity_base',
        'equity_step_factor',
        'risk_min_pct',
        'risk_max_pct',
        
        // Stop Loss
        'sl_mode',
        'sl_pips',
        'sl_r_multiple',
        
        // Take Profit
        'tp_mode',
        'tp1_enabled',
        'tp1_rr',
        'tp1_close_pct',
        'tp2_enabled',
        'tp2_rr',
        'tp2_close_pct',
        'tp3_enabled',
        'tp3_rr',
        'tp3_close_pct',
        'close_remaining_at_tp3',
        
        // Break Even
        'be_enabled',
        'be_trigger_rr',
        'be_offset_pips',
        
        // Trailing Stop
        'ts_enabled',
        'ts_mode',
        'ts_trigger_rr',
        'ts_step_pips',
        'ts_atr_period',
        'ts_atr_multiplier',
        'ts_update_interval_sec',
        
        // Layering / Grid
        'layering_enabled',
        'max_layers_per_symbol',
        'layer_distance_pips',
        'layer_martingale_mode',
        'layer_martingale_factor',
        'layer_max_total_risk_pct',
        
        // Hedging
        'hedging_enabled',
        'hedge_trigger_drawdown_pct',
        'hedge_distance_pips',
        'hedge_lot_factor',
        
        // Exit Per Candle
        'auto_close_on_candle_close',
        'auto_close_timeframe',
        'hold_max_candles',
        
        // Trading Schedule
        'trading_hours_start',
        'trading_hours_end',
        'trading_timezone',
        'trading_days_mask',
        'session_profile',
        'only_trade_in_session',
        
        // Weekly Target
        'weekly_target_enabled',
        'weekly_target_profit_pct',
        'weekly_reset_day',
        'auto_stop_on_weekly_target',
        
        // Meta
        'created_by_user_id',
        'is_default_template',
        'clonable',
        'visibility',
        
        // Filter Strategy (Sprint 1)
        'filter_strategy_id',
        
        // AI Model Profile (Sprint 2)
        'ai_model_profile_id',
        'ai_confirmation_mode',
        'ai_min_safety_score',
        'ai_position_mgmt_enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'tags' => 'array',
        'fixed_lot' => 'decimal:2',
        'risk_per_trade_pct' => 'decimal:2',
        'equity_base' => 'decimal:2',
        'equity_step_factor' => 'decimal:2',
        'risk_min_pct' => 'decimal:2',
        'risk_max_pct' => 'decimal:2',
        'sl_r_multiple' => 'decimal:2',
        'tp1_rr' => 'decimal:2',
        'tp1_close_pct' => 'decimal:2',
        'tp2_rr' => 'decimal:2',
        'tp2_close_pct' => 'decimal:2',
        'tp3_rr' => 'decimal:2',
        'tp3_close_pct' => 'decimal:2',
        'close_remaining_at_tp3' => 'boolean',
        'be_enabled' => 'boolean',
        'be_trigger_rr' => 'decimal:2',
        'ts_enabled' => 'boolean',
        'ts_trigger_rr' => 'decimal:2',
        'ts_atr_multiplier' => 'decimal:2',
        'ai_min_safety_score' => 'decimal:2',
        'ai_position_mgmt_enabled' => 'boolean',
        'layering_enabled' => 'boolean',
        'layer_martingale_factor' => 'decimal:2',
        'layer_max_total_risk_pct' => 'decimal:2',
        'hedging_enabled' => 'boolean',
        'hedge_trigger_drawdown_pct' => 'decimal:2',
        'hedge_lot_factor' => 'decimal:2',
        'auto_close_on_candle_close' => 'boolean',
        'trading_hours_start' => 'datetime:H:i',
        'trading_hours_end' => 'datetime:H:i',
        'trading_days_mask' => 'integer',
        'only_trade_in_session' => 'boolean',
        'weekly_target_enabled' => 'boolean',
        'weekly_target_profit_pct' => 'decimal:2',
        'auto_stop_on_weekly_target' => 'boolean',
        'is_default_template' => 'boolean',
        'clonable' => 'boolean',
        'tp1_enabled' => 'boolean',
        'tp2_enabled' => 'boolean',
        'tp3_enabled' => 'boolean',
    ];

    /**
     * Get the user who created this preset.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the filter strategy associated with this preset.
     */
    public function filterStrategy()
    {
        if (!class_exists(\Addons\FilterStrategyAddon\App\Models\FilterStrategy::class)) {
            return null;
        }
        return $this->belongsTo(\Addons\FilterStrategyAddon\App\Models\FilterStrategy::class, 'filter_strategy_id');
    }

    /**
     * Get the AI Model Profile for this preset (Sprint 2).
     */
    public function aiModelProfile()
    {
        if (!class_exists(\Addons\AiTradingAddon\App\Models\AiModelProfile::class)) {
            return null; // Fallback
        }
        return $this->belongsTo(\Addons\AiTradingAddon\App\Models\AiModelProfile::class, 'ai_model_profile_id');
    }

    /**
     * Get execution connections using this preset.
     */
    public function executionConnections()
    {
        if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            return $this->hasMany(\App\Models\User::class, 'preset_id'); // Fallback
        }
        return $this->hasMany(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class, 'preset_id');
    }

    /**
     * Get copy trading subscriptions using this preset.
     */
    public function copyTradingSubscriptions()
    {
        if (!class_exists(\Addons\CopyTrading\App\Models\CopyTradingSubscription::class)) {
            return collect(); // Fallback
        }
        return $this->hasMany(\Addons\CopyTrading\App\Models\CopyTradingSubscription::class, 'preset_id');
    }

    /**
     * Get users with this as default preset.
     */
    public function usersWithDefault()
    {
        return $this->hasMany(User::class, 'default_preset_id');
    }

    /**
     * Scope a query to only include enabled presets.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope a query to only include default templates.
     */
    public function scopeDefaultTemplates($query)
    {
        return $query->where('is_default_template', true);
    }

    /**
     * Scope a query to only include public presets.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'PUBLIC_MARKETPLACE');
    }

    /**
     * Scope a query to only include private presets.
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', 'PRIVATE');
    }

    /**
     * Scope a query to only include clonable presets.
     */
    public function scopeClonable($query)
    {
        return $query->where('clonable', true);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('created_by_user_id', $userId);
    }

    /**
     * Scope a query to filter by symbol.
     */
    public function scopeBySymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Scope a query to filter by timeframe.
     */
    public function scopeByTimeframe($query, string $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }

    /**
     * Check if preset is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'PUBLIC_MARKETPLACE';
    }

    /**
     * Check if preset is private.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === 'PRIVATE';
    }

    /**
     * Check if preset is clonable.
     */
    public function isClonable(): bool
    {
        return $this->clonable === true;
    }

    /**
     * Check if preset is a default template.
     */
    public function isDefaultTemplate(): bool
    {
        return $this->is_default_template === true;
    }

    /**
     * Check if user can edit this preset.
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Admins can edit any preset
        if ($user->is_admin ?? false) {
            return true;
        }

        // Users can edit their own presets
        if ($this->created_by_user_id === $user->id) {
            return true;
        }

        // Default templates cannot be edited by non-admins
        if ($this->is_default_template) {
            return false;
        }

        return false;
    }

    /**
     * Check if user can clone this preset.
     */
    public function canBeClonedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // If not clonable, only creator or admin can clone
        if (!$this->clonable) {
            return $this->canBeEditedBy($user);
        }

        // Public presets can be cloned by anyone
        if ($this->isPublic()) {
            return true;
        }

        // Private presets can only be cloned by creator or admin
        return $this->canBeEditedBy($user);
    }

    /**
     * Clone this preset for a user.
     */
    public function cloneFor(User $user, ?string $newName = null): self
    {
        if (!$this->canBeClonedBy($user)) {
            throw new \Exception('Preset cannot be cloned by this user');
        }

        $attributes = $this->getAttributes();
        unset($attributes['id']);
        unset($attributes['created_at']);
        unset($attributes['updated_at']);
        unset($attributes['deleted_at']);

        $attributes['created_by_user_id'] = $user->id;
        $attributes['is_default_template'] = false;
        $attributes['visibility'] = 'PRIVATE';
        
        if ($newName) {
            $attributes['name'] = $newName;
        } else {
            $attributes['name'] = $this->name . ' (Copy)';
        }

        return self::create($attributes);
    }
}

