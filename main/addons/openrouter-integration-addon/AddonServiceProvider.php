<?php

namespace Addons\OpenRouterIntegration;

use Addons\OpenRouterIntegration\App\Services\OpenRouterMarketAnalyzer;
use Addons\OpenRouterIntegration\App\Services\OpenRouterService;
use Addons\OpenRouterIntegration\App\Services\OpenRouterSignalParser;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(OpenRouterService::class, function ($app) {
            return new OpenRouterService();
        });

        $this->app->singleton(OpenRouterSignalParser::class, function ($app) {
            return new OpenRouterSignalParser($app->make(OpenRouterService::class));
        });

        $this->app->singleton(OpenRouterMarketAnalyzer::class, function ($app) {
            return new OpenRouterMarketAnalyzer($app->make(OpenRouterService::class));
        });

        // Merge addon config with app config
        $this->mergeConfigFrom(__DIR__ . '/config/openrouter.php', 'openrouter');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load views with namespace
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'openrouter');

        // Load routes (conditionally based on module status)
        $this->loadRoutes();

        // Register in AiProviderFactory (Multi-Channel Addon integration)
        $this->registerAiProvider();

        // Publish config (optional)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/openrouter.php' => config_path('openrouter.php'),
            ], 'openrouter-config');

            $this->publishes([
                __DIR__ . '/resources/views' => resource_path('views/vendor/openrouter'),
            ], 'openrouter-views');
        }
    }

    /**
     * Load addon routes.
     */
    protected function loadRoutes(): void
    {
        // Check if admin_ui module is enabled
        if ($this->isModuleEnabled('admin_ui')) {
            // Load admin routes file
            if (file_exists(__DIR__ . '/routes/admin.php')) {
                Route::middleware(['web', 'admin', 'demo'])
                    ->group(__DIR__ . '/routes/admin.php');
            }
        }
    }

    /**
     * Register OpenRouter as AI provider for Multi-Channel Addon.
     */
    protected function registerAiProvider(): void
    {
        // Check if Multi-Channel Addon is available
        if (class_exists(\Addons\MultiChannelSignalAddon\App\Services\AiProviderFactory::class)) {
            try {
                \Addons\MultiChannelSignalAddon\App\Services\AiProviderFactory::register(
                    'openrouter',
                    OpenRouterSignalParser::class
                );
            } catch (\Exception $e) {
                // Log error but don't break application
                \Illuminate\Support\Facades\Log::warning(
                    'Failed to register OpenRouter as AI provider: ' . $e->getMessage()
                );
            }
        }
    }

    /**
     * Check if module with target is enabled.
     */
    protected function isModuleEnabled(string $target): bool
    {
        // Get addon manifest
        $manifestPath = __DIR__ . '/addon.json';
        if (!file_exists($manifestPath)) {
            return true; // Default to enabled if manifest not found
        }

        try {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $modules = $manifest['modules'] ?? [];

            foreach ($modules as $module) {
                $targets = $module['targets'] ?? [];
                $enabled = $module['enabled'] ?? false;

                if (in_array($target, $targets) && $enabled) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return true; // Default to enabled on error
        }
    }
}

