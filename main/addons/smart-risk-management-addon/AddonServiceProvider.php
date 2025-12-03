<?php

namespace Addons\SmartRiskManagement;

use App\Support\AddonRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'smart-risk-management-addon';

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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'smart-risk-management');

        // Load admin routes
        if (file_exists(__DIR__ . '/routes/admin.php') && AddonRegistry::moduleEnabled(self::SLUG, 'admin_ui')) {
            Route::middleware(['web', 'admin', 'demo', 'permission:signal,admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(function (): void {
                    require __DIR__ . '/routes/admin.php';
                });
        }

        // Load user routes
        if (file_exists(__DIR__ . '/routes/user.php') && AddonRegistry::moduleEnabled(self::SLUG, 'user_ui')) {
            Route::middleware(['web', 'auth', 'inactive', 'is_email_verified', '2fa', 'kyc'])
                ->name('user.')
                ->group(function (): void {
                    require __DIR__ . '/routes/user.php';
                });
        }
    }
}

