<?php

namespace Addons\TradingManagement\Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TraderProfile extends Model
{
    use HasFactory;

    protected $table = 'trader_profiles';

    protected $fillable = [
        'user_id', 'display_name', 'bio', 'avatar_url', 'is_public',
        'accepts_followers', 'max_followers', 'subscription_price', 'currency',
        'total_followers', 'total_profit_percent', 'win_rate', 'avg_monthly_return',
        'max_drawdown', 'trades_count', 'verified', 'trading_style'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'accepts_followers' => 'boolean',
        'verified' => 'boolean',
        'subscription_price' => 'decimal:2',
        'total_profit_percent' => 'decimal:2',
        'win_rate' => 'decimal:2',
        'avg_monthly_return' => 'decimal:2',
        'max_drawdown' => 'decimal:2',
        'trading_style' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaderboardEntries()
    {
        return $this->hasMany(TraderLeaderboard::class, 'trader_id');
    }

    public function ratings()
    {
        return $this->hasMany(TraderRating::class, 'trader_id');
    }

    public function followers()
    {
        // Link to copy_trading_subscriptions
        $subscriptionClass = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class;
        if (class_exists($subscriptionClass)) {
            return $this->hasMany($subscriptionClass, 'trader_id', 'user_id')
                ->where('is_active', true);
        }
        return null;
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeAcceptingFollowers($query)
    {
        return $query->where('accepts_followers', true)
            ->where(function($q) {
                $q->whereNull('max_followers')
                  ->orWhereRaw('total_followers < max_followers');
            });
    }

    public function scopeFree($query)
    {
        return $query->where('subscription_price', 0);
    }

    public function scopePaid($query)
    {
        return $query->where('subscription_price', '>', 0);
    }

    public function scopeTopPerformers($query)
    {
        return $query->orderBy('total_profit_percent', 'desc');
    }

    public function canAcceptFollower(): bool
    {
        if (!$this->accepts_followers) {
            return false;
        }

        if ($this->max_followers === null) {
            return true;
        }

        return $this->total_followers < $this->max_followers;
    }
}


