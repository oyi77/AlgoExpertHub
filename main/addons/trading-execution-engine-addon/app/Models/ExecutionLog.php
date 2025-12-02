<?php

namespace Addons\TradingExecutionEngine\App\Models;

use App\Models\Signal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionLog extends Model
{
    use HasFactory;

    protected $table = 'execution_logs';

    protected $fillable = [
        'signal_id',
        'connection_id',
        'execution_type',
        'order_id',
        'symbol',
        'direction',
        'quantity',
        'entry_price',
        'sl_price',
        'tp_price',
        'status',
        'executed_at',
        'error_message',
        'response_data',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'sl_price' => 'decimal:8',
        'tp_price' => 'decimal:8',
        'executed_at' => 'datetime',
        'response_data' => 'array',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function connection()
    {
        return $this->belongsTo(ExecutionConnection::class);
    }

    public function position()
    {
        return $this->hasOne(ExecutionPosition::class, 'execution_log_id');
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

    public function isExecuted(): bool
    {
        return $this->status === 'executed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}

