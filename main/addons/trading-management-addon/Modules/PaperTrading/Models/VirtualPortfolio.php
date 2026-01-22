<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\PaperTrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VirtualPortfolio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'virtual_portfolios';

    protected $fillable = [
        'user_id',
        'exchange_connection_id',
        'balance',
        'market_type',
        'currency',
        'initial_balance',
        'current_balance',
        'pnl',
        'pnl_percentage',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'initial_balance' => 'decimal:8',
        'current_balance' => 'decimal:8',
        'pnl' => 'decimal:8',
        'pnl_percentage' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this portfolio.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the exchange connection.
     */
    public function exchangeConnection()
    {
        return $this->belongsTo(
            \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class,
            'exchange_connection_id'
        );
    }

    /**
     * Get virtual trades for this portfolio.
     */
    public function virtualTrades()
    {
        return $this->hasMany(VirtualTrade::class, 'virtual_portfolio_id');
    }

    /**
     * Calculate PnL from initial balance.
     */
    public function calculatePnL(): float
    {
        if ($this->initial_balance <= 0) {
            return 0;
        }
        
        return $this->current_balance - $this->initial_balance;
    }

    /**
     * Calculate PnL percentage.
     */
    public function calculatePnLPercentage(): float
    {
        if ($this->initial_balance <= 0) {
            return 0;
        }
        
        return (($this->current_balance - $this->initial_balance) / $this->initial_balance) * 100;
    }

    /**
     * Update portfolio balance after a trade.
     */
    public function updateBalance(float $amount, string $type): void
    {
        if ($type === 'credit') {
            $this->current_balance += $amount;
        } else {
            $this->current_balance -= $amount;
        }
        
        $this->pnl = $this->calculatePnL();
        $this->pnl_percentage = $this->calculatePnLPercentage();
        
        $this->save();
    }

    /**
     * Check if portfolio has sufficient balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->current_balance >= $amount;
    }

    /**
     * Get formatted PnL with sign.
     */
    public function getFormattedPnLAttribute(): string
    {
        $sign = $this->pnl >= 0 ? '+' : '';
        return $sign . number_format($this->pnl, 8) . ' (' . $sign . number_format($this->pnl_percentage, 2) . '%)';
    }

    /**
     * Scope for active portfolios.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for crypto portfolios.
     */
    public function scopeCrypto($query)
    {
        return $query->where('market_type', 'crypto');
    }

    /**
     * Scope for forex portfolios.
     */
    public function scopeForex($query)
    {
        return $query->where('market_type', 'fx');
    }
}
