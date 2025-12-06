<?php

namespace Addons\AlgoExpertPlus;

use App\Support\AddonRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AddonServiceProvider extends ServiceProvider
{
    protected const SLUG = 'algoexpert-plus-addon';

    public function register(): void
    {
        $manifest = $this->readManifest();
        $modules = collect($manifest['modules'] ?? []);

        // Register SEO service provider
        $seoEnabled = (bool) optional($modules->firstWhere('key', 'seo'))['enabled'] ?? false;
        if ($seoEnabled && class_exists(\Artesaos\SEOTools\Providers\SEOToolsServiceProvider::class)) {
            $this->app->register(\Artesaos\SEOTools\Providers\SEOToolsServiceProvider::class);
        }

        // Register Horizon service provider
        $queuesEnabled = (bool) optional($modules->firstWhere('key', 'queues'))['enabled'] ?? false;
        $queueIsRedis = env('QUEUE_CONNECTION') === 'redis';
        if ($queuesEnabled && $queueIsRedis && class_exists(\Laravel\Horizon\HorizonServiceProvider::class)) {
            $this->app->register(\Laravel\Horizon\HorizonServiceProvider::class);
        }

        // Register Backup service provider
        $backupEnabled = (bool) optional($modules->firstWhere('key', 'backup'))['enabled'] ?? false;
        if ($backupEnabled && class_exists(\Spatie\Backup\BackupServiceProvider::class)) {
            $this->app->register(\Spatie\Backup\BackupServiceProvider::class);
        }

        // Register Health service provider
        $healthEnabled = (bool) optional($modules->firstWhere('key', 'health'))['enabled'] ?? false;
        if ($healthEnabled && class_exists(\Spatie\Health\HealthServiceProvider::class)) {
            $this->app->register(\Spatie\Health\HealthServiceProvider::class);
        }

        // Register addon services as singletons
        $this->app->singleton(\Addons\AlgoExpertPlus\App\Services\BackupService::class);
        $this->app->singleton(\Addons\AlgoExpertPlus\App\Services\HealthService::class);
        $this->app->singleton(\Addons\AlgoExpertPlus\App\Services\SeoService::class);
        $this->app->singleton(\Addons\AlgoExpertPlus\App\Services\I18nService::class);
        $this->app->singleton(\Addons\AlgoExpertPlus\App\Services\SystemHealthService::class);
        $this->app->singleton(\Addons\AlgoExpertPlus\App\Services\DependencyService::class);
    }

    public function boot(): void
    {
        if (!AddonRegistry::active(self::SLUG)) {
            return;
        }

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'algoexpert-plus');

        $this->loadRoutes();

        $this->configureHorizonAccess();
        $this->configureLocale();
        $this->configureHealthUi();
    }

    protected function loadRoutes(): void
    {
        // Check if any module with admin_ui target is enabled
        $manifest = $this->readManifest();
        $modules = collect($manifest['modules'] ?? []);
        $hasAdminUi = $modules->contains(function ($module) {
            return ($module['enabled'] ?? false) && in_array('admin_ui', $module['targets'] ?? []);
        });

        // Always load routes if addon is active (main dashboard should be available)
        if (file_exists(__DIR__ . '/routes/admin.php')) {
            Route::middleware(['web', 'admin', 'demo'])
                ->prefix('admin')
                ->name('admin.')
                ->group(function (): void {
                    require __DIR__ . '/routes/admin.php';
                });
        }
    }

    protected function configureHorizonAccess(): void
    {
        try {
            $manifest = $this->readManifest();
            $queuesEnabled = (bool) optional(collect($manifest['modules'] ?? [])->firstWhere('key', 'queues'))['enabled'] ?? false;
            if ($queuesEnabled && env('QUEUE_CONNECTION') === 'redis' && class_exists(\Laravel\Horizon\Horizon::class)) {
                \Laravel\Horizon\Horizon::auth(function ($request) {
                    $user = auth()->guard('admin')->user();
                    if (!$user) {
                        return false;
                    }
                    if (method_exists($user, 'hasRole')) {
                        if ($user->type === 'super' || $user->hasRole('Super Admin')) {
                            return true;
                        }
                    }
                    return false;
                });
            }
        } catch (\Throwable $e) {
        }
    }

    protected function configureLocale(): void
    {
        try {
            $manifest = $this->readManifest();
            $i18nEnabled = (bool) optional(collect($manifest['modules'] ?? [])->firstWhere('key', 'i18n'))['enabled'] ?? false;
            if ($i18nEnabled) {
                $locale = config('app.locale', 'en');
                app()->setLocale($locale);
            }
        } catch (\Throwable $e) {
        }
    }

    protected function configureHealthUi(): void
    {
        try {
            $manifest = $this->readManifest();
            $healthEnabled = (bool) optional(collect($manifest['modules'] ?? [])->firstWhere('key', 'health'))['enabled'] ?? false;
            if ($healthEnabled && class_exists(\Spatie\Health\Facades\Health::class)) {
                // No-op: package registers routes; we only ensure it's loaded via provider
            }
        } catch (\Throwable $e) {
        }
    }

    protected function readManifest(): array
    {
        try {
            $path = __DIR__ . '/addon.json';
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);
                return is_array($data) ? $data : [];
            }
        } catch (\Throwable $e) {
        }
        return [];
    }
}
