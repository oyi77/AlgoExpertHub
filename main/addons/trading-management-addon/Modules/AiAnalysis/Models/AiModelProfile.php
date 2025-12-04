<?php

namespace Addons\TradingManagement\Modules\AiAnalysis\Models;

use App\Models\User;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AiModelProfile Model
 * 
 * Migrated from ai-trading-addon
 * Stores AI model configurations for market analysis
 */
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
        'ai_connection_id',
        'provider', // DEPRECATED
        'model_name', // DEPRECATED
        'api_key_ref', // DEPRECATED
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
     * Relationships
     */
    
    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function aiConnection()
    {
        if (class_exists(\Addons\AiConnectionAddon\App\Models\AiConnection::class)) {
            return $this->belongsTo(\Addons\AiConnectionAddon\App\Models\AiConnection::class, 'ai_connection_id');
        }
        return null;
    }

    public function tradingPresets()
    {
        // Future: Link to risk-management module presets
        if (class_exists(\Addons\TradingPresetAddon\App\Models\TradingPreset::class)) {
            return $this->hasMany(\Addons\TradingPresetAddon\App\Models\TradingPreset::class, 'ai_model_profile_id');
        }
        return collect();
    }

    /**
     * Scopes
     */
    
    public function scopePublic($query)
    {
        return $query->where('visibility', 'PUBLIC_MARKETPLACE');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeByMode($query, string $mode)
    {
        return $query->where('mode', $mode);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Helper Methods
     */
    
    public function getLinkedPresetsCountAttribute(): int
    {
        return $this->tradingPresets()->count();
    }

    public function canEditBy(?int $userId): bool
    {
        return $userId && $this->created_by_user_id === $userId;
    }

    public function canBeClonedBy(?int $userId): bool
    {
        if (!$this->clonable) return false;
        if ($this->visibility === 'PUBLIC_MARKETPLACE') return true;
        return $this->created_by_user_id === $userId;
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'PRIVATE';
    }

    public function usesCentralizedConnection(): bool
    {
        return !is_null($this->ai_connection_id);
    }

    public function getApiKey(): ?string
    {
        if ($this->ai_connection_id && $this->aiConnection) {
            return $this->aiConnection->getApiKey();
        }

        // Fallback to old method
        if (!$this->api_key_ref) return null;
        
        return config("ai-trading.providers.{$this->provider}.api_key") 
            ?? env($this->api_key_ref);
    }

    public function getModelName(): ?string
    {
        if ($this->ai_connection_id && $this->aiConnection) {
            return $this->aiConnection->getModel();
        }
        return $this->model_name;
    }

    public function getProviderSlug(): ?string
    {
        if ($this->ai_connection_id && $this->aiConnection && $this->aiConnection->provider) {
            return $this->aiConnection->provider->slug;
        }
        return $this->provider;
    }
}

