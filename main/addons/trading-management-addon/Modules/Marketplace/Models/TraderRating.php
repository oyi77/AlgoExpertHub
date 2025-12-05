<?php

namespace Addons\TradingManagement\Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TraderRating extends Model
{
    use HasFactory;

    protected $table = 'trader_ratings';

    protected $fillable = [
        'trader_id', 'follower_id', 'rating', 'review',
        'verified_follower', 'helpful_votes'
    ];

    protected $casts = [
        'verified_follower' => 'boolean',
    ];

    public function traderProfile()
    {
        return $this->belongsTo(TraderProfile::class, 'trader_id');
    }

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function scopeVerified($query)
    {
        return $query->where('verified_follower', true);
    }

    public function scopeWithReview($query)
    {
        return $query->whereNotNull('review');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeHelpful($query)
    {
        return $query->orderBy('helpful_votes', 'desc');
    }
}


