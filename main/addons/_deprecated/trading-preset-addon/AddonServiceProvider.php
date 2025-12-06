<?php

namespace Addons\TradingPresetAddon;

use App\Support\AddonRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'trading-preset-addon';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Services are auto-loaded via Composer autoload; no special bindings required.
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!AddonRegistry::active(self::SLUG)) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'trading-preset-addon');

        // Register observers
        if (class_exists(\App\Models\User::class)) {
            \App\Models\User::observe(\Addons\TradingPresetAddon\App\Observers\UserObserver::class);
        }

        if (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::observe(
                \Addons\TradingPresetAddon\App\Observers\ExecutionConnectionObserver::class
            );
        }

        if (file_exists(__DIR__ . '/routes/admin.php') && AddonRegistry::moduleEnabled(self::SLUG, 'admin_ui')) {
            Route::middleware(['web', 'admin', 'demo'])
                ->prefix('admin')
                ->name('admin.')
                ->group(function (): void {
                    require __DIR__ . '/routes/admin.php';
                });
        }

        if (file_exists(__DIR__ . '/routes/web.php') && AddonRegistry::moduleEnabled(self::SLUG, 'user_ui')) {
            Route::middleware(['web', 'auth'])
                ->name('user.')
                ->group(function (): void {
                    require __DIR__ . '/routes/web.php';
                });
        }
    }
}

