<?php

namespace Addons\TradingManagement\Modules\CopyTrading\Models;

use App\Models\User;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CopyTradingSubscription Model
 * 
 * Migrated from copy-trading-addon
 * Links to execution_connections and trading_presets
 */
class CopyTradingSubscription extends Model
{
    use HasFactory;

    protected $table = 'copy_trading_subscriptions';

    protected $fillable = [
        'trader_id',
        'follower_id',
        'copy_mode',
        'risk_multiplier',
        'max_position_size',
        'connection_id', // Database column name
        'copy_settings',
        'is_active',
        'subscribed_at',
        'unsubscribed_at',
        'stats',
        'preset_id',
    ];

    protected $casts = [
        'risk_multiplier' => 'decimal:4',
        'max_position_size' => 'decimal:8',
        'copy_settings' => 'array',
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'stats' => 'array',
    ];

    public function trader()
    {
        return $this->belongsTo(User::class, 'trader_id');
    }

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function executionConnection()
    {
        return $this->belongsTo(ExecutionConnection::class, 'connection_id');
    }

    public function preset()
    {
        return $this->belongsTo(TradingPreset::class, 'preset_id');
    }

    public function executions()
    {
        return $this->hasMany(CopyTradingExecution::class, 'subscription_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTrader($query, int $traderId)
    {
        return $query->where('trader_id', $traderId);
    }

    public function scopeByFollower($query, int $followerId)
    {
        return $query->where('follower_id', $followerId);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function activate(): void
    {
        $this->forceFill([
            'is_active' => true,
            'subscribed_at' => $this->subscribed_at ?? now(),
            'unsubscribed_at' => null,
        ])->save();
    }

    public function deactivate(): void
    {
        $this->forceFill([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ])->save();
    }
}

