<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Menu Configuration Service
 * Centralized menu structure management for user panel
 */
class MenuConfigService
{
    /**
     * Get user menu structure
     * 
     * @return array
     */
    public function getUserMenu(): array
    {
        return [
            'home' => [
                'label' => __('HOME'),
                'icon' => 'fas fa-home',
                'items' => [
                    [
                        'route' => 'user.dashboard',
                        'label' => __('Dashboard'),
                        'icon' => 'fas fa-home',
                    ],
                ],
            ],
            'trading_console' => [
                'label' => __('TRADING CONSOLE'),
                'icon' => 'fas fa-chart-line',
                'items' => $this->getTradingConsoleMenuItems(),
            ],
            'market_analysis' => [
                'label' => __('MARKET & ANALYSIS'),
                'icon' => 'fas fa-brain',
                'items' => $this->getMarketAnalysisMenuItems(),
            ],
            'marketplace' => [
                'label' => __('MARKETPLACE'),
                'icon' => 'fas fa-store',
                'items' => $this->getMarketplaceMenuItems(),
            ],
            'account' => [
                'label' => __('ACCOUNT'),
                'icon' => 'fas fa-user-circle',
                'items' => $this->getAccountMenuItems(),
            ],
            'support' => [
                'label' => __('SUPPORT'),
                'icon' => 'fas fa-life-ring',
                'items' => $this->getSupportMenuItems(),
            ],
        ];
    }

    /**
     * Get menu items for a specific user (with progressive disclosure)
     * 
     * @param User $user
     * @return array
     */
    public function getMenuForUser(User $user, bool $forceRefresh = false): array
    {
        // Cache menu structure per user
        $cacheKey = 'user_menu_' . $user->id;
        
        // Force refresh if requested (for debugging)
        if ($forceRefresh) {
            Cache::tags(['menu', 'user_' . $user->id])->forget($cacheKey);
            Cache::forget($cacheKey);
        }
        
        return Cache::tags(['menu', 'user_' . $user->id])
            ->remember($cacheKey, 3600, function () use ($user) {
                $menu = $this->getUserMenu();
                
                // Apply progressive disclosure
                $menu = $this->applyProgressiveDisclosure($menu, $user);
                
                // Allow addons to inject menu items
                $menu = $this->injectAddonMenus($menu, $user);
                
                // Filter out Trading Configuration menu if it exists (safety check)
                if (isset($menu['trading_console']['items'])) {
                    $menu['trading_console']['items'] = array_filter($menu['trading_console']['items'], function($item) {
                        $route = $item['route'] ?? '';
                        return $route !== 'user.trading.configuration.index';
                    });
                    // Re-index array
                    $menu['trading_console']['items'] = array_values($menu['trading_console']['items']);
                }
                
                // Also handle legacy 'trading' key if it exists
                if (isset($menu['trading']['items'])) {
                    $menu['trading']['items'] = array_filter($menu['trading']['items'], function($item) {
                        $route = $item['route'] ?? '';
                        return $route !== 'user.trading.configuration.index';
                    });
                    // Re-index array
                    $menu['trading']['items'] = array_values($menu['trading']['items']);
                }
                
                return $menu;
            });
    }

    /**
     * Get trading console menu items (new design)
     * 
     * @return array
     */
    public function getTradingConsoleMenuItems(): array
    {
        $items = [];

        // My Bots
        if (Route::has('user.trading.operations.index')) {
            $items[] = [
                'route' => 'user.trading.operations.index',
                'label' => __('My Bots'),
                'icon' => 'fas fa-robot',
                'tooltip' => __('Manage all trading bots'),
            ];
        }

        // Manual Trading (placeholder route - to be implemented)
        // Will be enabled when manual trading feature is implemented
        if (Route::has('user.manual-trading.index')) {
            $items[] = [
                'route' => 'user.manual-trading.index',
                'label' => __('Manual Trading'),
                'icon' => 'fas fa-hand-pointer',
                'tooltip' => __('Execute manual trades'),
            ];
        }

        // Multi-Channel Signal
        if (Route::has('user.trading.multi-channel-signal.index')) {
            $items[] = [
                'route' => 'user.trading.multi-channel-signal.index',
                'label' => __('Signal Center'),
                'icon' => 'fas fa-broadcast-tower',
                'tooltip' => __('Signal monitoring and history'),
            ];
        }

        // Risk Management
        if (Route::has('user.trading.configurations.index')) {
            $items[] = [
                'route' => 'user.trading.configurations.index',
                'label' => __('Risk Management'),
                'icon' => 'fas fa-shield-alt',
                'tooltip' => __('Risk parameters and monitoring'),
            ];
        }

        return $items;
    }

