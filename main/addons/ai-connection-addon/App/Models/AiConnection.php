<?php

namespace Addons\AiConnectionAddon\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AiConnection extends Model
{
    use HasFactory;

    protected $table = 'ai_connections';

    protected $fillable = [
        'provider_id',
        'name',
        'credentials',
        'settings',
        'status',
        'priority',
        'rate_limit_per_minute',
        'rate_limit_per_day',
        'last_used_at',
        'last_error_at',
        'error_count',
        'success_count',
    ];

    protected $casts = [
        'settings' => 'array',
        'status' => 'string',
        'priority' => 'integer',
        'rate_limit_per_minute' => 'integer',
        'rate_limit_per_day' => 'integer',
        'last_used_at' => 'datetime',
        'last_error_at' => 'datetime',
        'error_count' => 'integer',
        'success_count' => 'integer',
    ];

    /**
     * Get the provider this connection belongs to
     */
    public function provider()
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    /**
     * Get usage logs for this connection
     */
    public function usageLogs()
    {
        return $this->hasMany(AiConnectionUsage::class, 'connection_id');
    }

    /**
     * Get recent usage logs
     */
    public function recentUsage($days = 7)
    {
        return $this->usageLogs()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Active connections only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By provider
     */
    public function scopeByProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope: By priority (ascending)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Scope: Healthy connections (low error rate)
     */
    public function scopeHealthy($query, $errorThreshold = 10)
    {
        return $query->where('error_count', '<', $errorThreshold);
    }

    /**
     * Encrypt and set credentials
     */
    public function setCredentialsAttribute($value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        
        $this->attributes['credentials'] = encrypt($value);
    }

    /**
     * Decrypt and get credentials
     */
    public function getCredentialsAttribute($value)
    {
        try {
            $decrypted = decrypt($value);
            $decoded = json_decode($decrypted, true);
            
            // CRITICAL FIX: Always return array, never string
            if (is_array($decoded)) {
                return $decoded;
            }
            
            // If JSON decode failed but we have a string, wrap it as api_key
            // This handles legacy data or simple string credentials
            if (is_string($decrypted) && !empty($decrypted)) {
                \Log::warning('Credentials stored as plain string, wrapping as api_key', [
                    'connection_id' => $this->id,
                ]);
                return ['api_key' => $decrypted];
            }
            
            // If we got here, something is wrong
            \Log::error('Credentials could not be decoded to array', [
                'connection_id' => $this->id,
                'decrypted_type' => gettype($decrypted),
            ]);
            return [];
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt credentials', [
                'connection_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get specific credential value
     */
    public function getCredential(string $key, $default = null)
    {
        $credentials = $this->credentials;
        
        // Credentials should always be an array now (fixed in getter)
        if (!is_array($credentials)) {
            \Log::warning('Credentials not array in getCredential', [
                'connection_id' => $this->id,
                'type' => gettype($credentials),
            ]);
            return $default;
        }
        
        return $credentials[$key] ?? $default;
    }

    /**
     * Get API key from credentials
     */
    public function getApiKey(): ?string
    {
        return $this->getCredential('api_key');
    }

    /**
     * Get base URL from credentials
     */
    public function getBaseUrl(): ?string
    {
        return $this->getCredential('base_url');
    }

    /**
     * Get model name from settings
     */
    public function getModel(): ?string
    {
        return $this->settings['model'] ?? null;
    }

    /**
     * Check if connection is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if connection has errors
     */
    public function hasErrors(): bool
    {
        return $this->status === 'error' || $this->error_count > 0;
    }

    /**
     * Record successful use
     */
    public function recordSuccess()
    {
        $this->increment('success_count');
        $this->update([
            'last_used_at' => now(),
            'error_count' => 0, // Reset error count on success
            'status' => 'active',
        ]);
    }

    /**
     * Record error
     */
    public function recordError(string $errorMessage = null)
    {
        // Increment error count and get new value
        $this->increment('error_count');
        $this->refresh(); // CRITICAL: Refresh to get the updated error_count from DB
        
        // Now check the correct (post-increment) error_count value
        $newStatus = $this->error_count >= 10 ? 'error' : $this->status;
        
        $this->update([
            'last_error_at' => now(),
            'status' => $newStatus,
        ]);
        
        if ($errorMessage) {
            \Log::error('AI Connection Error', [
                'connection_id' => $this->id,
                'connection_name' => $this->name,
                'provider' => $this->provider->slug ?? 'unknown',
                'error' => $errorMessage,
                'error_count' => $this->error_count, // Now shows correct count
            ]);
        }
    }

    /**
     * Check if rate limited
     */
    public function isRateLimited(): bool
    {
        if (!$this->rate_limit_per_minute) {
            return false;
        }

        $recentUsage = $this->usageLogs()
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        return $recentUsage >= $this->rate_limit_per_minute;
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->success_count + $this->error_count;
        return $total > 0 ? round(($this->success_count / $total) * 100, 2) : 0;
    }

    /**
     * Get health status
     */
    public function getHealthStatusAttribute(): string
    {
        if ($this->status === 'error') {
            return 'critical';
        }
        
        if ($this->error_count >= 5) {
            return 'warning';
        }
        
        if ($this->success_rate >= 95) {
            return 'healthy';
        }
        
        return 'degraded';
    }
}

