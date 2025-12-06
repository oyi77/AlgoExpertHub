<?php

namespace Addons\CopyTrading\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyTradingExecution extends Model
{
    use HasFactory;

    protected $table = 'copy_trading_executions';

    protected $fillable = [
        'trader_position_id',
        'trader_id',
        'follower_id',
        'subscription_id',
        'follower_position_id',
        'follower_connection_id',
        'copied_at',
        'status',
        'error_message',
        'risk_multiplier_used',
        'original_quantity',
        'copied_quantity',
        'calculation_details',
    ];

    protected $casts = [
        'copied_at' => 'datetime',
        'risk_multiplier_used' => 'decimal:4',
        'original_quantity' => 'decimal:8',
        'copied_quantity' => 'decimal:8',
        'calculation_details' => 'array',
    ];

    public function traderPosition()
    {
        if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class)) {
            return $this->belongsTo(\App\Models\User::class, 'trader_position_id'); // Fallback
        }
        return $this->belongsTo(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class, 'trader_position_id');
    }

    public function followerPosition()
    {
        if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class)) {
            return $this->belongsTo(\App\Models\User::class, 'follower_position_id'); // Fallback
        }
        return $this->belongsTo(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class, 'follower_position_id');
    }

    public function trader()
    {
        return $this->belongsTo(User::class, 'trader_id');
    }

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function subscription()
    {
        return $this->belongsTo(CopyTradingSubscription::class, 'subscription_id');
    }

    public function followerConnection()
    {
        if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            return $this->belongsTo(\App\Models\User::class, 'follower_connection_id'); // Fallback
        }
        return $this->belongsTo(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class, 'follower_connection_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExecuted($query)
    {
        return $query->where('status', 'executed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByTrader($query, int $traderId)
    {
        return $query->where('trader_id', $traderId);
    }

    public function scopeByFollower($query, int $followerId)
    {
        return $query->where('follower_id', $followerId);
    }

    public function scopeByTraderPosition($query, int $traderPositionId)
    {
        return $query->where('trader_position_id', $traderPositionId);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExecuted(): bool
    {
        return $this->status === 'executed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
