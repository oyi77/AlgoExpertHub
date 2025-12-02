<?php

namespace Addons\MultiChannelSignalAddon\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AiConfiguration extends Model
{
    protected $table = 'ai_configurations';

    protected $fillable = [
        'provider',
        'name',
        'api_key',
        'api_url',
        'model',
        'settings',
        'enabled',
        'priority',
        'timeout',
        'temperature',
        'max_tokens',
    ];

    protected $casts = [
        'settings' => 'array',
        'enabled' => 'boolean',
        'priority' => 'integer',
        'timeout' => 'integer',
        'temperature' => 'float',
        'max_tokens' => 'integer',
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

        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    /**
     * Get active AI configurations.
     */
    public static function getActive(): \Illuminate\Support\Collection
    {
        return static::where('enabled', true)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get configuration by provider.
     */
    public static function getByProvider(string $provider): ?self
    {
        return static::where('provider', $provider)
            ->where('enabled', true)
            ->first();
    }
}

