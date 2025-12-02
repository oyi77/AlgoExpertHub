<?php

namespace Addons\MultiChannelSignalAddon\App\Models;

use App\Models\Plan;
use App\Models\Signal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalAnalytic extends Model
{
    use HasFactory;

    protected $table = 'signal_analytics';

    protected $fillable = [
        'signal_id',
        'channel_source_id',
        'plan_id',
        'user_id',
        'currency_pair',
        'direction',
        'open_price',
        'sl',
        'tp',
        'actual_open_price',
        'actual_close_price',
        'profit_loss',
        'pips',
        'trade_status',
        'signal_received_at',
        'signal_published_at',
        'trade_opened_at',
        'trade_closed_at',
        'metadata',
    ];

    protected $casts = [
        'open_price' => 'decimal:8',
        'sl' => 'decimal:8',
        'tp' => 'decimal:8',
        'actual_open_price' => 'decimal:8',
        'actual_close_price' => 'decimal:8',
        'profit_loss' => 'decimal:8',
        'pips' => 'decimal:2',
        'metadata' => 'array',
        'signal_received_at' => 'datetime',
        'signal_published_at' => 'datetime',
        'trade_opened_at' => 'datetime',
        'trade_closed_at' => 'datetime',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function channelSource()
    {
        return $this->belongsTo(ChannelSource::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByChannel($query, int $channelSourceId)
    {
        return $query->where('channel_source_id', $channelSourceId);
    }

    public function scopeByPlan($query, int $planId)
    {
        return $query->where('plan_id', $planId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeClosed($query)
    {
        return $query->where('trade_status', 'closed');
    }

    public function scopeProfitable($query)
    {
        return $query->where('trade_status', 'closed')->where('profit_loss', '>', 0);
    }

    public function scopeLoss($query)
    {
        return $query->where('trade_status', 'closed')->where('profit_loss', '<', 0);
    }
}

