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
     * Scope for published signals with optimized ordering
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', 1)->orderBy('published_date', 'desc');
    }

    /**
     * Scope for signals by market with index optimization
     */
    public function scopeByMarket($query, $marketId)
    {
        return $query->where('market_id', $marketId);
    }

    /**
     * Scope for signals by currency pair with index optimization
     */
    public function scopeByCurrencyPair($query, $currencyPairId)
    {
        return $query->where('currency_pair_id', $currencyPairId);
    }

    /**
     * Scope for signals by direction with index optimization
     */
    public function scopeByDirection($query, $direction)
    {
        return $query->where('direction', $direction);
    }

    /**
     * Scope for recent signals with limit
     */
    public function scopeRecent($query, $limit = 20)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope with optimized eager loading for display
     */
    public function scopeWithDisplayData($query)
    {
        return $query->with([
            'pair:id,name',
            'time:id,name',
            'market:id,name',
            'plans:id,name,status'
        ]);
    }

    /**
     * Scope for published signals only
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', 1);
    }

    /**
     * Scope for draft signals only
     */
    public function scopeDraft($query)
    {
        return $query->where('is_published', 0);
    }

    /**
     * Scope for auto-created signals
     */
    public function scopeAutoCreated($query)
    {
        return $query->where('auto_created', 1);
    }

    /**
     * Scope for manual signals
     */
    public function scopeManual($query)
    {
        return $query->where('auto_created', 0);
    }

    /**
     * Scope for signals by market
     */
    public function scopeByMarket($query, $marketId)
    {
        return $query->where('market_id', $marketId);
    }

    /**
     * Scope for signals by timeframe
     */
    public function scopeByTimeframe($query, $timeframeId)
    {
        return $query->where('time_frame_id', $timeframeId);
    }

    /**
     * Scope for signals published today
     */
    public function scopePublishedToday($query)
    {
        return $query->where('is_published', 1)
                    ->whereDate('published_date', today());
    }

    /**
     * Scope for signals published this week
     */
    public function scopePublishedThisWeek($query)
    {
        return $query->where('is_published', 1)
                    ->whereBetween('published_date', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
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

    /**
     * Multiple Take Profit support
     */
    public function takeProfits()
    {
        return $this->hasMany(SignalTakeProfit::class);
    }

    public function openTakeProfits()
    {
        return $this->hasMany(SignalTakeProfit::class)->where('is_closed', false);
    }

    public function closedTakeProfits()
    {
        return $this->hasMany(SignalTakeProfit::class)->where('is_closed', true);
    }

    /**
     * Get primary TP (first level or fallback to signal.tp)
     */
    public function getPrimaryTpAttribute()
    {
        $firstTp = $this->takeProfits()->orderBy('tp_level')->first();
        return $firstTp ? $firstTp->tp_price : $this->tp;
    }

    /**
     * Check if signal has multiple TPs
     */
    public function hasMultipleTps(): bool
    {
        return $this->takeProfits()->count() > 0;
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
