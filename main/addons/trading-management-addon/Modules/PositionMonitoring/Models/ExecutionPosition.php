<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Models;

use App\Models\Signal;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use Illuminate\Database\Eloquent\Model;

class ExecutionPosition extends Model
{
    protected $table = 'execution_positions';

    protected $fillable = [
        'signal_id',
        'connection_id', // Database column name
        'execution_log_id',
        'order_id',
        'symbol',
        'direction',
        'quantity',
        'entry_price',
        'current_price',
        'sl_price',
        'tp_price',
        'status',
        'pnl',
        'pnl_percentage',
        'closed_at',
        'closed_reason',
        'last_price_update_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'current_price' => 'decimal:8',
        'sl_price' => 'decimal:8',
        'tp_price' => 'decimal:8',
        'pnl' => 'decimal:8',
        'pnl_percentage' => 'decimal:4',
        'closed_at' => 'datetime',
        'last_price_update_at' => 'datetime',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

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

    public function executionLog()
    {
        return $this->belongsTo(ExecutionLog::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function updatePnL(float $currentPrice): void
    {
        // Validate inputs before calculation
        if ($currentPrice <= 0 || $this->entry_price <= 0 || $this->quantity <= 0) {
            \Log::warning('ExecutionPosition::updatePnL - Invalid values for PnL calculation', [
                'position_id' => $this->id,
                'current_price' => $currentPrice,
                'entry_price' => $this->entry_price,
                'quantity' => $this->quantity,
            ]);
            return;
        }

        $this->current_price = $currentPrice;
        
        $priceDiff = $this->direction === 'buy'
            ? $currentPrice - $this->entry_price
            : $this->entry_price - $currentPrice;

        $this->pnl = $priceDiff * $this->quantity;
        
        // Avoid division by zero - only calculate percentage if entry_price is valid
        if ($this->entry_price > 0) {
            $this->pnl_percentage = ($priceDiff / $this->entry_price) * 100;
        } else {
            $this->pnl_percentage = 0;
        }
        
        $this->last_price_update_at = now();
        
        $this->save();
    }

    public function shouldCloseBySL(float $currentPrice): bool
    {
        if (!$this->sl_price) return false;

        return $this->direction === 'buy'
            ? $currentPrice <= $this->sl_price
            : $currentPrice >= $this->sl_price;
    }

    public function shouldCloseByTP(float $currentPrice): bool
    {
        if (!$this->tp_price) return false;

        return $this->direction === 'buy'
            ? $currentPrice >= $this->tp_price
            : $currentPrice <= $this->tp_price;
    }
}

