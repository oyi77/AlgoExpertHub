<?php

namespace Addons\TradingManagement\Modules\DataProvider\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * StreamSubscription Model
 * 
 * Tracks which bots/connections subscribe to which streams
 */
class StreamSubscription extends Model
{
    use HasFactory;

    protected $table = 'stream_subscriptions';

    protected $fillable = [
        'stream_id',
        'subscriber_type',
        'subscriber_id',
    ];

    /**
     * Get the stream this subscription belongs to
     */
    public function stream()
    {
        return $this->belongsTo(MetaapiStream::class, 'stream_id');
    }

    /**
     * Get the subscriber (bot or connection) - polymorphic relationship
     */
    public function subscriber()
    {
        if ($this->subscriber_type === 'bot') {
            return $this->belongsTo(
                \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class,
                'subscriber_id'
            );
        } elseif ($this->subscriber_type === 'connection') {
            return $this->belongsTo(
                \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class,
                'subscriber_id'
            );
        }
        
        return null;
    }
}
