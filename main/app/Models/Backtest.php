<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Backtest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'symbol',
        'timeframe',
        'start_date',
        'end_date',
        'initial_balance',
        'final_balance',
        'total_return',
        'win_rate',
        'max_drawdown',
        'profit_factor',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'average_win',
        'average_loss',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'slippage_model',
        'slippage_pips',
        'spread_cost_enabled',
        'partial_fills_enabled',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'initial_balance' => 'decimal:8',
        'final_balance' => 'decimal:8',
        'total_return' => 'decimal:4',
        'win_rate' => 'decimal:2',
        'max_drawdown' => 'decimal:4',
        'profit_factor' => 'decimal:4',
        'average_win' => 'decimal:8',
        'average_loss' => 'decimal:8',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'slippage_pips' => 'decimal:4',
        'spread_cost_enabled' => 'boolean',
        'partial_fills_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(BacktestTrade::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

