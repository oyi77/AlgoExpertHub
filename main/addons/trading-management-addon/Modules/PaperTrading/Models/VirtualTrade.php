<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\PaperTrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VirtualTrade extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'virtual_trades';

    protected $fillable = [
        'virtual_portfolio_id',
        'symbol',
        'direction',
        'quantity',
        'entry_price',
        'exit_price',
        'pnl',
        'pnl_percentage',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'entry_price' => 'decimal:20,8',
        'exit_price' => 'decimal:20,8',
        'pnl' => 'decimal:20,8',
        'pnl_percentage' => 'decimal:8,4',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the portfolio that owns this trade.
     */
    public function portfolio()
    {
        return $this->belongsTo(VirtualPortfolio::class, 'virtual_portfolio_id');
    }

    /**
     * Calculate PnL for the trade.
     */
    public function calculatePnL(): float
    {
        if ($this->entry_price <= 0) {
            return 0;
        }
        
        if ($this->direction === 'buy') {
            return ($this->exit_price - $this->entry_price) * $this->quantity;
        } else {
            return ($this->entry_price - $this->exit_price) * $this->quantity;
        }
    }

    /**
     * Calculate PnL percentage.
     */
    public function calculatePnLPercentage(): float
    {
        if ($this->entry_price <= 0) {
            return 0;
        }
        
        $pnl = $this->pnl ?? $this->calculatePnL();
        return ($pnl / ($this->entry_price * $this->quantity)) * 100;
    }

    /**
     * Close the trade with exit price.
     */
    public function close(float $exitPrice): void
    {
        $this->exit_price = $exitPrice;
        $this->pnl = $this->calculatePnL();
        $this->pnl_percentage = $this->calculatePnLPercentage();
        $this->status = 'closed';
        $this->closed_at = now();
        $this->save();
    }

    /**
     * Scope for open trades.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope for closed trades.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope for profitable trades.
     */
    public function scopeProfitable($query)
    {
        return $query->where('pnl', '>', 0);
    }

    /**
     * Scope for losing trades.
     */
    public function scopeLosing($query)
    {
        return $query->where('pnl', '<', 0);
    }

    /**
     * Get formatted PnL attribute.
     */
    public function getFormattedPnLAttribute(): string
    {
        $sign = ($this->pnl ?? 0) >= 0 ? '+' : '';
        return $sign . number_format($this->pnl ?? 0, 8);
    }
}
