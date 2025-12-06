<?php

namespace Addons\PageBuilderAddon;

use App\Support\AddonRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PageBuilderServiceProvider extends ServiceProvider
{
    protected const SLUG = 'page-builder-addon';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(\Addons\PageBuilderAddon\App\Services\PageBuilderService::class);
        $this->app->singleton(\Addons\PageBuilderAddon\App\Services\ThemeIntegrationService::class);
        $this->app->singleton(\Addons\PageBuilderAddon\App\Services\MenuManagerService::class);
        $this->app->singleton(\Addons\PageBuilderAddon\App\Services\TemplateService::class);
        $this->app->singleton(\Addons\PageBuilderAddon\App\Services\ThemeTemplateService::class);
        $this->app->singleton(\Addons\PageBuilderAddon\App\Services\LayoutManagerService::class);
        $this->app->singleton(\Addons\PageBuilderAddon\App\Services\WidgetLibraryService::class);
        $this->app->singleton(\Addons\PageBuilderAddon\App\Services\GlobalStylesService::class);
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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'page-builder-addon');

        // Load admin routes if admin_ui module is enabled
        if (file_exists(__DIR__ . '/routes/admin.php') && AddonRegistry::moduleEnabled(self::SLUG, 'admin_ui')) {
            Route::middleware(['web', 'admin', 'demo'])
                ->prefix('admin/page-builder')
                ->name('admin.page-builder.')
                ->group(function (): void {
                    require __DIR__ . '/routes/admin.php';
                });
        }
    }
}
