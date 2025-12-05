<?php

namespace Addons\TradingManagement\Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TraderLeaderboard extends Model
{
    use HasFactory;

    protected $table = 'trader_leaderboard';

    protected $fillable = [
        'trader_id', 'rank', 'timeframe', 'profit_percent', 'win_rate',
        'sharpe_ratio', 'roi', 'total_trades', 'followers_gained',
        'avg_rating', 'calculated_at'
    ];

    protected $casts = [
        'profit_percent' => 'decimal:2',
        'win_rate' => 'decimal:2',
        'sharpe_ratio' => 'decimal:4',
        'roi' => 'decimal:2',
        'avg_rating' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function traderProfile()
    {
        return $this->belongsTo(TraderProfile::class, 'trader_id');
    }

    public function scopeByTimeframe($query, string $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }

    public function scopeTopRanked($query, int $limit = 100)
    {
        return $query->orderBy('rank', 'asc')->limit($limit);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('calculated_at', 'desc');
    }
}


