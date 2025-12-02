<?php

namespace Addons\OpenRouterIntegration;

use App\Support\AddonRegistry;
use Addons\OpenRouterIntegration\App\Services\OpenRouterMarketAnalyzer;
use Addons\OpenRouterIntegration\App\Services\OpenRouterService;
use Addons\OpenRouterIntegration\App\Services\OpenRouterSignalParser;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'openrouter-integration-addon';

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
        if (!AddonRegistry::active(self::SLUG)) {
            return;
        }

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
        // Load admin routes (conditionally based on module status)
        if (file_exists(__DIR__ . '/routes/admin.php') && AddonRegistry::moduleEnabled(self::SLUG, 'admin_ui')) {
            Route::middleware(['web', 'admin', 'demo'])
                ->prefix('admin')
                ->name('admin.')
                ->group(function (): void {
                    require __DIR__ . '/routes/admin.php';
                });
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

}

