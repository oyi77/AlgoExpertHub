<?php

namespace Addons\OpenRouterIntegration\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class OpenRouterConfiguration extends Model
{
    protected $table = 'openrouter_configurations';

    protected $fillable = [
        'name',
        'api_key',
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
     * Get decrypted API key.
     */
    public function getDecryptedApiKey(): ?string
    {
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

