<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

/**
 * Menu Builder Helper
 * Builds user menu groups for sidebar reorganization
 */
class MenuBuilder
{
    /**
     * Build menu groups for user sidebar
     * Structure: HOME, USER SETUPS, TRADING, REPORTS, MARKETPLACES, WALLET, ACCOUNT
     */
    public static function buildUserMenuGroups(): array
    {
        $groups = [];

        // Check addon module status
        $multiChannelEnabled = \App\Support\AddonRegistry::active('multi-channel-signal-addon') 
            && \App\Support\AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'user_ui');
        $tradingManagementEnabled = \App\Support\AddonRegistry::active('trading-management-addon');
        $executionEngineEnabled = $tradingManagementEnabled 
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'execution');
        $copyTradingEnabled = $tradingManagementEnabled 
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'copy_trading')
            && $executionEngineEnabled;
        $tradingPresetEnabled = $tradingManagementEnabled 
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'risk_management');
        $filterStrategyEnabled = $tradingManagementEnabled 
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'filter_strategy');
        $aiTradingEnabled = $tradingManagementEnabled 
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'ai_analysis');
        $srmEnabled = $tradingManagementEnabled 
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'risk_management');

        // 0. HOME (Group 1)
        $home = [];
        if (Route::has('user.dashboard')) {
            $home[] = [
                'route' => 'user.dashboard',
                'label' => __('Dashboard'),
                'icon' => 'fas fa-home',
                'children' => null,
            ];
        }
        if (!empty($home)) {
            $groups[] = [
                'key' => 'home',
                'label' => __('HOME'),
                'icon' => 'fas fa-home',
                'items' => $home,
            ];
        }

        // 1. USER SETUPS
        $userSetups = [];
        
        // External Signal (new multi-tab page)
        if ($multiChannelEnabled && Route::has('user.external-signals.index')) {
            $userSetups[] = [
                'route' => 'user.external-signals.index',
                'label' => __('External Signal'),
                'icon' => 'fas fa-signal',
                'children' => null,
            ];
        }
        
        // Trade Accounts / My Connections
        if ($executionEngineEnabled && Route::has('user.execution-connections.index')) {
            $userSetups[] = [
                'route' => 'user.execution-connections.index',
                'label' => __('Trade Accounts'),
                'icon' => 'fas fa-exchange-alt',
                'children' => null,
            ];
        }
        
        // Trading Presets
        if ($tradingPresetEnabled) {
            $presetChildren = [];
            if (Route::has('user.trading-presets.index')) {
                $presetChildren[] = [
                    'route' => 'user.trading-presets.index',
                    'label' => __('My Presets'),
                    'icon' => null,
                ];
            }
            if (!empty($presetChildren)) {
                $userSetups[] = [
                    'route' => null,
                    'label' => __('Trading Presets'),
                    'icon' => 'fas fa-cog',
                    'children' => $presetChildren,
                ];
            }
        }
        
        // Filter Strategies
        if ($filterStrategyEnabled) {
            $filterChildren = [];
            if (Route::has('user.filter-strategies.index')) {
                $filterChildren[] = [
                    'route' => 'user.filter-strategies.index',
                    'label' => __('My Strategies'),
                    'icon' => null,
                ];
            }
            if (!empty($filterChildren)) {
                $userSetups[] = [
                    'route' => null,
                    'label' => __('Filter Strategies'),
                    'icon' => 'fas fa-filter',
                    'children' => $filterChildren,
                ];
            }
        }
        
        // AI Model Profiles
        if ($aiTradingEnabled) {
            $aiChildren = [];
            if (Route::has('user.ai-model-profiles.index')) {
                $aiChildren[] = [
                    'route' => 'user.ai-model-profiles.index',
                    'label' => __('My Profiles'),
                    'icon' => null,
                ];
            }
            if (!empty($aiChildren)) {
                $userSetups[] = [
                    'route' => null,
                    'label' => __('AI Model Profiles'),
                    'icon' => 'fas fa-robot',
                    'children' => $aiChildren,
                ];
            }
        }
        
        if (!empty($userSetups)) {
            $groups[] = [
                'key' => 'user_setups',
                'label' => __('USER SETUPS'),
                'icon' => 'fas fa-cog',
                'items' => $userSetups,
            ];
        }

        // 2. TRADING
        $trading = [];
        
        // Trading Overview (new - main entry point)
        if (Route::has('user.trading.overview')) {
            $trading[] = [
                'route' => 'user.trading.overview',
                'label' => __('Trading Overview'),
                'icon' => 'fas fa-chart-line',
                'children' => null,
            ];
        }
        
        // Copy Trading submenu
        if ($copyTradingEnabled) {
            $copyChildren = [];
            if (Route::has('user.copy-trading.settings')) {
                $copyChildren[] = ['route' => 'user.copy-trading.settings', 'label' => __('Settings'), 'icon' => null];
            }
            if (Route::has('user.copy-trading.traders.index')) {
                $copyChildren[] = ['route' => 'user.copy-trading.traders.index', 'label' => __('Browse Traders'), 'icon' => null];
            }
            if (Route::has('user.copy-trading.subscriptions.index')) {
                $copyChildren[] = ['route' => 'user.copy-trading.subscriptions.index', 'label' => __('My Subscriptions'), 'icon' => null];
            }
            if (Route::has('user.copy-trading.history.index')) {
                $copyChildren[] = ['route' => 'user.copy-trading.history.index', 'label' => __('History'), 'icon' => null];
            }
            if (!empty($copyChildren)) {
                $trading[] = [
                    'route' => null,
                    'label' => __('Copy Trading'),
                    'icon' => 'fas fa-copy',
                    'children' => $copyChildren,
                ];
            }
        }
        
        // Manual Trade
        if (Route::has('user.trade')) {
            $trading[] = [
                'route' => 'user.trade',
                'label' => __('Manual Trade'),
                'icon' => 'fas fa-hand-pointer',
                'children' => null,
            ];
        }
        
        if (!empty($trading)) {
            $groups[] = [
                'key' => 'trading',
                'label' => __('TRADING'),
                'icon' => 'fas fa-chart-line',
                'items' => $trading,
            ];
        }

        // 3. REPORTS
        $reports = [];
        
        // Dashboard / Overview (reuse Trading Analytics or SRM Dashboard)
        if ($executionEngineEnabled && Route::has('user.execution-analytics.index')) {
            $reports[] = [
                'route' => 'user.execution-analytics.index',
                'label' => __('Dashboard / Overview'),
                'icon' => 'fas fa-chart-pie',
                'children' => null,
            ];
        } elseif ($srmEnabled && Route::has('user.srm.dashboard')) {
            $reports[] = [
                'route' => 'user.srm.dashboard',
                'label' => __('Dashboard / Overview'),
                'icon' => 'fas fa-chart-pie',
                'children' => null,
            ];
        }
        
        // History submenu (trading execution logs, closed positions, etc.)
        $historyChildren = [];
        // Execution/position history (if exists)
        if ($executionEngineEnabled && Route::has('user.execution-positions.index')) {
            $historyChildren[] = ['route' => 'user.execution-positions.index', 'label' => __('Trade History'), 'icon' => null];
        }
        if ($executionEngineEnabled && Route::has('user.execution-logs.index')) {
            $historyChildren[] = ['route' => 'user.execution-logs.index', 'label' => __('Execution Logs'), 'icon' => null];
        }
        if ($copyTradingEnabled && Route::has('user.copy-trading.history.index')) {
            $historyChildren[] = ['route' => 'user.copy-trading.history.index', 'label' => __('Copy Trading History'), 'icon' => null];
        }
        if (!empty($historyChildren)) {
            $reports[] = [
                'route' => null,
                'label' => __('History'),
                'icon' => 'fas fa-history',
                'children' => $historyChildren,
            ];
        }
        
        // Adjustments (SRM/AI changes)
        if ($srmEnabled && Route::has('user.srm.adjustments.index')) {
            $reports[] = [
                'route' => 'user.srm.adjustments.index',
                'label' => __('Adjustments'),
                'icon' => 'fas fa-adjust',
                'children' => null,
            ];
        }
        
        // Analytics (P/L charts, winrate, etc.)
        if ($executionEngineEnabled && Route::has('user.execution-analytics.index')) {
            $reports[] = [
                'route' => 'user.execution-analytics.index',
                'label' => __('Analytics'),
                'icon' => 'fas fa-chart-bar',
                'children' => null,
            ];
        }
        
        // Insights (AI explanations)
        if ($srmEnabled && Route::has('user.srm.insights.index')) {
            $reports[] = [
                'route' => 'user.srm.insights.index',
                'label' => __('Insights'),
                'icon' => 'fas fa-lightbulb',
                'children' => null,
            ];
        }
        
        if (!empty($reports)) {
            $groups[] = [
                'key' => 'reports',
                'label' => __('REPORTS'),
                'icon' => 'fas fa-chart-bar',
                'items' => $reports,
            ];
        }

        // 4. MARKETPLACES
        $marketplaces = [];
        
        // Signal Marketplace (if exists - provider/traders/channel)
        // Note: May not exist yet, but placeholder for future
        
        // Preset Marketplace
        if ($tradingPresetEnabled && Route::has('user.trading-presets.marketplace')) {
            $marketplaces[] = [
                'route' => 'user.trading-presets.marketplace',
                'label' => __('Preset Marketplace'),
                'icon' => 'fas fa-store',
                'children' => null,
            ];
        }
        
        // Filter Strategy Marketplace
        if ($filterStrategyEnabled && Route::has('user.filter-strategies.marketplace')) {
            $marketplaces[] = [
                'route' => 'user.filter-strategies.marketplace',
                'label' => __('Filter Strategy Marketplace'),
                'icon' => 'fas fa-store',
                'children' => null,
            ];
        }
        
        // AI Model Marketplace
        if ($aiTradingEnabled && Route::has('user.ai-model-profiles.marketplace')) {
            $marketplaces[] = [
                'route' => 'user.ai-model-profiles.marketplace',
                'label' => __('AI Model Marketplace'),
                'icon' => 'fas fa-store',
                'children' => null,
            ];
        }
        
        // Copy Trading browse traders (as marketplace entry)
        if ($copyTradingEnabled && Route::has('user.copy-trading.traders.index')) {
            $marketplaces[] = [
                'route' => 'user.copy-trading.traders.index',
                'label' => __('Copy Trading Traders'),
                'icon' => 'fas fa-store',
                'children' => null,
            ];
        }
        
        if (!empty($marketplaces)) {
            $groups[] = [
                'key' => 'marketplaces',
                'label' => __('MARKETPLACES'),
                'icon' => 'fas fa-store',
                'items' => $marketplaces,
            ];
        }

        // 5. WALLET (Group 6)
        $wallet = [];
        
        // Deposit
        if (Route::has('user.deposit')) {
            $wallet[] = [
                'route' => 'user.deposit',
                'label' => __('Deposit'),
                'icon' => 'fas fa-arrow-down',
                'children' => null,
            ];
        }
        
        // Withdraw
        if (Route::has('user.withdraw')) {
            $wallet[] = [
                'route' => 'user.withdraw',
                'label' => __('Withdraw'),
                'icon' => 'fas fa-arrow-up',
                'children' => null,
            ];
        }
        
        // Transfer Money
        if (Route::has('user.transfer_money')) {
            $wallet[] = [
                'route' => 'user.transfer_money',
                'label' => __('Transfer Money'),
                'icon' => 'fas fa-exchange-alt',
                'children' => null,
            ];
        }
        
        // Transaction History submenu
        $transactionHistoryChildren = [];
        if (Route::has('user.deposit.log')) {
            $transactionHistoryChildren[] = ['route' => 'user.deposit.log', 'label' => __('Deposits'), 'icon' => null];
        }
        if (Route::has('user.withdraw.all')) {
            $transactionHistoryChildren[] = ['route' => 'user.withdraw.all', 'label' => __('Withdrawals'), 'icon' => null];
        }
        if (Route::has('user.transfer_money.log')) {
            $transactionHistoryChildren[] = ['route' => 'user.transfer_money.log', 'label' => __('Transfers'), 'icon' => null];
        }
        if (Route::has('user.receive_money.log')) {
            $transactionHistoryChildren[] = ['route' => 'user.receive_money.log', 'label' => __('Received'), 'icon' => null];
        }
        if (Route::has('user.transaction.log')) {
            $transactionHistoryChildren[] = ['route' => 'user.transaction.log', 'label' => __('Transactions'), 'icon' => null];
        }
        if (Route::has('user.commision')) {
            $transactionHistoryChildren[] = ['route' => 'user.commision', 'label' => __('Commissions'), 'icon' => null];
        }
        if (Route::has('user.invest.log')) {
            $transactionHistoryChildren[] = ['route' => 'user.invest.log', 'label' => __('Investments'), 'icon' => null];
        }
        if (Route::has('user.subscription')) {
            $transactionHistoryChildren[] = ['route' => 'user.subscription', 'label' => __('Subscriptions'), 'icon' => null];
        }
        if (!empty($transactionHistoryChildren)) {
            $wallet[] = [
                'route' => null,
                'label' => __('Transaction History'),
                'icon' => 'fas fa-history',
                'children' => $transactionHistoryChildren,
            ];
        }
        
        if (!empty($wallet)) {
            $groups[] = [
                'key' => 'wallet',
                'label' => __('WALLET'),
                'icon' => 'fas fa-wallet',
                'items' => $wallet,
            ];
        }

        // 6. ACCOUNT (Group 7)
        $account = [];
        
        // Plans & Subscriptions
        if (Route::has('user.plans')) {
            $account[] = [
                'route' => 'user.plans',
                'label' => __('Plans & Subscriptions'),
                'icon' => 'fas fa-clipboard-list',
                'children' => null,
            ];
        }
        
        // Referral Program
        if (Route::has('user.refferalLog')) {
            $account[] = [
                'route' => 'user.refferalLog',
                'label' => __('Referral Program'),
                'icon' => 'fas fa-user-friends',
                'children' => null,
            ];
        }
        
        // Profile Settings
        if (Route::has('user.profile')) {
            $account[] = [
                'route' => 'user.profile',
                'label' => __('Profile Settings'),
                'icon' => 'fas fa-user-cog',
                'children' => null,
            ];
        }
        
        // Support Tickets
        if (Route::has('user.ticket.index')) {
            $account[] = [
                'route' => 'user.ticket.index',
                'label' => __('Support Tickets'),
                'icon' => 'fas fa-ticket-alt',
                'children' => null,
            ];
        }
        
        // Notifications (optional, if route exists)
        if (Route::has('user.notifications.index')) {
            $account[] = [
                'route' => 'user.notifications.index',
                'label' => __('Notifications'),
                'icon' => 'fas fa-bell',
                'children' => null,
            ];
        }
        
        if (!empty($account)) {
            $groups[] = [
                'key' => 'account',
                'label' => __('ACCOUNT'),
                'icon' => 'fas fa-user',
                'items' => $account,
            ];
        }

        return $groups;
    }
}

