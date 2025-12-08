<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOnboardingProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'welcome_seen',
        'profile_completed',
        'plan_subscribed',
        'signal_source_added',
        'trading_connection_setup',
        'trading_preset_created',
        'first_deposit_made',
        'onboarding_completed',
        'completed_at',
    ];

    protected $casts = [
        'welcome_seen' => 'boolean',
        'profile_completed' => 'boolean',
        'plan_subscribed' => 'boolean',
        'signal_source_added' => 'boolean',
        'trading_connection_setup' => 'boolean',
        'trading_preset_created' => 'boolean',
        'first_deposit_made' => 'boolean',
        'onboarding_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the onboarding progress.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
