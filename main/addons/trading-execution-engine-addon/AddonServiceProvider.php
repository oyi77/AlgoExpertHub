<?php

namespace Addons\TradingExecutionEngine;

use Addons\TradingExecutionEngine\App\Observers\SignalObserver;
use App\Models\Signal;
use App\Support\AddonRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'trading-execution-engine-addon';

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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'trading-execution-engine');

        // Register signal observer
        if (AddonRegistry::moduleEnabled(self::SLUG, 'execution')) {
            Signal::observe(SignalObserver::class);
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

