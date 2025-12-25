<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacktestTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'backtest_id',
        'entry_time',
        'exit_time',
        'entry_price',
        'exit_price',
        'direction',
        'quantity',
        'profit_loss',
        'profit_loss_percent',
        'status',
        'stop_loss',
        'take_profit',
        'notes',
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'entry_price' => 'decimal:8',
        'exit_price' => 'decimal:8',
        'quantity' => 'decimal:8',
        'profit_loss' => 'decimal:8',
        'profit_loss_percent' => 'decimal:4',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
    ];

    public function backtest(): BelongsTo
    {
        return $this->belongsTo(Backtest::class);
    }

    public function scopeWinning($query)
    {
        return $query->where('profit_loss', '>', 0);
    }

    public function scopeLosing($query)
    {
        return $query->where('profit_loss', '<', 0);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}

