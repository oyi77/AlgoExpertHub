<?php

namespace Addons\TradingManagement;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Trading Management Addon Service Provider
 * 
 * Registers all modules, routes, views, and services for the Trading Management addon.
 * 
 * Modules:
 * - data_provider: Data connections and market data fetching
 * - market_data: Market data storage and caching
 * - filter_strategy: Technical indicator filtering
 * - ai_analysis: AI-powered market confirmation
 * - risk_management: Manual presets + Smart Risk
 * - execution: Trade execution
 * - position_monitoring: Position tracking and analytics
 * - copy_trading: Social trading
 * - backtesting: Strategy testing
 */
class AddonServiceProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register()
    {
        // Register shared services (available to all modules)
        $this->registerSharedServices();

        // Merge addon config
        $this->mergeConfigFrom(__DIR__ . '/config/trading-management.php', 'trading-management');
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot()
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load views with namespace
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'trading-management');

        // Load routes conditionally based on enabled modules
        $this->loadRoutes();

        // Register scheduled tasks
        $this->registerScheduledTasks();

        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }

        // Publish assets
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/trading-management'),
        ], 'trading-management-views');

        $this->publishes([
            __DIR__ . '/config/trading-management.php' => config_path('trading-management.php'),
        ], 'trading-management-config');
    }

    /**
     * Register shared services
     *
     * @return void
     */
    protected function registerSharedServices()
    {
        // Register shared services that all modules can use
        // These will be singletons to ensure consistency
        
        // Phase 2: Data Layer services
        if ($this->isModuleEnabled('data_provider')) {
            $this->app->singleton(
                \Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory::class
            );
            $this->app->singleton(
                \Addons\TradingManagement\Modules\DataProvider\Services\DataConnectionService::class
            );
        }

        if ($this->isModuleEnabled('market_data')) {
            $this->app->singleton(
                \Addons\TradingManagement\Modules\MarketData\Services\MarketDataService::class
            );
        }
    }

    /**
     * Load routes based on enabled modules
     *
     * @return void
     */
    protected function loadRoutes()
    {
        // Get addon manifest to check which modules are enabled
        $manifest = json_decode(file_get_contents(__DIR__ . '/addon.json'), true);
        $modules = collect($manifest['modules'] ?? []);

        // Admin routes (if any module with admin_ui is enabled)
        if ($modules->where('enabled', true)->whereIn('targets', [['admin_ui']])->isNotEmpty()) {
            Route::middleware(['web', 'admin', 'demo'])
                ->prefix('admin/trading-management')
                ->name('admin.trading-management.')
                ->group(__DIR__ . '/routes/admin.php');
        }

        // User routes (if any module with user_ui is enabled)
        if ($modules->where('enabled', true)->whereIn('targets', [['user_ui']])->isNotEmpty()) {
            Route::middleware(['web', 'auth', 'inactive', 'is_email_verified', '2fa', 'kyc'])
                ->prefix('user/trading-management')
                ->name('user.trading-management.')
                ->group(__DIR__ . '/routes/user.php');
        }

        // API routes (if needed)
        // Route::prefix('api/trading-management')
        //     ->middleware('api')
        //     ->name('api.trading-management.')
        //     ->group(__DIR__ . '/routes/api.php');
    }

    /**
     * Register scheduled tasks
     *
     * @return void
     */
    protected function registerScheduledTasks()
    {
        if ($this->isModuleEnabled('market_data')) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

                // Fetch market data every 5 minutes
                $schedule->job(new \Addons\TradingManagement\Modules\MarketData\Jobs\FetchAllActiveConnectionsJob())
                    ->everyFiveMinutes()
                    ->name('trading-management:fetch-market-data')
                    ->withoutOverlapping();

                // Cleanup old data daily at 2 AM
                $schedule->job(new \Addons\TradingManagement\Modules\MarketData\Jobs\CleanOldMarketDataJob())
                    ->dailyAt('02:00')
                    ->name('trading-management:cleanup-market-data');
            });
        }
    }

    /**
     * Register artisan commands
     *
     * @return void
     */
    protected function registerCommands()
    {
        // Commands will be added as modules are implemented
        // Example:
        // $this->commands([
        //     Commands\FetchMarketDataCommand::class,
        //     Commands\BackfillHistoricalDataCommand::class,
        // ]);
    }

    /**
     * Check if a module is enabled
     *
     * @param string $moduleKey Module key from addon.json
     * @return bool
     */
    protected function isModuleEnabled(string $moduleKey): bool
    {
        $manifest = json_decode(file_get_contents(__DIR__ . '/addon.json'), true);
        $modules = collect($manifest['modules'] ?? []);

        $module = $modules->firstWhere('key', $moduleKey);

        return $module && ($module['enabled'] ?? false);
    }
}

