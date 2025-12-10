<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // Core configuration
            ConfigurationSeeder::class,
            AdminSeeder::class,
            RolePermission::class,  // After AdminSeeder so admin exists
            LanguageSeeder::class,
            
            // Payment & Gateway setup
            GatewaySeeder::class,
            WithdrawGatewaySeeder::class,
            
            // Content & Pages
            EmailTemplateSeeder::class,
            PageSeeder::class,
            ContentSeeder::class,
            
            // Trading setup
            CurrencyPairSeeder::class,
            TimeFrameSeeder::class,
            MarketSeeder::class,
            PlanSeeder::class,
            TradingPresetSeeder::class,
            
            // Demo data for investor presentations
            UserSeeder::class,              // Demo users
            SignalSeeder::class,            // Trading signals
            PaymentSeeder::class,           // Payments/subscriptions
            DepositSeeder::class,           // Deposits
            WithdrawSeeder::class,          // Withdrawals
            TransactionSeeder::class,       // All transactions
            PlanSubscriptionSeeder::class,   // Active subscriptions
            NotificationSeeder::class,      // Notifications
            
            // Referrals
            ReferralSeeder::class,
            
            // AI & Addons
            AIProviderSeeder::class,
            ParsingPatternSeeder::class,
            
            // Addon Features (conditional - will skip if models don't exist)
            AiConnectionSeeder::class,        // AI Connections (after AIProviderSeeder)
            AiModelProfileSeeder::class,      // AI Model Profiles
            FilterStrategySeeder::class,      // Filter Strategies
            RulebookEaStrategySeeder::class,  // RULEBOOK EA Multi-Timeframe Strategy (after FilterStrategySeeder)
            ChannelSourceSeeder::class,        // Channel Sources
            SignalAnalyticSeeder::class,        // Signal Analytics (after ChannelSourceSeeder, SignalSeeder)
            ExecutionConnectionSeeder::class,  // Execution Connections
            ExecutionLogSeeder::class,        // Execution Logs (after ExecutionConnectionSeeder)
            ExecutionPositionSeeder::class,   // Execution Positions (after ExecutionLogSeeder)
            CopyTradingSeeder::class,          // Copy Trading Settings
            CopyTradingSubscriptionSeeder::class, // Copy Trading Subscriptions (after CopyTradingSeeder, users, connections)
            BacktestSeeder::class,            // Backtests (after FilterStrategySeeder, AiModelProfileSeeder, TradingPresetSeeder)
            AiUsageAnalyticsSeeder::class,    // AI Usage Analytics (after AiConnectionSeeder)
            MarketplaceSeeder::class,         // Marketplace (bot templates, trader profiles, backtests)
        ]);

        // Conditional addon seeders (only if addon is active and class exists)
        try {
            $seederClass = \Addons\TradingManagement\Database\Seeders\PrebuiltTradingBotSeeder::class;
            if (class_exists($seederClass) && \App\Support\AddonRegistry::active('trading-management-addon')) {
                $this->call($seederClass);
            }
        } catch (\Exception $e) {
            // Silently skip if addon is not active, class doesn't exist, or registry fails
            // This prevents seeding from failing when addon is not installed/active
        }
    }
}
