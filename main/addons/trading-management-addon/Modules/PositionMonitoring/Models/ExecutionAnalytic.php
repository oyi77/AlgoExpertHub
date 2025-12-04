<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;

/**
 * ExecutionAnalytic Model
 * 
 * Stores aggregated analytics for execution connections
 */
class ExecutionAnalytic extends Model
{
    use HasFactory;

    protected $table = 'execution_analytics';

    protected $fillable = [
        'execution_connection_id',
        'date',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'total_pnl',
        'total_profit',
        'total_loss',
        'avg_win',
        'avg_loss',
        'profit_factor',
        'max_drawdown',
        'recovery_factor',
    ];

    protected $casts = [
        'date' => 'date',
        'win_rate' => 'decimal:2',
        'total_pnl' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'total_loss' => 'decimal:2',
        'avg_win' => 'decimal:2',
        'avg_loss' => 'decimal:2',
        'profit_factor' => 'decimal:2',
        'max_drawdown' => 'decimal:2',
        'recovery_factor' => 'decimal:2',
    ];

    /**
     * Belongs to execution connection
     */
    public function connection()
    {
        return $this->belongsTo(ExecutionConnection::class, 'execution_connection_id');
    }
}

