<?php

namespace Addons\AiTradingAddon;

use App\Support\AddonRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'ai-trading-addon';

    public function register()
    {
        // Register services, bindings, singletons
        $this->app->singleton(\Addons\AiTradingAddon\App\Services\AiTradingProviderFactory::class);
        
        // Merge addon config with app config (if exists)
        if (file_exists(__DIR__ . '/config/ai-trading.php')) {
            $this->mergeConfigFrom(__DIR__ . '/config/ai-trading.php', 'ai-trading');
        }
    }

    public function boot()
    {
        if (!AddonRegistry::active(self::SLUG)) {
            return;
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'ai-trading-addon');
        
        // Load routes (conditionally based on module status)
        if (file_exists(__DIR__ . '/routes/admin.php') && AddonRegistry::moduleEnabled(self::SLUG, 'admin_ui')) {
            Route::middleware(['web', 'admin', 'demo'])
                ->prefix('admin')
                ->name('admin.')
                ->group(function (): void {
                    require __DIR__ . '/routes/admin.php';
                });
        }

        if (file_exists(__DIR__ . '/routes/user.php') && AddonRegistry::moduleEnabled(self::SLUG, 'user_ui')) {
            Route::middleware(['web', 'auth', 'inactive', 'is_email_verified', '2fa', 'kyc'])
                ->name('user.')
                ->group(function (): void {
                    require __DIR__ . '/routes/user.php';
                });
        }
    }
}

