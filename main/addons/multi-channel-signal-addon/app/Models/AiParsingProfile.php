<?php

namespace Addons\MultiChannelSignalAddon\App\Models;

use Addons\AiConnectionAddon\App\Models\AiConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiParsingProfile extends Model
{
    use HasFactory;

    protected $table = 'ai_parsing_profiles';

    protected $fillable = [
        'channel_source_id',
        'ai_connection_id',
        'name',
        'parsing_prompt',
        'settings',
        'priority',
        'enabled',
    ];

    protected $casts = [
        'settings' => 'array',
        'priority' => 'integer',
        'enabled' => 'boolean',
    ];

    /**
     * Get the channel source this profile belongs to
     */
    public function channelSource()
    {
        return $this->belongsTo(ChannelSource::class, 'channel_source_id');
    }

    /**
     * Get the AI connection this profile uses
     */
    public function aiConnection()
    {
        return $this->belongsTo(AiConnection::class, 'ai_connection_id');
    }

    /**
     * Scope: Enabled profiles only
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: By priority (ascending)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Scope: Global profiles (not tied to specific channel)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('channel_source_id');
    }

    /**
     * Scope: For specific channel
     */
    public function scopeForChannel($query, int $channelId)
    {
        return $query->where('channel_source_id', $channelId);
    }

    /**
     * Check if this is a global profile
     */
    public function isGlobal(): bool
    {
        return is_null($this->channel_source_id);
    }

    /**
     * Get effective settings (merge connection settings with profile overrides)
     */
    public function getEffectiveSettings(): array
    {
        $connectionSettings = $this->aiConnection->settings ?? [];
        $profileSettings = $this->settings ?? [];
        
        return array_merge($connectionSettings, $profileSettings);
    }

    /**
     * Get effective parsing prompt
     */
    public function getParsingPrompt(): ?string
    {
        return $this->parsing_prompt;
    }

    /**
     * Check if profile is active and usable
     */
    public function isUsable(): bool
    {
        return $this->enabled && 
               $this->aiConnection && 
               $this->aiConnection->isActive();
    }

    /**
     * Get display name with status
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->name;
        
        if (!$this->enabled) {
            $name .= ' (Disabled)';
        } elseif (!$this->aiConnection || !$this->aiConnection->isActive()) {
            $name .= ' (Connection Inactive)';
        }
        
        return $name;
    }
}

