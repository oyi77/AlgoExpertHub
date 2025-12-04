<?php

namespace Addons\TradingManagement\Modules\TradingBot\Models;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use App\Models\Signal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TradingBotPosition Model
 * 
 * Tracks positions opened by trading bots
 * Links to execution_positions for actual exchange positions
 */
class TradingBotPosition extends Model
{
    use HasFactory;

    protected $table = 'trading_bot_positions';

    protected $fillable = [
        'bot_id',
        'signal_id',
        'execution_position_id',
        'symbol',
        'direction',
        'entry_price',
        'current_price',
        'stop_loss',
        'take_profit',
        'quantity',
        'status',
        'profit_loss',
        'close_reason',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'entry_price' => 'decimal:8',
        'current_price' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'quantity' => 'decimal:8',
        'profit_loss' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    public function bot()
    {
        return $this->belongsTo(TradingBot::class, 'bot_id');
    }

    public function signal()
    {
        return $this->belongsTo(Signal::class, 'signal_id');
    }

    public function executionPosition()
    {
        // Link to execution_positions if table exists
        if (\Schema::hasTable('execution_positions')) {
            return $this->belongsTo(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class, 'execution_position_id');
        }
        return null;
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

    public function scopeForBot($query, $botId)
    {
        return $query->where('bot_id', $botId);
    }

    /**
     * Helper Methods
     */
    
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Calculate current profit/loss percentage
     */
    public function getProfitLossPercentage(): ?float
    {
        if (!$this->current_price || !$this->entry_price) {
            return null;
        }

        if (in_array($this->direction, ['buy', 'long'])) {
            return (($this->current_price - $this->entry_price) / $this->entry_price) * 100;
        } else {
            return (($this->entry_price - $this->current_price) / $this->entry_price) * 100;
        }
    }

    /**
     * Check if stop loss hit
     */
    public function isStopLossHit(): bool
    {
        if (!$this->stop_loss || !$this->current_price) {
            return false;
        }

        if (in_array($this->direction, ['buy', 'long'])) {
            return $this->current_price <= $this->stop_loss;
        } else {
            return $this->current_price >= $this->stop_loss;
        }
    }

    /**
     * Check if take profit hit
     */
    public function isTakeProfitHit(): bool
    {
        if (!$this->take_profit || !$this->current_price) {
            return false;
        }

        if (in_array($this->direction, ['buy', 'long'])) {
            return $this->current_price >= $this->take_profit;
        } else {
            return $this->current_price <= $this->take_profit;
        }
    }
}
