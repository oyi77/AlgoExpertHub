<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Searchable, HasApiTokens;

    protected $casts = [
        'address' => 'object',
        'kyc_information' => 'array'
    ];


    public function loginSecurity()
    {
        return $this->hasOne(LoginSecurity::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(PlanSubscription::class);
    }

    public function currentplan()
    {
        return $this->subscriptions()->where('is_current',1);
    }
    
    public function payments()
    {
        return $this->hasMany(Payment::class,'user_id');
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class,'user_id');
    }

    public function withdraws()
    {
        return $this->hasMany(Withdraw::class,'user_id');
    }

    public function refferals()
    {
        return $this->hasMany(User::class,'ref_id' );
    }

    public function refferedBy()
    {
        return $this->belongsTo(User::class,'ref_id');
    }
    
    public function reffer()
    {
        return $this->hasMany(User::class,'ref_id');
    }

    public function interest()
    {
        return $this->hasMany(UserInterest::class,'user_id');
    }

    public function commissions()
    {
        return $this->hasMany(ReferralCommission::class,'commission_to');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class,'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class,'user_id');
    }

    public function dashboardSignal()
    {
        return $this->hasMany(DashboardSignal::class);
    }

    // Telegram chat id for direct messaging
    protected $fillable = [
        'telegram_chat_id',
        'phone_country_code',
    ];

    public function trades()
    {
        return $this->hasMany(Trade::class,'user_id');
    }

    // Scopes for better query reusability
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function scopeEmailVerified($query)
    {
        return $query->where('is_email_verified', 1);
    }

    public function scopeKycApproved($query)
    {
        return $query->where('kyc_status', 'approved');
    }

    public function scopeKycPending($query)
    {
        return $query->where('kyc_status', 'pending');
    }

    public function scopeWithActiveSubscription($query)
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->where('is_current', 1)
              ->where('end_date', '>', now());
        });
    }

    public function scopeRegisteredToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeRegisteredThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeRegisteredThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeByReferrer($query, $referrerId)
    {
        return $query->where('ref_id', $referrerId);
    }

    public function scopeSearchByName($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('username', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%");
        });
    }

    public function onboardingProgress()
    {
        return $this->hasOne(UserOnboardingProgress::class);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for verified users
     */
    public function scopeVerified($query)
    {
        return $query->where('is_email_verified', 1);
    }

    /**
     * Scope for users with current subscriptions
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->where('is_current', 1)->where('end_date', '>', now());
        });
    }

    /**
     * Scope for users by KYC status
     */
    public function scopeByKycStatus($query, $status)
    {
        return $query->where('kyc_status', $status);
    }

    /**
     * Get current subscription with optimized query
     */
    public function getCurrentSubscriptionAttribute()
    {
        return $this->subscriptions()
            ->where('is_current', 1)
            ->where('end_date', '>', now())
            ->with('plan:id,name,price,plan_type')
            ->first();
    }

}
