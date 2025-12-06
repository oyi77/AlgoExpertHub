<?php

namespace App\Services;

use App\Models\GlobalConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

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
        try {
            if ($useCache) {
                return Cache::remember(
                    "global_config.{$key}",
                    now()->addHours(24),
                    fn() => GlobalConfiguration::getValue($key, $default)
                );
            }

            return GlobalConfiguration::getValue($key, $default);
        } catch (\Exception $e) {
            \Log::error('GlobalConfigurationService::get error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
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
        try {
            $config = GlobalConfiguration::setValue($key, $value, $description);
            
            // Clear cache
            Cache::forget("global_config.{$key}");
            
            return $config;
        } catch (\Exception $e) {
            \Log::error('GlobalConfigurationService::set error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all configuration as array
     *
     * @return array
     */
    public static function all(): array
    {
        try {
            return Cache::remember(
                'global_config.all',
                now()->addHours(24),
                function () {
                    if (!Schema::hasTable('global_configurations')) {
                        return [];
                    }
                    return GlobalConfiguration::all()
                        ->pluck('config_value', 'config_key')
                        ->toArray();
                }
            );
        } catch (\Exception $e) {
            \Log::error('GlobalConfigurationService::all error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if configuration exists
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        try {
            if (!Schema::hasTable('global_configurations')) {
                return false;
            }
            return GlobalConfiguration::where('config_key', $key)->exists();
        } catch (\Exception $e) {
            \Log::error('GlobalConfigurationService::has error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete configuration by key
     *
     * @param string $key
     * @return bool
     */
    public static function delete(string $key): bool
    {
        try {
            if (!Schema::hasTable('global_configurations')) {
                return false;
            }
            $deleted = GlobalConfiguration::where('config_key', $key)->delete();
            
            if ($deleted) {
                Cache::forget("global_config.{$key}");
                Cache::forget('global_config.all');
            }
            
            return $deleted > 0;
        } catch (\Exception $e) {
            \Log::error('GlobalConfigurationService::delete error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all configuration cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        try {
            if (!Schema::hasTable('global_configurations')) {
                return;
            }
            $keys = GlobalConfiguration::pluck('config_key');
            foreach ($keys as $key) {
                Cache::forget("global_config.{$key}");
            }
            Cache::forget('global_config.all');
        } catch (\Exception $e) {
            \Log::error('GlobalConfigurationService::clearCache error', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
