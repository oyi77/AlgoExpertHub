<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App\Contracts\AiProviderInterface;
use Addons\MultiChannelSignalAddon\App\Models\AiConfiguration;
use Addons\MultiChannelSignalAddon\App\Services\AiProviders\OpenAiProvider;
use Addons\MultiChannelSignalAddon\App\Services\AiProviders\GeminiProvider;
use InvalidArgumentException;

class AiProviderFactory
{
    protected static array $providers = [
        'openai' => OpenAiProvider::class,
        'gemini' => GeminiProvider::class,
    ];

    /**
     * Create provider instance.
     */
    public static function create(string $provider): AiProviderInterface
    {
        if (!isset(self::$providers[$provider])) {
            throw new InvalidArgumentException("AI provider '{$provider}' is not supported.");
        }

        $providerClass = self::$providers[$provider];
        return new $providerClass();
    }

    /**
     * Get all available providers.
     */
    public static function getAvailableProviders(): array
    {
        return array_keys(self::$providers);
    }

    /**
     * Register a custom provider.
     */
    public static function register(string $name, string $providerClass): void
    {
        if (!is_subclass_of($providerClass, AiProviderInterface::class)) {
            throw new InvalidArgumentException("Provider class must implement AiProviderInterface");
        }

        self::$providers[$name] = $providerClass;
    }

    /**
     * Get provider instance from configuration.
     */
    public static function createFromConfig(AiConfiguration $config): ?AiProviderInterface
    {
        try {
            return self::create($config->provider);
        } catch (\Exception $e) {
            return null;
        }
    }
}

