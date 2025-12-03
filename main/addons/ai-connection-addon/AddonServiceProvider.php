<?php

namespace Addons\AiConnectionAddon;

use App\Support\AddonRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'ai-connection-addon';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register singleton services
        $this->app->singleton(\Addons\AiConnectionAddon\App\Services\AiConnectionService::class);
        $this->app->singleton(\Addons\AiConnectionAddon\App\Services\ConnectionRotationService::class);
        $this->app->singleton(\Addons\AiConnectionAddon\App\Services\ProviderAdapterFactory::class);
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
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'ai-connection-addon');

        // Load API routes
        if (file_exists(__DIR__ . '/routes/api.php') && AddonRegistry::moduleEnabled(self::SLUG, 'api')) {
            Route::middleware('api')
                ->prefix('api/ai-connections')
                ->name('api.ai-connections.')
                ->group(function (): void {
                    require __DIR__ . '/routes/api.php';
                });
        }

        // Load admin routes
        if (file_exists(__DIR__ . '/routes/admin.php') && AddonRegistry::moduleEnabled(self::SLUG, 'admin_ui')) {
            Route::middleware(['web', 'admin', 'demo'])
                ->prefix('admin/ai-connections')
                ->name('admin.ai-connections.')
                ->group(function (): void {
                    require __DIR__ . '/routes/admin.php';
                });
        }

        // Register scheduled commands
        if ($this->app->runningInConsole() && AddonRegistry::moduleEnabled(self::SLUG, 'monitoring')) {
            $this->commands([
                \Addons\AiConnectionAddon\App\Console\Commands\MonitorConnectionHealth::class,
                \Addons\AiConnectionAddon\App\Console\Commands\CleanupUsageLogs::class,
                \Addons\AiConnectionAddon\App\Console\Commands\MigrateExistingCredentials::class,
            ]);
        }
    }
}

