<?php

namespace Addons\MultiChannelSignalAddon\App\Models;

use App\Models\Market;
use App\Models\Plan;
use App\Models\Signal;
use App\Models\TimeFrame;
use App\Models\User;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ChannelSource extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'channel_sources';

    public $searchable = ['name'];

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'config',
        'status',
        'last_processed_at',
        'error_count',
        'last_error',
        'auto_publish_confidence_threshold',
        'parser_preference',
        'default_plan_id',
        'default_market_id',
        'default_timeframe_id',
        'is_admin_owned',
        'scope',
    ];

    protected $casts = [
        'last_processed_at' => 'datetime',
        // 'config' => 'array', // REMOVED: Using custom accessor/mutator for encryption
        'error_count' => 'integer',
        'auto_publish_confidence_threshold' => 'integer',
        'is_admin_owned' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function defaultPlan()
    {
        return $this->belongsTo(Plan::class, 'default_plan_id');
    }

    public function defaultMarket()
    {
        return $this->belongsTo(Market::class, 'default_market_id');
    }

    public function defaultTimeframe()
    {
        return $this->belongsTo(TimeFrame::class, 'default_timeframe_id');
    }

    public function messages()
    {
        return $this->hasMany(ChannelMessage::class);
    }

    public function signals()
    {
        return $this->hasMany(Signal::class);
    }

    /**
     * Get users assigned to this admin channel.
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'channel_source_users');
    }

    /**
     * Get plans assigned to this admin channel.
     */
    public function assignedPlans()
    {
        return $this->belongsToMany(Plan::class, 'channel_source_plans');
    }

    /**
     * Get AI parsing profiles for this channel
     */
    public function parsingProfiles()
    {
        return $this->hasMany(AiParsingProfile::class, 'channel_source_id');
    }

    /**
     * Get enabled parsing profiles
     */
    public function activeParsingProfiles()
    {
        return $this->parsingProfiles()->enabled()->byPriority();
    }

    public function setConfigAttribute($value): void
    {
        if (is_array($value)) {
            $json = json_encode($value);
            if ($json === false) {
                \Log::error("Failed to JSON encode config", ['config' => $value]);
                throw new \RuntimeException("Failed to encode config to JSON");
            }
            $this->attributes['config'] = Crypt::encryptString($json);
        } else {
            $this->attributes['config'] = $value;
        }
    }

    public function getConfigAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($value);
            $decoded = json_decode($decrypted, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error("Failed to JSON decode config", [
                    'json_error' => json_last_error_msg(),
                    'decrypted_length' => strlen($decrypted)
                ]);
                return [];
            }
            
            return $decoded ?? [];
        } catch (\Throwable $th) {
            \Log::error("Failed to decrypt config", [
                'error' => $th->getMessage(),
                'value_length' => strlen($value ?? '')
            ]);
            return [];
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeError($query)
    {
        return $query->where('status', 'error');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include admin-owned channels.
     */
    public function scopeAdminOwned($query)
    {
        return $query->where('is_admin_owned', 1);
    }

    /**
     * Scope a query to only include user-owned channels.
     */
    public function scopeUserOwned($query)
    {
        return $query->where('is_admin_owned', 0);
    }

    /**
     * Scope a query to only include channels assigned to a user.
     * Includes: channels assigned directly to user, channels assigned to user's plan, global channels.
     */
    public function scopeAssignedToUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // Global channels (available to all users)
            $q->where('scope', 'global')
                // Channels assigned directly to this user
                ->orWhereHas('assignedUsers', function ($userQuery) use ($userId) {
                    $userQuery->where('users.id', $userId);
                })
                // Channels assigned to plans the user is subscribed to
                ->orWhereHas('assignedPlans', function ($planQuery) use ($userId) {
                    $planQuery->whereHas('subscriptions', function ($subscriptionQuery) use ($userId) {
                        $subscriptionQuery->where('user_id', $userId)
                            ->where('is_current', 1)
                            ->where('plan_expired_at', '>', now());
                    });
                });
        });
    }

    /**
     * Check if channel is admin-owned.
     */
    public function isAdminOwned(): bool
    {
        return $this->is_admin_owned === 1 || $this->is_admin_owned === true;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function updateLastProcessed(?\DateTimeInterface $processedAt = null): void
    {
        $this->forceFill([
            'last_processed_at' => $processedAt ?? now(),
        ])->save();
    }

    public function incrementError(?string $message = null): void
    {
        $this->forceFill([
            'status' => 'error',
            'error_count' => ($this->error_count ?? 0) + 1,
            'last_error' => $message,
        ])->save();
    }

    public function resetErrors(): void
    {
        $this->forceFill([
            'status' => 'active',
            'error_count' => 0,
            'last_error' => null,
        ])->save();
    }
}
