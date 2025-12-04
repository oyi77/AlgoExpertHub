<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Models;

use App\Models\User;
use App\Traits\Searchable;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TradingPreset Model
 * 
 * Migrated from trading-preset-addon to trading-management-addon
 * NEW: Integrated with Smart Risk (AI adaptive risk)
 * 
 * Comprehensive risk management preset with:
 * - Position sizing (fixed or risk percentage)
 * - SL/TP configuration (multi-TP support)
 * - Break-even and trailing stop
 * - Layering/Grid support
 * - Hedging support
 * - Filter strategy integration
 * - AI model integration
 * - Smart Risk integration (NEW)
 */
class TradingPreset extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'trading_presets';

    public $searchable = ['name', 'description', 'symbol'];

    protected $fillable = [
        'name', 'description', 'symbol', 'timeframe', 'enabled', 'tags',
        'position_size_mode', 'fixed_lot', 'risk_per_trade_pct', 'max_positions', 'max_positions_per_symbol',
        'equity_dynamic_mode', 'equity_base', 'equity_step_factor', 'risk_min_pct', 'risk_max_pct',
        'sl_mode', 'sl_pips', 'sl_r_multiple',
        'tp_mode', 'tp1_enabled', 'tp1_rr', 'tp1_close_pct',
        'tp2_enabled', 'tp2_rr', 'tp2_close_pct',
        'tp3_enabled', 'tp3_rr', 'tp3_close_pct', 'close_remaining_at_tp3',
        'be_enabled', 'be_trigger_rr', 'be_offset_pips',
        'ts_enabled', 'ts_mode', 'ts_trigger_rr', 'ts_step_pips', 'ts_atr_period', 'ts_atr_multiplier', 'ts_update_interval_sec',
        'layering_enabled', 'max_layers_per_symbol', 'layer_distance_pips', 'layer_martingale_mode', 'layer_martingale_factor', 'layer_max_total_risk_pct',
        'hedging_enabled', 'hedge_trigger_drawdown_pct', 'hedge_distance_pips', 'hedge_lot_factor',
        'auto_close_on_candle_close', 'auto_close_timeframe', 'hold_max_candles',
        'trading_hours_start', 'trading_hours_end', 'trading_timezone', 'trading_days_mask', 'session_profile', 'only_trade_in_session',
        'weekly_target_enabled', 'weekly_target_profit_pct', 'weekly_reset_day', 'auto_stop_on_weekly_target',
        'created_by_user_id', 'is_default_template', 'clonable', 'visibility',
        'filter_strategy_id', 'ai_model_profile_id', 'ai_confirmation_mode', 'ai_min_safety_score', 'ai_position_mgmt_enabled',
        'smart_risk_enabled', 'smart_risk_min_score', 'smart_risk_slippage_buffer', 'smart_risk_dynamic_lot',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'tags' => 'array',
        'fixed_lot' => 'decimal:2',
        'risk_per_trade_pct' => 'decimal:2',
        // ... (all decimal casts from original)
        'is_default_template' => 'boolean',
        'clonable' => 'boolean',
        'smart_risk_enabled' => 'boolean', // NEW
        'smart_risk_slippage_buffer' => 'boolean', // NEW
        'smart_risk_dynamic_lot' => 'boolean', // NEW
    ];

    /**
     * Relationships
     */
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function filterStrategy()
    {
        return $this->belongsTo(FilterStrategy::class, 'filter_strategy_id');
    }

    public function aiModelProfile()
    {
        return $this->belongsTo(AiModelProfile::class, 'ai_model_profile_id');
    }

    public function usersWithDefault()
    {
        return $this->hasMany(User::class, 'default_preset_id');
    }

    /**
     * Scopes
     */
    
    public function scopeEnabled($query) { return $query->where('enabled', true); }
    public function scopeDefaultTemplates($query) { return $query->where('is_default_template', true); }
    public function scopePublic($query) { return $query->where('visibility', 'PUBLIC_MARKETPLACE'); }
    public function scopePrivate($query) { return $query->where('visibility', 'PRIVATE'); }
    public function scopeClonable($query) { return $query->where('clonable', true); }
    public function scopeByUser($query, int $userId) { return $query->where('created_by_user_id', $userId); }

    /**
     * Helper Methods
     */
    
    public function isPublic(): bool { return $this->visibility === 'PUBLIC_MARKETPLACE'; }
    public function isClonable(): bool { return $this->clonable === true; }
    public function isDefaultTemplate(): bool { return $this->is_default_template === true; }
    
    public function hasSmartRisk(): bool { return $this->smart_risk_enabled === true; } // NEW
    public function hasFilterStrategy(): bool { return !is_null($this->filter_strategy_id); }
    public function hasAiConfirmation(): bool { return $this->ai_confirmation_mode !== 'NONE'; }

    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) return false;
        if ($user->is_admin ?? false) return true;
        if ($this->created_by_user_id === $user->id) return true;
        if ($this->is_default_template) return false;
        return false;
    }

    public function cloneFor(User $user, ?string $newName = null): self
    {
        $attributes = $this->getAttributes();
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);
        
        $attributes['created_by_user_id'] = $user->id;
        $attributes['is_default_template'] = false;
        $attributes['visibility'] = 'PRIVATE';
        $attributes['name'] = $newName ?? ($this->name . ' (Copy)');

        return self::create($attributes);
    }
}

