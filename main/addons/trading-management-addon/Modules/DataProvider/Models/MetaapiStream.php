<?php

namespace Addons\TradingManagement\Modules\DataProvider\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MetaapiStream Model
 * 
 * Represents an active MetaAPI streaming connection for a specific account/symbol/timeframe
 */
class MetaapiStream extends Model
{
    use HasFactory;

    protected $table = 'metaapi_streams';

    protected $fillable = [
        'account_id',
        'symbol',
        'timeframe',
        'status',
        'subscriber_count',
        'last_update_at',
        'last_error',
    ];

    protected $casts = [
        'last_update_at' => 'datetime',
        'subscriber_count' => 'integer',
    ];

    /**
     * Get subscriptions for this stream
     */
    public function subscriptions()
    {
        return $this->hasMany(StreamSubscription::class, 'stream_id');
    }

    /**
     * Check if stream is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if stream has subscribers
     */
    public function hasSubscribers(): bool
    {
        return $this->subscriber_count > 0;
    }
}
