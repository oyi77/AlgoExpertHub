<?php

namespace Addons\TradingExecutionEngine\App\Models;

use App\Models\Signal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionPosition extends Model
{
    use HasFactory;

    protected $table = 'execution_positions';

    protected $fillable = [
        'signal_id',
        'connection_id',
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
        // Multi-TP fields
        'tp1_price',
        'tp2_price',
        'tp3_price',
        'tp1_close_pct',
        'tp2_close_pct',
        'tp3_close_pct',
        'tp1_closed_at',
        'tp2_closed_at',
        'tp3_closed_at',
        'tp1_closed_qty',
        'tp2_closed_qty',
        'tp3_closed_qty',
        // Trailing stop fields
        'trailing_stop_enabled',
        'trailing_stop_distance',
        'trailing_stop_percentage',
        'highest_price',
        'lowest_price',
        'breakeven_enabled',
        'breakeven_trigger_price',
        'sl_moved_to_breakeven',
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
        // Multi-TP casts
        'tp1_price' => 'decimal:8',
        'tp2_price' => 'decimal:8',
        'tp3_price' => 'decimal:8',
        'tp1_close_pct' => 'decimal:2',
        'tp2_close_pct' => 'decimal:2',
        'tp3_close_pct' => 'decimal:2',
        'tp1_closed_at' => 'datetime',
        'tp2_closed_at' => 'datetime',
        'tp3_closed_at' => 'datetime',
        'tp1_closed_qty' => 'decimal:8',
        'tp2_closed_qty' => 'decimal:8',
        'tp3_closed_qty' => 'decimal:8',
        // Trailing stop casts
        'trailing_stop_enabled' => 'boolean',
        'trailing_stop_distance' => 'decimal:4',
        'trailing_stop_percentage' => 'decimal:2',
        'highest_price' => 'decimal:8',
        'lowest_price' => 'decimal:8',
        'breakeven_enabled' => 'boolean',
        'breakeven_trigger_price' => 'decimal:8',
        'sl_moved_to_breakeven' => 'boolean',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function connection()
    {
        return $this->belongsTo(ExecutionConnection::class);
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

    public function scopeByConnection($query, int $connectionId)
    {
        return $query->where('connection_id', $connectionId);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Calculate PnL based on current price.
     */
    public function calculatePnL(?float $currentPrice = null): array
    {
        $price = $currentPrice ?? $this->current_price ?? $this->entry_price;
        
        if (!$price || !$this->entry_price || !$this->quantity) {
            return ['pnl' => 0, 'pnl_percentage' => 0];
        }

        $priceDiff = $this->direction === 'buy' 
            ? ($price - $this->entry_price) 
            : ($this->entry_price - $price);

        $pnl = $priceDiff * $this->quantity;
        $pnlPercentage = ($priceDiff / $this->entry_price) * 100;

        return [
            'pnl' => $pnl,
            'pnl_percentage' => $pnlPercentage,
        ];
    }

    /**
     * Update PnL based on current price.
     */
    public function updatePnL(?float $currentPrice = null): void
    {
        $calculated = $this->calculatePnL($currentPrice);
        
        $this->forceFill([
            'current_price' => $currentPrice ?? $this->current_price,
            'pnl' => $calculated['pnl'],
            'pnl_percentage' => $calculated['pnl_percentage'],
            'last_price_update_at' => now(),
        ])->save();
    }

    /**
     * Close position.
     */
    public function close(string $reason, ?float $closePrice = null): void
    {
        $closePrice = $closePrice ?? $this->current_price ?? $this->entry_price;
        $this->updatePnL($closePrice);

        $this->forceFill([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_reason' => $reason,
            'current_price' => $closePrice,
        ])->save();
    }
}

