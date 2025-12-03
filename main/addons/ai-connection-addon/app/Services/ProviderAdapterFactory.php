<?php

namespace Addons\AiConnectionAddon\App\Services;

use Addons\AiConnectionAddon\App\Contracts\AiProviderInterface;
use Addons\AiConnectionAddon\App\Providers\OpenAiAdapter;
use Addons\AiConnectionAddon\App\Providers\GeminiAdapter;
use Addons\AiConnectionAddon\App\Providers\OpenRouterAdapter;

class ProviderAdapterFactory
{
    protected $adapters = [];

    /**
     * Make an adapter for the given provider
     *
     * @param string $providerSlug Provider slug (openai, gemini, openrouter)
     * @return AiProviderInterface
     * @throws \Exception
     */
    public function make(string $providerSlug): AiProviderInterface
    {
        // Return cached adapter if exists
        if (isset($this->adapters[$providerSlug])) {
            return $this->adapters[$providerSlug];
        }

        // Create new adapter based on provider slug
        $adapter = match ($providerSlug) {
            'openai' => new OpenAiAdapter(),
            'gemini' => new GeminiAdapter(),
            'openrouter' => new OpenRouterAdapter(),
            default => throw new \Exception("Unsupported provider: {$providerSlug}"),
        };

        // Cache the adapter
        $this->adapters[$providerSlug] = $adapter;

        return $adapter;
    }

    /**
     * Check if provider is supported
     *
     * @param string $providerSlug Provider slug
     * @return bool
     */
    public function isSupported(string $providerSlug): bool
    {
        return in_array($providerSlug, ['openai', 'gemini', 'openrouter']);
    }

    /**
     * Get list of supported providers
     *
     * @return array
     */
    public function getSupportedProviders(): array
    {
        return [
            'openai' => 'OpenAI',
            'gemini' => 'Google Gemini',
            'openrouter' => 'OpenRouter',
        ];
    }
}

