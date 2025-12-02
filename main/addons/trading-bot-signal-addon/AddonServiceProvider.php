<?php

namespace Addons\TradingBotSignalAddon;

use App\Support\AddonRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'trading-bot-signal-addon';

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/trading-bot.php',
            'trading-bot'
        );
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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'trading-bot-signal-addon');

        if (file_exists(__DIR__ . '/routes/admin.php') && AddonRegistry::moduleEnabled(self::SLUG, 'admin_ui')) {
            Route::middleware(['web', 'admin', 'demo'])
                ->prefix('admin')
                ->name('admin.')
                ->group(function (): void {
                    require __DIR__ . '/routes/admin.php';
                });
        }

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Addons\TradingBotSignalAddon\App\Console\Commands\TradingBotWorkerCommand::class,
                \Addons\TradingBotSignalAddon\App\Console\Commands\SyncFirebaseDataCommand::class,
                \Addons\TradingBotSignalAddon\App\Console\Commands\TestFirebaseConnectionCommand::class,
                \Addons\TradingBotSignalAddon\App\Console\Commands\RefreshFirebaseTokenCommand::class,
            ]);
        }
    }
}