    /**
     * Get market & analysis menu items (new design)
     * 
     * @return array
     */
    public function getMarketAnalysisMenuItems(): array
    {
        $items = [];

        // AI Market Insights (placeholder - to be implemented)
        if (Route::has('user.ai.market-insights')) {
            $items[] = [
                'route' => 'user.ai.market-insights',
                'label' => __('AI Market Insights'),
                'icon' => 'fas fa-brain',
                'tooltip' => __('AI analysis and market confirmation'),
            ];
        }

        // Performance Analytics
        if (Route::has('user.trading.execution-log.index')) {
            $items[] = [
                'route' => 'user.trading.execution-log.index',
                'label' => __('Performance Analytics'),
                'icon' => 'fas fa-chart-bar',
                'tooltip' => __('Detailed performance reports'),
            ];
        }

        // Backtesting
        if (Route::has('user.trading.backtesting.index')) {
            $items[] = [
                'route' => 'user.trading.backtesting.index',
                'label' => __('Backtesting Center'),
                'icon' => 'fas fa-history',
                'tooltip' => __('Strategy backtesting'),
            ];
        }

        return $items;
    }

    /**
     * Get marketplace menu items (new design)
     * 
     * @return array
     */
    public function getMarketplaceMenuItems(): array
    {
        $items = [];

        // Preset Marketplace
        if (Route::has('user.trading.marketplaces.index')) {
            $items[] = [
                'route' => 'user.trading.marketplaces.index',
                'label' => __('Preset Marketplace'),
                'icon' => 'fas fa-store',
                'tooltip' => __('Trading preset templates'),
                'type' => 'marketplace',
                'categories' => [
                    'presets' => __('Trading Presets'),
                    'strategies' => __('Strategies'),
                    'ai-profiles' => __('AI Profiles'),
                    'bots' => __('Bots'),
                ],
            ];
        }

        // Bot Marketplace (future feature)
        if (Route::has('user.bot-marketplace.index')) {
            $items[] = [
                'route' => 'user.bot-marketplace.index',
                'label' => __('Bot Marketplace'),
                'icon' => 'fas fa-shopping-cart',
                'tooltip' => __('Pre-built bots'),
            ];
        }

        return $items;
    }

    /**
     * Get support menu items (new design)
     * 
     * @return array
     */
    public function getSupportMenuItems(): array
    {
        $items = [];

        // Help Docs (placeholder - to be implemented)
        if (Route::has('user.help.docs')) {
            $items[] = [
                'route' => 'user.help.docs',
                'label' => __('Help Docs'),
                'icon' => 'fas fa-book',
                'tooltip' => __('User guides'),
            ];
        }

        // Support Tickets
        if (Route::has('user.ticket.index')) {
            $items[] = [
                'route' => 'user.ticket.index',
                'label' => __('Support Tickets'),
                'icon' => 'fas fa-ticket-alt',
                'tooltip' => __('Technical support'),
            ];
        }

        return $items;
    }

    /**
     * Get trading menu items (legacy method - kept for backward compatibility)
     * @deprecated Use getTradingConsoleMenuItems() instead
     * @return array
     */
    public function getTradingMenuItems(): array
    {
        $items = [];

        // Always include trading menu items (routes are registered)
        // Multi-Channel Signal (direct link, no submenu - tabs are on the page)
        $items[] = [
            'route' => 'user.trading.multi-channel-signal.index',
            'label' => __('Multi-Channel Signal'),
            'icon' => 'fas fa-signal',
            'tooltip' => __('Manage signal sources, channel forwarding, and review auto-created signals'),
        ];

        // Trading Operations (unified page)
        $items[] = [
            'route' => 'user.trading.operations.index',
            'label' => __('Trading Operations'),
            'icon' => 'fas fa-bolt',
            'tooltip' => __('Manage connections, monitor positions, and view trading analytics'),
            'type' => 'unified_page',
            'tabs' => [
                'connections' => __('Connections'),
                'trading-bots' => __('Trading Bots'),
            ],
            'children' => [
                [
                    'route' => 'user.trading.execution-log.index',
                    'label' => __('Execution Log'),
                    'icon' => 'fas fa-list',
                    'tooltip' => __('View all trade execution logs and monitor execution status'),
                ],
                [
                    'route' => 'user.trading.configurations.index',
                    'label' => __('Configurations'),
                    'icon' => 'fas fa-cog',
                    'tooltip' => __('Configure risk presets, filter strategies, and AI model profiles'),
                ],
            ],
        ];

        // Backtesting (unified page)
        $items[] = [
            'route' => 'user.trading.backtesting.index',
            'label' => __('Backtesting'),
            'icon' => 'fas fa-flask',
            'tooltip' => __('Create backtests, view results, and analyze performance'),
            'type' => 'unified_page',
            'tabs' => [
                'create' => __('Create Backtest'),
                'results' => __('Results'),
                'reports' => __('Performance Reports'),
            ],
        ];

        // Marketplaces (unified page - no submenu, tabs are on the page)
        $items[] = [
            'route' => 'user.trading.marketplaces.index',
            'label' => __('Marketplaces'),
            'icon' => 'fas fa-store',
            'tooltip' => __('Browse and clone trading presets, strategies, AI profiles, and bots'),
            'type' => 'unified_page',
        ];

        return $items;
    }

