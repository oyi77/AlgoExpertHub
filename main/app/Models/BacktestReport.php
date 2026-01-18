<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacktestReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'backtest_id',
        'name',
        'description',
        'period',
        'start_date',
        'end_date',
        'total_backtests',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'total_profit',
        'total_loss',
        'total_return',
        'avg_win_rate',
        'avg_loss',
        'profit_factor',
        'max_drawdown',
        'max_profit',
        'max_loss',
        'avg_win',
        'avg_loss',
        'best_win_streak',
        'worst_loss_streak',
        'data',
        'generated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_profit' => 'decimal:8',
        'total_loss' => 'decimal:8',
        'total_return' => 'decimal:4',
        'avg_win_rate' => 'decimal:2',
        'avg_loss' => 'decimal:2',
        'profit_factor' => 'decimal:4',
        'max_drawdown' => 'decimal:4',
        'max_profit' => 'decimal:8',
        'max_loss' => 'decimal:8',
        'avg_win' => 'decimal:2',
        'avg_loss' => 'decimal:2',
        'generated_at' => 'datetime',
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function backtest(): BelongsTo
    {
        return $this->belongsTo(Backtest::class);
    }
}
