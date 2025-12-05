<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalTakeProfit extends Model
{
    use HasFactory;

    protected $table = 'signal_take_profits';

    protected $fillable = [
        'signal_id',
        'tp_level',
        'tp_price',
        'tp_percentage',
        'lot_percentage',
        'is_closed',
        'closed_at',
    ];

    protected $casts = [
        'tp_price' => 'decimal:8',
        'tp_percentage' => 'decimal:2',
        'lot_percentage' => 'decimal:2',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    /**
     * Scopes
     */
    
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('tp_level', $level);
    }

    /**
     * Helper Methods
     */
    
    public function markAsClosed(): void
    {
        $this->update([
            'is_closed' => true,
            'closed_at' => now(),
        ]);
    }

    public function isOpen(): bool
    {
        return !$this->is_closed;
    }
}
