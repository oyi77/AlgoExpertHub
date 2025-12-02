<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ChannelSource extends Model
{
    use HasFactory, Searchable;

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
        'default_plan_id',
        'default_market_id',
        'default_timeframe_id'
    ];

    protected $casts = [
        'last_processed_at' => 'datetime',
        'config' => 'array',
        'error_count' => 'integer',
        'auto_publish_confidence_threshold' => 'integer'
    ];

    /**
     * Get the user that owns the channel source.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the default plan.
     */
    public function defaultPlan()
    {
        return $this->belongsTo(Plan::class, 'default_plan_id');
    }

    /**
     * Get the default market.
     */
    public function defaultMarket()
    {
        return $this->belongsTo(Market::class, 'default_market_id');
    }

    /**
     * Get the default timeframe.
     */
    public function defaultTimeframe()
    {
        return $this->belongsTo(TimeFrame::class, 'default_timeframe_id');
    }

    /**
     * Get the channel messages.
     */
    public function messages()
    {
        return $this->hasMany(ChannelMessage::class);
    }

    /**
     * Get the signals created from this channel source.
     */
    public function signals()
    {
        return $this->hasMany(Signal::class);
    }

    /**
     * Encrypt config before saving.
     */
    public function setConfigAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['config'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['config'] = $value;
        }
    }

    /**
     * Decrypt config when retrieving.
     */
    public function getConfigAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($value);
            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            // If decryption fails, return empty array
            return [];
        }
    }

    /**
     * Scope a query to only include active channels.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include paused channels.
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope a query to only include channels with errors.
     */
    public function scopeError($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if channel is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Pause the channel.
     */
    public function pause()
    {
        $this->update(['status' => 'paused']);
        return $this;
    }

    /**
     * Resume the channel.
     */
    public function resume()
    {
        $this->update(['status' => 'active']);
        return $this;
    }

    /**
     * Increment error count and update last error.
     */
    public function incrementError($errorMessage = null)
    {
        $this->increment('error_count');
        
        if ($errorMessage) {
            $this->update(['last_error' => $errorMessage]);
        }

        // Auto-pause after 10 consecutive errors
        if ($this->error_count >= 10) {
            $this->update(['status' => 'error']);
        }

        return $this;
    }

    /**
     * Reset error count.
     */
    public function resetErrors()
    {
        $this->update([
            'error_count' => 0,
            'last_error' => null,
            'status' => 'active'
        ]);
        return $this;
    }

    /**
     * Update last processed timestamp.
     */
    public function updateLastProcessed()
    {
        $this->update(['last_processed_at' => now()]);
        return $this;
    }
}

