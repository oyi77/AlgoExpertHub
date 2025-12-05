<?php

namespace Addons\TradingManagement\Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateBacktest extends Model
{
    use HasFactory;

    protected $table = 'template_backtests';

    protected $fillable = [
        'template_type', 'template_id', 'capital_initial', 'capital_final',
        'net_profit_percent', 'win_rate', 'profit_factor', 'max_drawdown',
        'total_trades', 'winning_trades', 'losing_trades', 'avg_win_percent',
        'avg_loss_percent', 'backtest_period_start', 'backtest_period_end',
        'symbols_tested', 'timeframes_tested', 'detailed_results'
    ];

    protected $casts = [
        'capital_initial' => 'decimal:2',
        'capital_final' => 'decimal:2',
        'net_profit_percent' => 'decimal:2',
        'win_rate' => 'decimal:2',
        'profit_factor' => 'decimal:4',
        'max_drawdown' => 'decimal:2',
        'avg_win_percent' => 'decimal:2',
        'avg_loss_percent' => 'decimal:2',
        'backtest_period_start' => 'datetime',
        'backtest_period_end' => 'datetime',
        'symbols_tested' => 'array',
        'timeframes_tested' => 'array',
        'detailed_results' => 'array',
    ];

    public function template()
    {
        switch ($this->template_type) {
            case 'bot':
                return $this->belongsTo(BotTemplate::class, 'template_id');
            case 'signal':
                return $this->belongsTo(SignalSourceTemplate::class, 'template_id');
            case 'complete':
                return $this->belongsTo(CompleteBot::class, 'template_id');
            default:
                return null;
        }
    }

    public function scopeByTemplateType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    public function scopeHighWinRate($query, float $minWinRate = 70)
    {
        return $query->where('win_rate', '>=', $minWinRate);
    }

    public function scopeProfitable($query)
    {
        return $query->where('net_profit_percent', '>', 0);
    }

    public function getEquityCurveData(): array
    {
        if (empty($this->detailed_results)) {
            return [];
        }

        $equity = $this->capital_initial;
        $curve = [['trade' => 0, 'equity' => $equity]];

        foreach ($this->detailed_results as $index => $trade) {
            $equity += $trade['profit'] ?? 0;
            $curve[] = ['trade' => $index + 1, 'equity' => $equity];
        }

        return $curve;
    }
}


