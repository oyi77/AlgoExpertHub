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
        'connection_id', // Database column name
        'user_id',
        'admin_id',
        'date',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'total_pnl',
        'profit_factor',
        'max_drawdown',
        'balance',
        'equity',
        'additional_metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'total_pnl' => 'decimal:8',
        'win_rate' => 'decimal:2',
        'profit_factor' => 'decimal:4',
        'max_drawdown' => 'decimal:4',
        'balance' => 'decimal:8',
        'equity' => 'decimal:8',
        'additional_metrics' => 'array',
    ];

    /**
     * Belongs to execution connection
     */
    public function connection()
    {
        return $this->belongsTo(ExecutionConnection::class, 'connection_id');
    }
}

