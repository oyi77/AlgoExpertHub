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
                
                // Ensure support menu always exists and has items (core feature)
                if (!isset($menu['support'])) {
                    $menu['support'] = [
                        'label' => __('SUPPORT'),
                        'icon' => 'fas fa-life-ring',
                        'items' => $this->getSupportMenuItems(),
                    ];
                } elseif (empty($menu['support']['items']) || !is_array($menu['support']['items']) || count($menu['support']['items']) === 0) {
                    $menu['support']['items'] = $this->getSupportMenuItems();
                }
                
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

        // Terminal - Always include for all users
        if (Route::has('user.terminal.index')) {
            $items[] = [
                'route' => 'user.terminal.index',
                'label' => __('Terminal'),
                'icon' => 'fas fa-chart-line',
                'tooltip' => __('Trading terminal'),
            ];
        }

        // Signals - Always include for all users
        if (Route::has('user.signals.index')) {
            $items[] = [
                'route' => 'user.signals.index',
                'label' => __('Signals'),
                'icon' => 'fas fa-broadcast-tower',
                'tooltip' => __('Trading signals'),
            ];
        }

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
                'label' => __('Signal Sources'),
                'icon' => 'fas fa-network-wired',
                'tooltip' => __('Manage signal sources and channel forwarding'),
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

        // Help Center - Always include (route exists in trading.php)
        // Route is: user.help.index (defined in routes/web/trading.php)
        $items[] = [
            'route' => 'user.help.index',
            'label' => __('Help Center'),
            'icon' => 'fas fa-book',
            'tooltip' => __('User guides and documentation'),
        ];

        // Support Tickets - Always include (core feature, resource route always exists)
        // Resource route 'ticket' creates 'user.ticket.index'
        $items[] = [
            'route' => 'user.ticket.index',
            'label' => __('Support Tickets'),
            'icon' => 'fas fa-ticket-alt',
            'tooltip' => __('Technical support'),
        ];

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

        // Investment (submenu)
        $investmentItems = [];
        if (Route::has('user.invest.all')) {
            $investmentItems[] = [
                'route' => 'user.invest.all',
                'label' => __('All Investments'),
                'icon' => 'fas fa-chart-line',
            ];
        }
        if (Route::has('user.invest.pending')) {
            $investmentItems[] = [
                'route' => 'user.invest.pending',
                'label' => __('Pending Investments'),
                'icon' => 'fas fa-clock',
            ];
        }
        if (Route::has('user.invest.log')) {
            $investmentItems[] = [
                'route' => 'user.invest.log',
                'label' => __('Investment Log'),
                'icon' => 'fas fa-list',
            ];
        }
        if (Route::has('user.interest.log')) {
            $investmentItems[] = [
                'route' => 'user.interest.log',
                'label' => __('Interest Log'),
                'icon' => 'fas fa-percentage',
            ];
        }

        if (!empty($investmentItems)) {
            $items[] = [
                'label' => __('Investment'),
                'icon' => 'fas fa-piggy-bank',
                'type' => 'submenu',
                'children' => $investmentItems,
            ];
        }

        // Withdraw History (submenu)
        $withdrawItems = [];
        if (Route::has('user.withdraw.all')) {
            $withdrawItems[] = [
                'route' => 'user.withdraw.all',
                'label' => __('All Withdrawals'),
                'icon' => 'fas fa-list',
            ];
        }
        if (Route::has('user.withdraw.pending')) {
            $withdrawItems[] = [
                'route' => 'user.withdraw.pending',
                'label' => __('Pending Withdrawals'),
                'icon' => 'fas fa-clock',
            ];
        }
        if (Route::has('user.withdraw.complete')) {
            $withdrawItems[] = [
                'route' => 'user.withdraw.complete',
                'label' => __('Completed Withdrawals'),
                'icon' => 'fas fa-check-circle',
            ];
        }

        if (!empty($withdrawItems)) {
            $items[] = [
                'label' => __('Withdraw History'),
                'icon' => 'fas fa-money-bill-wave',
                'type' => 'submenu',
                'children' => $withdrawItems,
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
        // Trading menu visibility - show Terminal and Signals for all users
        // Other trading menus based on onboarding progress
        
        // Always keep trading_console, market_analysis, marketplace sections
        // but can filter items inside based on plan status
        
        // Filter trading console items based on plan
        if (isset($menu['trading_console']['items'])) {
            $planBasedRoutes = ['user.trading.operations.index', 'user.trading.multi-channel-signal.index'];
            $menu['trading_console']['items'] = array_filter($menu['trading_console']['items'], function($item) use ($user, $onboardingService, $planBasedRoutes) {
                $route = $item['route'] ?? '';
                // Skip plan-based routes if user has no active plan
                if (in_array($route, $planBasedRoutes) && !$onboardingService->hasActivePlan($user)) {
                    return false;
                }
                return true;
            });
            // Re-index array
            $menu['trading_console']['items'] = array_values($menu['trading_console']['items']);
        }
        
        // Filter market_analysis items based on plan
        if (isset($menu['market_analysis']['items'])) {
            $planBasedRoutes = ['user.trading.execution-log.index', 'user.trading.backtesting.index'];
            $menu['market_analysis']['items'] = array_filter($menu['market_analysis']['items'], function($item) use ($user, $onboardingService, $planBasedRoutes) {
                $route = $item['route'] ?? '';
                if (in_array($route, $planBasedRoutes) && !$onboardingService->hasActivePlan($user)) {
                    return false;
                }
                return true;
            });
            $menu['market_analysis']['items'] = array_values($menu['market_analysis']['items']);
        }
        
        // Filter marketplace items based on plan
        if (isset($menu['marketplace']['items'])) {
            $planBasedRoutes = ['user.trading.marketplaces.index'];
            $menu['marketplace']['items'] = array_filter($menu['marketplace']['items'], function($item) use ($user, $onboardingService, $planBasedRoutes) {
                $route = $item['route'] ?? '';
                if (in_array($route, $planBasedRoutes) && !$onboardingService->hasActivePlan($user)) {
                    return false;
                }
                return true;
            });
            $menu['marketplace']['items'] = array_values($menu['marketplace']['items']);
        }
        
        // Remove empty sections
        $sectionsToCheck = ['trading_console', 'market_analysis', 'marketplace'];
        foreach ($sectionsToCheck as $section) {
            if (!isset($menu[$section]['items']) || empty($menu[$section]['items'])) {
                unset($menu[$section]);
            }
        }
        
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

