<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'direction',
        'quantity',
        'entry_price',
        'current_price',
        'sl_price',
        'tp_price',
        'pnl',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'current_price' => 'decimal:8',
        'sl_price' => 'decimal:8',
        'tp_price' => 'decimal:8',
        'pnl' => 'decimal:8',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySymbol($query, $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Helper methods
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function calculatePnL(float $currentPrice): float
    {
        if ($this->direction === 'buy') {
            return ($currentPrice - $this->entry_price) * $this->quantity;
        } else {
            return ($this->entry_price - $currentPrice) * $this->quantity;
        }
    }

    public function updatePnL(float $currentPrice): void
    {
        $this->current_price = $currentPrice;
        $this->pnl = $this->calculatePnL($currentPrice);
        $this->save();
    }

    public function close(float $closePrice): void
    {
        $this->current_price = $closePrice;
        $this->pnl = $this->calculatePnL($closePrice);
        $this->status = 'closed';
        $this->closed_at = now();
        $this->save();

        // Update user balance
        $this->user->balance += $this->pnl;
        $this->user->save();
    }
}
