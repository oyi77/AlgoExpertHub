<?php

namespace App\Services;

use App\Models\GlobalConfiguration;
use Illuminate\Support\Facades\Cache;

class GlobalConfigurationService
{
    /**
     * Get configuration value by key
     *
     * @param string $key
     * @param mixed $default
     * @param bool $useCache
     * @return mixed
     */
    public static function get(string $key, $default = null, bool $useCache = true)
    {
        if ($useCache) {
            return Cache::remember(
                "global_config.{$key}",
                now()->addHours(24),
                fn() => GlobalConfiguration::getValue($key, $default)
            );
        }

        return GlobalConfiguration::getValue($key, $default);
    }

    /**
     * Set configuration value by key
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @return GlobalConfiguration
     */
    public static function set(string $key, $value, ?string $description = null): GlobalConfiguration
    {
        $config = GlobalConfiguration::setValue($key, $value, $description);
        
        // Clear cache
        Cache::forget("global_config.{$key}");
        
        return $config;
    }

    /**
     * Get all configuration as array
     *
     * @return array
     */
    public static function all(): array
    {
        return Cache::remember(
            'global_config.all',
            now()->addHours(24),
            function () {
                return GlobalConfiguration::all()
                    ->pluck('config_value', 'config_key')
                    ->toArray();
            }
        );
    }

    /**
     * Check if configuration exists
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return GlobalConfiguration::where('config_key', $key)->exists();
    }

    /**
     * Delete configuration by key
     *
     * @param string $key
     * @return bool
     */
    public static function delete(string $key): bool
    {
        $deleted = GlobalConfiguration::where('config_key', $key)->delete();
        
        if ($deleted) {
            Cache::forget("global_config.{$key}");
            Cache::forget('global_config.all');
        }
        
        return $deleted > 0;
    }

    /**
     * Clear all configuration cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $keys = GlobalConfiguration::pluck('config_key');
        foreach ($keys as $key) {
            Cache::forget("global_config.{$key}");
        }
        Cache::forget('global_config.all');
    }
}
