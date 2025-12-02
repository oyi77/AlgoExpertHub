<?php

namespace Addons\AiTradingAddon\App\Models;

use App\Models\User;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiModelProfile extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'ai_model_profiles';

    public $searchable = ['name', 'description', 'provider', 'model_name'];

    protected $fillable = [
        'name',
        'description',
        'created_by_user_id',
        'visibility',
        'clonable',
        'enabled',
        'provider',
        'model_name',
        'api_key_ref',
        'mode',
        'prompt_template',
        'settings',
        'max_calls_per_minute',
        'max_calls_per_day',
    ];

    protected $casts = [
        'clonable' => 'boolean',
        'enabled' => 'boolean',
        'settings' => 'array',
        'max_calls_per_minute' => 'integer',
        'max_calls_per_day' => 'integer',
    ];

    /**
     * Get the owner (user) of this profile.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get trading presets using this AI model profile.
     */
    public function tradingPresets()
    {
        if (!class_exists(\Addons\TradingPresetAddon\App\Models\TradingPreset::class)) {
            return collect();
        }
        return $this->hasMany(\Addons\TradingPresetAddon\App\Models\TradingPreset::class, 'ai_model_profile_id');
    }

    /**
     * Get count of linked presets.
     */
    public function getLinkedPresetsCountAttribute(): int
    {
        return $this->tradingPresets()->count();
    }

    /**
     * Check if profile can be edited by user.
     */
    public function canEditBy(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        // Owner can always edit
        if ($this->created_by_user_id === $userId) {
            return true;
        }

        // Public profiles cannot be edited by others
        return false;
    }

    /**
     * Check if profile can be cloned by user.
     */
    public function canBeClonedBy(?int $userId): bool
    {
        if (!$this->clonable) {
            return false;
        }

        // Public profiles can be cloned by anyone
        if ($this->visibility === 'PUBLIC_MARKETPLACE') {
            return true;
        }

        // Private profiles can only be cloned by owner
        return $this->created_by_user_id === $userId;
    }

    /**
     * Check if profile is private.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === 'PRIVATE';
    }

    /**
     * Get API key from reference (from config/env).
     */
    public function getApiKey(): ?string
    {
        if (!$this->api_key_ref) {
            return null;
        }

        // Try to get from config first
        $configKey = config("ai-trading.providers.{$this->provider}.api_key");
        if ($configKey) {
            return $configKey;
        }

        // Try from env
        $envKey = env($this->api_key_ref);
        if ($envKey) {
            return $envKey;
        }

        return null;
    }

    /**
     * Scope: Get public profiles.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'PUBLIC_MARKETPLACE');
    }

    /**
     * Scope: Get enabled profiles.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: Filter by mode.
     */
    public function scopeByMode($query, string $mode)
    {
        return $query->where('mode', $mode);
    }

    /**
     * Scope: Filter by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}

