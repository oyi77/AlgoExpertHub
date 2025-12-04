<?php

namespace Addons\TradingManagement\Modules\TradingBot\Models;

use App\Models\User;
use App\Models\Admin;
use App\Traits\Searchable;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TradingBot Model
 * 
 * Coinrule-like trading bot builder
 * Combines: Exchange Connection + Trading Preset + Filter Strategy + AI Profile
 */
class TradingBot extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'trading_bots';

    public $searchable = ['name', 'description'];

    protected $fillable = [
        'user_id', 'admin_id',
        'name', 'description',
        'exchange_connection_id', 'trading_preset_id', 'filter_strategy_id', 'ai_model_profile_id',
        'is_active', 'is_paper_trading',
        'total_executions', 'successful_executions', 'failed_executions',
        'total_profit', 'win_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_paper_trading' => 'boolean',
        'total_executions' => 'integer',
        'successful_executions' => 'integer',
        'failed_executions' => 'integer',
        'total_profit' => 'decimal:2',
        'win_rate' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function exchangeConnection()
    {
        return $this->belongsTo(ExchangeConnection::class, 'exchange_connection_id');
    }

    public function tradingPreset()
    {
        return $this->belongsTo(TradingPreset::class, 'trading_preset_id');
    }

    public function filterStrategy()
    {
        return $this->belongsTo(FilterStrategy::class, 'filter_strategy_id');
    }

    public function aiModelProfile()
    {
        return $this->belongsTo(AiModelProfile::class, 'ai_model_profile_id');
    }

    /**
     * Scopes
     */
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePaperTrading($query)
    {
        return $query->where('is_paper_trading', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Helper Methods
     */
    
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isPaperTrading(): bool
    {
        return $this->is_paper_trading;
    }

    public function getOwnerAttribute()
    {
        return $this->user_id ? $this->user : $this->admin;
    }

    /**
     * Update statistics
     */
    public function updateStatistics()
    {
        // Calculate win rate from executions
        // This would be called after executions complete
        // For now, placeholder - will be implemented with execution logs
    }
}
