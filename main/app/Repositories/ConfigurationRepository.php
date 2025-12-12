<?php

namespace App\Repositories;

use App\Models\Configuration;
use App\Services\CacheManager;

class ConfigurationRepository
{
    public static function get(): ?Configuration
    {
        $cacheManager = app(CacheManager::class);
        
        return $cacheManager->remember('configuration.main', 7200, function () {
            return Configuration::first();
        }, ['configuration']);
    }

    /**
     * Clear configuration cache
     */
    public static function clearCache(): void
    {
        $cacheManager = app(CacheManager::class);
        $cacheManager->invalidateByTags(['configuration']);
    }
}

