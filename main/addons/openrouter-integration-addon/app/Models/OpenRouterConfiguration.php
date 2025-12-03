<?php

namespace Addons\OpenRouterIntegration\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class OpenRouterConfiguration extends Model
{
    protected $table = 'openrouter_configurations';

    protected $fillable = [
        'name',
        'ai_connection_id', // NEW: Reference to centralized connection
        'api_key', // DEPRECATED: Kept for backward compatibility
        'model_id',
        'site_url',
        'site_name',
        'temperature',
        'max_tokens',
        'timeout',
        'enabled',
        'priority',
        'use_for_parsing',
        'use_for_analysis',
    ];

    protected $casts = [
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'timeout' => 'integer',
        'enabled' => 'boolean',
        'priority' => 'integer',
        'use_for_parsing' => 'boolean',
        'use_for_analysis' => 'boolean',
    ];

    /**
     * Get the AI connection this configuration uses
     */
    public function aiConnection()
    {
        return $this->belongsTo(\Addons\AiConnectionAddon\App\Models\AiConnection::class, 'ai_connection_id');
    }

    /**
     * Get decrypted API key (NEW - uses centralized connection)
     */
    public function getDecryptedApiKey(): ?string
    {
        // Use centralized connection if available
        if ($this->ai_connection_id && $this->aiConnection) {
            return $this->aiConnection->getApiKey();
        }

        // DEPRECATED: Fallback to local API key for backward compatibility
        if (empty($this->api_key)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_key);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if configuration uses centralized connection
     */
    public function usesCentralizedConnection(): bool
    {
        return !is_null($this->ai_connection_id);
    }

    /**
     * Get effective settings (merge connection + config settings)
     */
    public function getEffectiveSettings(): array
    {
        $settings = [
            'model' => $this->model_id,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
            'timeout' => $this->timeout,
            'site_url' => $this->site_url,
            'site_name' => $this->site_name,
        ];

        // Merge with connection settings if using centralized connection
        if ($this->ai_connection_id && $this->aiConnection) {
            $connectionSettings = $this->aiConnection->settings ?? [];
            $settings = array_merge($connectionSettings, array_filter($settings));
        }

        return $settings;
    }

    /**
     * Set encrypted API key.
     */
    public function setApiKeyAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['api_key'] = null;
            return;
        }

        // Only encrypt if not already encrypted
        try {
            Crypt::decryptString($value);
            $this->attributes['api_key'] = $value;
        } catch (\Exception $e) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * Get active configurations.
     */
    public static function getActive(): \Illuminate\Support\Collection
    {
        return static::where('enabled', true)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get active configurations for parsing.
     */
    public static function getActiveForParsing(): \Illuminate\Support\Collection
    {
        return static::where('enabled', true)
            ->where('use_for_parsing', true)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get active configurations for market analysis.
     */
    public static function getActiveForAnalysis(): \Illuminate\Support\Collection
    {
        return static::where('enabled', true)
            ->where('use_for_analysis', true)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get first active configuration for parsing.
     */
    public static function getFirstActiveForParsing(): ?self
    {
        return static::where('enabled', true)
            ->where('use_for_parsing', true)
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Get first active configuration for analysis.
     */
    public static function getFirstActiveForAnalysis(): ?self
    {
        return static::where('enabled', true)
            ->where('use_for_analysis', true)
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Relationship: Model.
     */
    public function model()
    {
        return $this->belongsTo(OpenRouterModel::class, 'model_id', 'model_id');
    }
}

