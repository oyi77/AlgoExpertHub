<?php

namespace App\Providers;

use App\Support\AddonRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Conditionally register addon service providers based on their status
        $this->registerAddonServiceProviders();
        
    }
    
    public function boot()
    {
        Model::unguard();

        Paginator::useBootstrap();

        // Enable database query logging
        if (env('LOG_QUERIES', true)) {
            DB::listen(function ($query) {
                $sql = $query->sql;
                $bindings = $query->bindings;
                $time = $query->time;
                
                // Replace ? placeholders with actual values
                foreach ($bindings as $binding) {
                    $value = is_numeric($binding) ? $binding : "'{$binding}'";
                    $sql = preg_replace('/\?/', $value, $sql, 1);
                }
                
                Log::debug('Database Query', [
                    'sql' => $sql,
                    'time' => $time . 'ms',
                    'connection' => $query->connectionName,
                ]);
            });
        }

        // Global view composer to ensure $page is always available
        view()->composer('*', function ($view) {
            $data = $view->getData();
            if (!isset($data['page'])) {
                $view->with('page', null);
            }
            try {
                if (\App\Support\AddonRegistry::active('algoexpert-plus-addon') && \App\Support\AddonRegistry::moduleEnabled('algoexpert-plus-addon','seo')) {
                    if (class_exists(\Artesaos\SEOTools\Facades\SEOMeta::class)) {
                        \Artesaos\SEOTools\Facades\SEOMeta::setTitle($data['title'] ?? config('app.name'));
                        \Artesaos\SEOTools\Facades\SEOMeta::setCanonical(url()->current());
                    }
                    if (class_exists(\Artesaos\SEOTools\Facades\OpenGraph::class)) {
                        \Artesaos\SEOTools\Facades\OpenGraph::setTitle($data['title'] ?? config('app.name'));
                        \Artesaos\SEOTools\Facades\OpenGraph::setUrl(url()->current());
                    }
                }
            } catch (\Throwable $e) {
            }
        });


    }

    /**
     * Register addon service providers only if their addons are active.
     *
     * @return void
     */
    protected function registerAddonServiceProviders(): void
    {
        try {
            $addonProviders = [
                'ai-connection-addon' => \Addons\AiConnectionAddon\AddonServiceProvider::class,
                'multi-channel-signal-addon' => \Addons\MultiChannelSignalAddon\AddonServiceProvider::class,
                'trading-bot-signal-addon' => \Addons\TradingBotSignalAddon\AddonServiceProvider::class,
                // Consolidated into trading-management-addon:
                // - trading-execution-engine-addon (module: execution)
                // - copy-trading-addon (module: copy_trading)
                // - trading-preset-addon (module: risk_management)
                // - filter-strategy-addon (module: filter_strategy)
                // - ai-trading-addon (module: ai_analysis)
                // - smart-risk-management-addon (module: risk_management)
                'openrouter-integration-addon' => \Addons\OpenRouterIntegration\AddonServiceProvider::class,
                'trading-management-addon' => \Addons\TradingManagement\AddonServiceProvider::class, // Consolidated trading management
                'page-builder-addon' => \Addons\PageBuilderAddon\PageBuilderServiceProvider::class,
                'algoexpert-plus-addon' => \Addons\AlgoExpertPlus\AddonServiceProvider::class,
            ];

            foreach ($addonProviders as $addonSlug => $providerClass) {
                // Only register if class exists and addon is active
                if (class_exists($providerClass)) {
                    try {
                        if (AddonRegistry::active($addonSlug)) {
                            $this->app->register($providerClass);
                        }
                    } catch (\Exception $e) {
                        // If AddonRegistry fails, skip this addon silently
                        // This prevents errors during early bootstrap
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if addon registration encounters any issues
            // This ensures the application can still boot even if addon system has problems
        }
    }

}
