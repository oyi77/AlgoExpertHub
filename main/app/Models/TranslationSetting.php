<?php

namespace App\Models;

use Addons\AiConnectionAddon\App\Models\AiConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TranslationSetting extends Model
{
    use HasFactory;

    protected $table = 'translation_settings';

    protected $fillable = [
        'ai_connection_id',
        'fallback_connection_id',
        'batch_size',
        'delay_between_requests_ms',
        'settings',
    ];

    protected $casts = [
        'batch_size' => 'integer',
        'delay_between_requests_ms' => 'integer',
        'settings' => 'array',
    ];

    /**
     * Get the primary AI connection for translations
     */
    public function aiConnection()
    {
        return $this->belongsTo(AiConnection::class, 'ai_connection_id');
    }

    /**
     * Get the fallback AI connection
     */
    public function fallbackConnection()
    {
        return $this->belongsTo(AiConnection::class, 'fallback_connection_id');
    }

    /**
     * Get the current translation settings (singleton pattern)
     */
    public static function current(): ?self
    {
        return static::first();
    }

    /**
     * Check if translation is configured
     */
    public static function isConfigured(): bool
    {
        return static::whereNotNull('ai_connection_id')->exists();
    }

    /**
     * Get effective settings for translation
     */
    public function getEffectiveSettings(): array
    {
        $connectionSettings = $this->aiConnection->settings ?? [];
        $translationSettings = $this->settings ?? [];
        
        return array_merge($connectionSettings, $translationSettings);
    }
}

