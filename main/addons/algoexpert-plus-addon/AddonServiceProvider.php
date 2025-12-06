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

        $seoEnabled = (bool) optional($modules->firstWhere('key', 'seo'))['enabled'] ?? false;
        if ($seoEnabled && class_exists(\Artesaos\SEOTools\Providers\SEOToolsServiceProvider::class)) {
            $this->app->register(\Artesaos\SEOTools\Providers\SEOToolsServiceProvider::class);
        }

        $queuesEnabled = (bool) optional($modules->firstWhere('key', 'queues'))['enabled'] ?? false;
        $queueIsRedis = env('QUEUE_CONNECTION') === 'redis';
        if ($queuesEnabled && $queueIsRedis && class_exists(\Laravel\Horizon\HorizonServiceProvider::class)) {
            $this->app->register(\Laravel\Horizon\HorizonServiceProvider::class);
        }
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
    }

    protected function loadRoutes(): void
    {
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

