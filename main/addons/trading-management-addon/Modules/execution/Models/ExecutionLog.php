<?php

namespace Addons\TradingManagement\Modules\Execution\Models;

use App\Models\Signal;
use Illuminate\Database\Eloquent\Model;

class ExecutionLog extends Model
{
    protected $table = 'execution_logs';

    protected $fillable = [
        'execution_connection_id',
        'signal_id',
        'order_id',
        'symbol',
        'side',
        'lot_size',
        'entry_price',
        'stop_loss',
        'take_profit',
        'status',
        'error_message',
        'order_data',
    ];

    protected $casts = [
        'lot_size' => 'decimal:2',
        'entry_price' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'order_data' => 'array',
    ];

    public function executionConnection()
    {
        return $this->belongsTo(ExecutionConnection::class);
    }

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function position()
    {
        return $this->hasOne(\Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class);
    }

    public function scopeByConnection($query, int $connectionId)
    {
        return $query->where('execution_connection_id', $connectionId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}

