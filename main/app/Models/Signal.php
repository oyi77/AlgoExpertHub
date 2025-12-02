<?php

namespace App\Models;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    use HasFactory, Searchable;

    public $searchable = ['id'];

    protected $casts = [
        'published_date' => 'datetime'
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_signals');
    }

    public function pair()
    {
        return $this->belongsTo(CurrencyPair::class, 'currency_pair_id');
    }

    public function time()
    {
        return $this->belongsTo(TimeFrame::class, 'time_frame_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id');
    }

    /**
     * Get the channel source that created this signal.
     */
    public function channelSource()
    {
        return $this->belongsTo(ChannelSource::class);
    }

    /**
     * Scope a query to only include auto-created signals.
     */
    public function scopeAutoCreated($query)
    {
        return $query->where('auto_created', 1);
    }

    /**
     * Scope a query to filter by channel source.
     */
    public function scopeByChannelSource($query, $channelSourceId)
    {
        return $query->where('channel_source_id', $channelSourceId);
    }

    /**
     * Check if signal was auto-created.
     */
    public function isAutoCreated()
    {
        return $this->auto_created == 1;
    }

    /**
     * Get the channel source that created this signal.
     */
    public function getChannelSource()
    {
        return $this->channelSource;
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->id = rand(1111111, 99999999);
            }
        });
    }
}
