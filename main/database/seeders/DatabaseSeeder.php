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
            ConfigurationSeeder::class,
            AdminSeeder::class,
            RolePermission::class,  // After AdminSeeder so admin exists
            LanguageSeeder::class,
            GatewaySeeder::class,
            WithdrawGatewaySeeder::class,
            EmailTemplateSeeder::class,
            CurrencyPairSeeder::class,
            TimeFrameSeeder::class,
            MarketSeeder::class,
            PlanSeeder::class,
            PageSeeder::class,
            ContentSeeder::class,
            ReferralSeeder::class,
            AIProviderSeeder::class,
            ParsingPatternSeeder::class,
            TradingPresetSeeder::class,
            \Addons\TradingManagement\Database\Seeders\PrebuiltTradingBotSeeder::class,
        ]);
    }
}
