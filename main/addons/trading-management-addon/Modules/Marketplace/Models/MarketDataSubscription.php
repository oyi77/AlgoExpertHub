<?php

namespace Addons\TradingManagement\Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketDataSubscription extends Model
{
    use HasFactory;

    protected $table = 'market_data_subscriptions';

    protected $fillable = [
        'symbol', 'timeframe', 'subscriber_type', 'subscriber_id',
        'last_access', 'access_count', 'is_active'
    ];

    protected $casts = [
        'last_access' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    public function scopeByTimeframe($query, string $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }

    public function scopeBySubscriberType($query, string $type)
    {
        return $query->where('subscriber_type', $type);
    }

    public function scopeUnused($query, int $days = 30)
    {
        return $query->where('last_access', '<', now()->subDays($days));
    }

    public function recordAccess()
    {
        $this->increment('access_count');
        $this->update(['last_access' => now()]);
    }

    public static function getActiveSymbols(): array
    {
        return static::active()
            ->distinct()
            ->pluck('symbol')
            ->toArray();
    }

    public static function getActiveTimeframesForSymbol(string $symbol): array
    {
        return static::active()
            ->where('symbol', $symbol)
            ->distinct()
            ->pluck('timeframe')
            ->toArray();
    }
}


