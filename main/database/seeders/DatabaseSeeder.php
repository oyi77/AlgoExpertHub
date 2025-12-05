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
            \Addons\TradingManagement\Database\Seeders\PrebuiltTradingBotSeeder::class,
        ]);
    }
}
