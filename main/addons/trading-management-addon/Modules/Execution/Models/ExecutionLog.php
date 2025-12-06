<?php

namespace Addons\TradingManagement\Modules\Execution\Models;

use App\Models\Signal;
use Illuminate\Database\Eloquent\Model;

class ExecutionLog extends Model
{
    protected $table = 'execution_logs';

    protected $fillable = [
        'connection_id', // Database column name
        'signal_id',
        'order_id',
        'symbol',
        'direction', // Database column name (not 'side')
        'quantity', // Database column name (not 'lot_size')
        'entry_price',
        'sl_price', // Database column name (not 'stop_loss')
        'tp_price', // Database column name (not 'take_profit')
        'status',
        'error_message',
        'response_data', // Database column name (not 'order_data')
        'execution_type',
        'executed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'sl_price' => 'decimal:8',
        'tp_price' => 'decimal:8',
        'response_data' => 'array',
        'executed_at' => 'datetime',
    ];

    public function executionConnection()
    {
        return $this->belongsTo(ExecutionConnection::class, 'connection_id');
    }

    /**
     * Alias for executionConnection() for compatibility with views
     */
    public function connection()
    {
        return $this->executionConnection();
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
        return $query->where('connection_id', $connectionId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get side attribute (alias for direction) - for compatibility
     * Database uses 'direction', but code may expect 'side'
     */
    public function getSideAttribute()
    {
        return strtoupper($this->direction ?? '');
    }

    /**
     * Get lot_size attribute (alias for quantity) - for compatibility
     */
    public function getLotSizeAttribute()
    {
        return $this->quantity ?? 0;
    }

    /**
     * Get stop_loss attribute (alias for sl_price) - for compatibility
     */
    public function getStopLossAttribute()
    {
        return $this->sl_price;
    }

    /**
     * Get take_profit attribute (alias for tp_price) - for compatibility
     */
    public function getTakeProfitAttribute()
    {
        return $this->tp_price;
    }

    /**
     * Get order_data attribute (alias for response_data) - for compatibility
     */
    public function getOrderDataAttribute()
    {
        return $this->response_data;
    }
}

