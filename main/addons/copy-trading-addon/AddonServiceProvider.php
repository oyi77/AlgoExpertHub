<?php

namespace Addons\CopyTrading;

use Addons\CopyTrading\App\Listeners\PositionClosedListener;
use Addons\CopyTrading\App\Listeners\PositionCreatedListener;
use App\Support\AddonRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'copy-trading-addon';

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

        // Always load migrations and views if addon is active
        // The dependency check is done at runtime in controllers/services
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'copy-trading');

        // Register routes (always register if addon is active, dependency check is done in controllers)
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

        // Register event listeners (only if trading execution engine is active and ExecutionPosition class exists)
        if (AddonRegistry::active('trading-execution-engine-addon')
            && AddonRegistry::moduleEnabled(self::SLUG, 'copy_engine') 
            && class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class)) {
            $executionPositionClass = \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class;
            
            Event::listen(
                'eloquent.created: ' . $executionPositionClass,
                PositionCreatedListener::class
            );
            
            Event::listen(
                'eloquent.updated: ' . $executionPositionClass,
                PositionClosedListener::class
            );
        }
    }
}
