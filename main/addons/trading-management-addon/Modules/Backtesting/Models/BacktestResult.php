<?php

namespace Addons\TradingManagement\Modules\Backtesting\Models;

use Illuminate\Database\Eloquent\Model;

class BacktestResult extends Model
{
    protected $table = 'backtest_results';

    protected $fillable = [
        'backtest_id',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'total_profit',
        'total_loss',
        'net_profit',
        'final_balance',
        'return_percent',
        'profit_factor',
        'sharpe_ratio',
        'max_drawdown',
        'max_drawdown_percent',
        'avg_win',
        'avg_loss',
        'largest_win',
        'largest_loss',
        'consecutive_wins',
        'consecutive_losses',
        'equity_curve',
        'trade_details',
    ];

    protected $casts = [
        'total_trades' => 'integer',
        'winning_trades' => 'integer',
        'losing_trades' => 'integer',
        'win_rate' => 'decimal:2',
        'total_profit' => 'decimal:8',
        'total_loss' => 'decimal:8',
        'net_profit' => 'decimal:8',
        'final_balance' => 'decimal:8',
        'return_percent' => 'decimal:4',
        'profit_factor' => 'decimal:4',
        'sharpe_ratio' => 'decimal:4',
        'max_drawdown' => 'decimal:8',
        'max_drawdown_percent' => 'decimal:4',
        'avg_win' => 'decimal:8',
        'avg_loss' => 'decimal:8',
        'largest_win' => 'decimal:8',
        'largest_loss' => 'decimal:8',
        'equity_curve' => 'array',
        'trade_details' => 'array',
    ];

    public function backtest()
    {
        return $this->belongsTo(Backtest::class);
    }

    public function isProfitable(): bool
    {
        return $this->net_profit > 0;
    }

    public function isGoodWinRate(): bool
    {
        return $this->win_rate >= 50;
    }

    public function isGoodProfitFactor(): bool
    {
        return $this->profit_factor >= 1.5;
    }

    public function getGradeAttribute(): string
    {
        $score = 0;
        
        if ($this->win_rate >= 60) $score += 30;
        elseif ($this->win_rate >= 50) $score += 20;
        elseif ($this->win_rate >= 40) $score += 10;
        
        if ($this->profit_factor >= 2.0) $score += 30;
        elseif ($this->profit_factor >= 1.5) $score += 20;
        elseif ($this->profit_factor >= 1.0) $score += 10;
        
        if ($this->max_drawdown_percent <= 10) $score += 20;
        elseif ($this->max_drawdown_percent <= 20) $score += 10;
        
        if ($this->return_percent >= 20) $score += 20;
        elseif ($this->return_percent >= 10) $score += 10;
        
        return match(true) {
            $score >= 80 => 'A (Excellent)',
            $score >= 60 => 'B (Good)',
            $score >= 40 => 'C (Average)',
            $score >= 20 => 'D (Poor)',
            default => 'F (Failed)',
        };
    }
}

