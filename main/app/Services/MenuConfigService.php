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
            'trading' => [
                'label' => __('TRADING'),
                'icon' => 'fas fa-chart-line',
                'items' => $this->getTradingMenuItems(),
            ],
            'account' => [
                'label' => __('ACCOUNT'),
                'icon' => 'fas fa-user-circle',
                'items' => $this->getAccountMenuItems(),
            ],
        ];
    }

    /**
     * Get menu items for a specific user (with progressive disclosure)
     * 
     * @param User $user
     * @return array
     */
    public function getMenuForUser(User $user): array
    {
        // Cache menu structure per user
        $cacheKey = 'user_menu_' . $user->id;
        
        return Cache::tags(['menu', 'user_' . $user->id])
            ->remember($cacheKey, 3600, function () use ($user) {
                $menu = $this->getUserMenu();
                
                // Apply progressive disclosure
                $menu = $this->applyProgressiveDisclosure($menu, $user);
                
                // Allow addons to inject menu items
                $menu = $this->injectAddonMenus($menu, $user);
                
                return $menu;
            });
    }

    /**
     * Get trading menu items
     * 
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
                'executions' => __('Executions'),
                'open-positions' => __('Open Positions'),
                'closed-positions' => __('Closed Positions'),
                'analytics' => __('Analytics'),
                'trading-bots' => __('Trading Bots'),
            ],
        ];

        // Trading Configuration (unified page)
        $items[] = [
            'route' => 'user.trading.configuration.index',
            'label' => __('Trading Configuration'),
            'icon' => 'fas fa-cog',
            'tooltip' => __('Configure risk presets, filter strategies, and AI model profiles'),
            'type' => 'unified_page',
            'tabs' => [
                'data-connections' => __('Data Connections'),
                'risk-presets' => __('Risk Presets'),
                'smart-risk' => __('Smart Risk Management'),
                'filter-strategies' => __('Filter Strategies'),
                'ai-profiles' => __('AI Model Profiles'),
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
        
        // Hide trading menu if user has no active plan
        if (!$onboardingService->hasActivePlan($user)) {
            unset($menu['trading']);
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
        Cache::tags(['menu', 'user_' . $user->id])->flush();
    }

    /**
     * Clear menu cache for all users
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        Cache::tags(['menu'])->flush();
    }
}

