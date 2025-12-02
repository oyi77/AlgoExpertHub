<?php

namespace Addons\CopyTrading\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'connection_id',
        'copy_settings',
        'is_active',
        'subscribed_at',
        'unsubscribed_at',
        'stats',
        'preset_id',
    ];

    protected $casts = [
        'copy_mode' => 'string',
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

    public function connection()
    {
        if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            return $this->belongsTo(\App\Models\User::class, 'connection_id'); // Fallback to prevent errors
        }
        return $this->belongsTo(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class, 'connection_id');
    }

    public function executions()
    {
        return $this->hasMany(CopyTradingExecution::class, 'subscription_id');
    }

    /**
     * Get the preset assigned to this subscription.
     */
    public function preset()
    {
        if (!class_exists(\Addons\TradingPresetAddon\App\Models\TradingPreset::class)) {
            return null;
        }
        return $this->belongsTo(\Addons\TradingPresetAddon\App\Models\TradingPreset::class, 'preset_id');
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

    public function scopeEasyMode($query)
    {
        return $query->where('copy_mode', 'easy');
    }

    public function scopeAdvancedMode($query)
    {
        return $query->where('copy_mode', 'advanced');
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isEasyMode(): bool
    {
        return $this->copy_mode === 'easy';
    }

    public function isAdvancedMode(): bool
    {
        return $this->copy_mode === 'advanced';
    }

    public function getCopySettings(): array
    {
        return $this->copy_settings ?? [];
    }

    public function getCopyMethod(): ?string
    {
        return $this->copy_settings['method'] ?? null;
    }

    public function getCopyPercentage(): ?float
    {
        return isset($this->copy_settings['percentage']) ? (float) $this->copy_settings['percentage'] : null;
    }

    public function getFixedQuantity(): ?float
    {
        return isset($this->copy_settings['fixed_quantity']) ? (float) $this->copy_settings['fixed_quantity'] : null;
    }

    public function getMinQuantity(): ?float
    {
        return isset($this->copy_settings['min_quantity']) ? (float) $this->copy_settings['min_quantity'] : null;
    }

    public function getMaxQuantity(): ?float
    {
        return isset($this->copy_settings['max_quantity']) ? (float) $this->copy_settings['max_quantity'] : null;
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
