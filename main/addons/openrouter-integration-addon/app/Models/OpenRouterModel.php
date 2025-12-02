<?php

namespace Addons\OpenRouterIntegration\App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenRouterModel extends Model
{
    protected $table = 'openrouter_models';

    protected $fillable = [
        'model_id',
        'name',
        'provider',
        'context_length',
        'pricing',
        'modalities',
        'is_available',
        'last_synced_at',
    ];

    protected $casts = [
        'context_length' => 'integer',
        'pricing' => 'array',
        'modalities' => 'array',
        'is_available' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get available models.
     */
    public static function getAvailable(): \Illuminate\Support\Collection
    {
        return static::where('is_available', true)
            ->orderBy('provider')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get models by provider.
     */
    public static function getByProvider(string $provider): \Illuminate\Support\Collection
    {
        return static::where('provider', $provider)
            ->where('is_available', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get formatted display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->provider} - {$this->name}";
    }

    /**
     * Get formatted pricing string.
     */
    public function getPricingStringAttribute(): string
    {
        if (!$this->pricing) {
            return 'N/A';
        }

        $prompt = $this->pricing['prompt'] ?? 0;
        $completion = $this->pricing['completion'] ?? 0;

        return sprintf('$%.4f / $%.4f per 1M tokens', $prompt * 1000000, $completion * 1000000);
    }
}

