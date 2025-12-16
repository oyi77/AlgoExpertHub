<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CacheManager;
use App\Services\QueryOptimizationService;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register CacheManager as singleton
        $this->app->singleton(CacheManager::class, function ($app) {
            return new CacheManager();
        });

        // Register QueryOptimizationService as singleton
        $this->app->singleton(QueryOptimizationService::class, function ($app) {
            return new QueryOptimizationService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Enable query monitoring in debug mode
        if (config('app.debug') && config('database.enable_query_monitoring')) {
            $queryOptimizationService = $this->app->make(QueryOptimizationService::class);
            $queryOptimizationService->enableQueryMonitoring();
        }
    }
}