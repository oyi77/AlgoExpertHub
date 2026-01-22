<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon;

use App\Support\AddonRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'dex-analytics-addon';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/dex-analytics.php', 'dex-analytics');
    }

    public function boot(): void
    {
        if (!AddonRegistry::active(self::SLUG)) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'dex-analytics-addon');

        if (file_exists(__DIR__ . '/routes/admin.php') && AddonRegistry::moduleEnabled(self::SLUG, 'admin_ui')) {
            Route::middleware(['web', 'admin', 'demo'])
                ->prefix('admin/dex-analytics')
                ->name('admin.dex-analytics.')
                ->group(function (): void {
                    require __DIR__ . '/routes/admin.php';
                });
        }

        if (file_exists(__DIR__ . '/routes/user.php') && AddonRegistry::moduleEnabled(self::SLUG, 'user_ui')) {
            Route::middleware(['web', 'auth', 'inactive', 'is_email_verified', '2fa', 'kyc'])
                ->prefix('user/dex-analytics')
                ->name('user.dex-analytics.')
                ->group(function (): void {
                    require __DIR__ . '/routes/user.php';
                });
        }

        if (file_exists(__DIR__ . '/routes/api.php') && AddonRegistry::moduleEnabled(self::SLUG, 'api')) {
            Route::middleware('api')
                ->prefix('api/dex-analytics')
                ->name('api.dex-analytics.')
                ->group(function (): void {
                    require __DIR__ . '/routes/api.php';
                });
        }

        if ($this->app->runningInConsole() && AddonRegistry::moduleEnabled(self::SLUG, 'processing')) {
            $this->commands([
                \Addons\DexAnalyticsAddon\App\Console\Commands\DexAnalyticsPollCommand::class,
                \Addons\DexAnalyticsAddon\App\Console\Commands\DexAnalyticsRefreshCommand::class,
                \Addons\DexAnalyticsAddon\App\Console\Commands\DexAnalyticsComputeCommand::class,
            ]);
        }
    }
}
