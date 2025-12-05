<?php

namespace App\Providers;

use App\Support\AddonRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;

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

        // Global view composer to ensure $page is always available
        view()->composer('*', function ($view) {
            $data = $view->getData();
            if (!isset($data['page'])) {
                $view->with('page', null);
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
                'trading-execution-engine-addon' => \Addons\TradingExecutionEngine\AddonServiceProvider::class,
                'copy-trading-addon' => \Addons\CopyTrading\AddonServiceProvider::class,
                'trading-preset-addon' => \Addons\TradingPresetAddon\AddonServiceProvider::class,
                'filter-strategy-addon' => \Addons\FilterStrategyAddon\AddonServiceProvider::class,
                'ai-trading-addon' => \Addons\AiTradingAddon\AddonServiceProvider::class,
                'openrouter-integration-addon' => \Addons\OpenRouterIntegration\AddonServiceProvider::class,
                'smart-risk-management-addon' => \Addons\SmartRiskManagement\AddonServiceProvider::class,
                'trading-management-addon' => \Addons\TradingManagement\AddonServiceProvider::class, // NEW: Consolidated trading management
                'page-builder-addon' => \Addons\PageBuilderAddon\PageBuilderServiceProvider::class,
                'advanced-theme-addon' => \Addons\AdvancedThemeAddon\AddonServiceProvider::class,
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
