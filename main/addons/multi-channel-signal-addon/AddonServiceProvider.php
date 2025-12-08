<?php

namespace Addons\MultiChannelSignalAddon;

use App\Support\AddonRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'multi-channel-signal-addon';

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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'multi-channel-signal-addon');

        if (file_exists(__DIR__ . '/routes/api.php') && AddonRegistry::moduleEnabled(self::SLUG, 'processing')) {
            Route::middleware('api')
                ->prefix('api')
                ->group(function (): void {
                    require __DIR__ . '/routes/api.php';
                });
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

        // Register console commands (always register, needed for scheduler even when not in console)
        $this->commands([
            \Addons\MultiChannelSignalAddon\App\Console\Commands\ProcessTelegramMtprotoChannels::class,
            \Addons\MultiChannelSignalAddon\App\Console\Commands\ProcessTradingBotChannels::class,
        ]);
    }
}