    /**
     * Get account menu items
     * 
     * @return array
     */
    protected function getAccountMenuItems(): array
    {
        $items = [];

        // My Subscription
        if (Route::has('user.subscription')) {
            $items[] = [
                'route' => 'user.subscription',
                'label' => __('My Subscription'),
                'icon' => 'fas fa-id-card',
            ];
        }

        // Plans
        if (Route::has('user.plans')) {
            $items[] = [
                'route' => 'user.plans',
                'label' => __('Plans'),
                'icon' => 'fas fa-clipboard-list',
            ];
        }

        // Wallet (submenu)
        $walletItems = [];
        if (Route::has('user.deposit')) {
            $walletItems[] = [
                'route' => 'user.deposit',
                'label' => __('Deposit'),
                'icon' => 'fas fa-credit-card',
            ];
        }
        if (Route::has('user.withdraw')) {
            $walletItems[] = [
                'route' => 'user.withdraw',
                'label' => __('Withdraw'),
                'icon' => 'fas fa-hand-holding-usd',
            ];
        }
        if (Route::has('user.transfer_money')) {
            $walletItems[] = [
                'route' => 'user.transfer_money',
                'label' => __('Transfer Money'),
                'icon' => 'fas fa-exchange-alt',
            ];
        }
        if (Route::has('user.transaction.log')) {
            $walletItems[] = [
                'route' => 'user.transaction.log',
                'label' => __('Transaction History'),
                'icon' => 'fas fa-history',
            ];
        }

        if (!empty($walletItems)) {
            $items[] = [
                'label' => __('Wallet'),
                'icon' => 'fas fa-wallet',
                'type' => 'submenu',
                'children' => $walletItems,
            ];
        }

        // Profile Settings
        if (Route::has('user.profile')) {
            $items[] = [
                'route' => 'user.profile',
                'label' => __('Profile Settings'),
                'icon' => 'fas fa-user-cog',
            ];
        }

        // Referral Log
        if (Route::has('user.refferalLog')) {
            $items[] = [
                'route' => 'user.refferalLog',
                'label' => __('Referral Log'),
                'icon' => 'fas fa-user-friends',
            ];
        }

        // Support Ticket
        if (Route::has('user.ticket.index')) {
            $items[] = [
                'route' => 'user.ticket.index',
                'label' => __('Support Ticket'),
                'icon' => 'fas fa-ticket-alt',
            ];
        }

        return $items;
    }

    /**
     * Apply progressive disclosure based on user onboarding progress
     * 
     * @param array $menu
     * @param User $user
     * @return array
     */
    public function applyProgressiveDisclosure(array $menu, User $user): array
    {
        $onboardingService = app(UserOnboardingService::class);
        
        // Always show home and account menus
        // Trading menu visibility based on onboarding progress
        
        // Hide trading_console menu if user has no active plan (renamed from 'trading')
        if (!$onboardingService->hasActivePlan($user)) {
            unset($menu['trading_console']);
            unset($menu['market_analysis']);
            unset($menu['marketplace']);
            return $menu;
        }

        // Show all trading menu items if user has active plan
        // Progressive disclosure is handled via onboarding checklist, not menu hiding
        // This allows users to see all available features and guides them via onboarding
        
        return $menu;
    }

    /**
     * Inject addon menu items
     * 
     * @param array $menu
     * @param User $user
     * @return array
     */
    public function injectAddonMenus(array $menu, User $user): array
    {
        // Allow addons to inject menu items via event
        // This is a placeholder for future addon integration
        // Addons can listen to MenuBuilding event and inject items
        
        return $menu;
    }

    /**
     * Clear menu cache for a user
     * 
     * @param User $user
     * @return void
     */
    public function clearCache(User $user): void
    {
        $cacheKey = 'user_menu_' . $user->id;
        Cache::forget($cacheKey);
        Cache::tags(['menu', 'user_' . $user->id])->forget($cacheKey);
        Cache::tags(['menu', 'user_' . $user->id])->flush();
    }

    /**
     * Clear menu cache for all users
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        // Clear all menu-related cache
        Cache::tags(['menu'])->flush();
        // Also try to clear by pattern (if using file cache)
        try {
            $cacheDir = storage_path('framework/cache/data');
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*user_menu*');
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
    }
}

