<?php

namespace Addons\AiTradingAddon\App\Services;

use Addons\AiTradingAddon\App\Contracts\AiTradingProviderInterface;
use Addons\AiTradingAddon\App\Models\AiModelProfile;
use Addons\AiTradingAddon\App\Services\Providers\OpenAiTradingProvider;
use Addons\AiTradingAddon\App\Services\Providers\GeminiTradingProvider;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class AiTradingProviderFactory
{
    protected static array $providers = [
        'openai' => OpenAiTradingProvider::class,
        'gemini' => GeminiTradingProvider::class,
    ];

    /**
     * Create provider instance.
     */
    public static function create(string $provider): AiTradingProviderInterface
    {
        if (!isset(self::$providers[$provider])) {
            throw new InvalidArgumentException("AI trading provider '{$provider}' is not supported.");
        }

        $providerClass = self::$providers[$provider];
        
        if (!class_exists($providerClass)) {
            throw new InvalidArgumentException("AI trading provider class '{$providerClass}' not found.");
        }

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
        if (!is_subclass_of($providerClass, AiTradingProviderInterface::class)) {
            throw new InvalidArgumentException("Provider class must implement AiTradingProviderInterface");
        }

        self::$providers[$name] = $providerClass;
    }

    /**
     * Get provider instance from AI Model Profile.
     */
    public static function createFromProfile(AiModelProfile $profile): ?AiTradingProviderInterface
    {
        try {
            if (!$profile->enabled) {
                Log::warning("AI Model Profile {$profile->id} is disabled");
                return null;
            }

            $provider = self::create($profile->provider);
            return $provider;
        } catch (\Exception $e) {
            Log::error("Failed to create AI trading provider from profile", [
                'profile_id' => $profile->id,
                'provider' => $profile->provider,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

