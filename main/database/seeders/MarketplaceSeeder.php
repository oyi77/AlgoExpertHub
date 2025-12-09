<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class MarketplaceSeeder extends Seeder
{
    public function run()
    {
        // Check if tables exist
        $requiredTables = ['bot_templates', 'template_backtests', 'signal_source_templates', 'complete_bots', 'trader_profiles'];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->command->warn("Marketplace table '{$table}' not found. Skipping marketplace seeding.");
                return;
            }
        }

        // Check if models exist
        if (!class_exists(\Addons\TradingManagement\Modules\Marketplace\Models\BotTemplate::class)) {
            $this->command->warn('Marketplace models not found. Skipping marketplace seeding.');
            return;
        }

        $BotTemplate = \Addons\TradingManagement\Modules\Marketplace\Models\BotTemplate::class;
        $SignalSourceTemplate = \Addons\TradingManagement\Modules\Marketplace\Models\SignalSourceTemplate::class;
        $CompleteBot = \Addons\TradingManagement\Modules\Marketplace\Models\CompleteBot::class;
        $TemplateBacktest = \Addons\TradingManagement\Modules\Marketplace\Models\TemplateBacktest::class;
        $TraderProfile = \Addons\TradingManagement\Modules\Marketplace\Models\TraderProfile::class;
        // Create sample bot templates
        $categories = ['grid', 'dca', 'martingale', 'scalping', 'trend_following'];
        for ($i = 1; $i <= 20; $i++) {
            $bot = $BotTemplate::create([
                'user_id' => User::inRandomOrder()->first()?->id,
                'name' => "Bot Template #{$i}",
                'description' => "High-performance {$categories[array_rand($categories)]} bot with proven results",
                'category' => $categories[array_rand($categories)],
                'config' => ['risk' => 1.5, 'max_positions' => 3],
                'is_public' => true,
                'is_featured' => $i <= 5,
                'price' => $i % 3 == 0 ? 49.99 : 0,
                'avg_rating' => rand(35, 50) / 10,
                'total_ratings' => rand(10, 200),
            ]);

            $TemplateBacktest::create([
                'template_type' => 'bot',
                'template_id' => $bot->id,
                'capital_initial' => 10000,
                'capital_final' => 10000 + rand(2000, 50000),
                'net_profit_percent' => rand(20, 500),
                'win_rate' => rand(60, 85),
                'profit_factor' => rand(15, 30) / 10,
                'max_drawdown' => rand(5, 20),
                'total_trades' => rand(100, 1000),
                'winning_trades' => rand(60, 800),
                'losing_trades' => rand(40, 200),
                'backtest_period_start' => now()->subYear(),
                'backtest_period_end' => now(),
                'symbols_tested' => ['BTC/USDT', 'ETH/USDT'],
                'timeframes_tested' => ['1H', '4H'],
            ]);
        }

        // Signal sources
        for ($i = 1; $i <= 15; $i++) {
            $SignalSourceTemplate::create([
                'user_id' => User::inRandomOrder()->first()?->id,
                'name' => "Signal Source #{$i}",
                'description' => "Professional signal channel with 75%+ accuracy",
                'source_type' => ['telegram', 'api', 'firebase'][array_rand(['telegram', 'api', 'firebase'])],
                'config' => ['channel' => '@signalchannel', 'parser' => 'regex'],
                'is_public' => true,
                'is_featured' => $i <= 3,
                'price' => $i % 4 == 0 ? 29.99 : 0,
                'avg_rating' => rand(35, 48) / 10,
                'total_ratings' => rand(5, 150),
            ]);
        }

        // Complete bots
        for ($i = 1; $i <= 10; $i++) {
            $bot = $CompleteBot::create([
                'user_id' => User::inRandomOrder()->first()?->id,
                'name' => "Complete Bot #{$i}",
                'description' => "Fully automated trading system with indicators and rules",
                'indicators_config' => ['EMA' => [20, 50], 'RSI' => 14],
                'entry_rules' => ['ema_cross' => true, 'rsi_oversold' => 30],
                'exit_rules' => ['sl_percent' => 2, 'tp_percent' => 4],
                'risk_config' => ['risk_percent' => 1],
                'is_public' => true,
                'is_featured' => $i <= 2,
                'price' => $i % 2 == 0 ? 99.99 : 0,
                'avg_rating' => rand(38, 50) / 10,
                'total_ratings' => rand(15, 100),
            ]);

            $TemplateBacktest::create([
                'template_type' => 'complete',
                'template_id' => $bot->id,
                'capital_initial' => 10000,
                'capital_final' => 10000 + rand(3000, 70000),
                'net_profit_percent' => rand(30, 700),
                'win_rate' => rand(65, 90),
                'profit_factor' => rand(18, 35) / 10,
                'max_drawdown' => rand(4, 15),
                'total_trades' => rand(200, 2000),
                'winning_trades' => rand(150, 1800),
                'losing_trades' => rand(50, 200),
                'backtest_period_start' => now()->subYear(),
                'backtest_period_end' => now(),
                'symbols_tested' => ['BTC/USDT', 'ETH/USDT', 'EUR/USD'],
                'timeframes_tested' => ['1H', '4H', '1D'],
            ]);
        }

        // Trader profiles
        $users = User::limit(20)->get();
        foreach ($users as $user) {
            $TraderProfile::create([
                'user_id' => $user->id,
                'display_name' => $user->username,
                'bio' => "Professional trader with 5+ years experience",
                'is_public' => true,
                'accepts_followers' => true,
                'subscription_price' => rand(0, 1) ? 0 : rand(20, 100),
                'total_profit_percent' => rand(50, 500),
                'win_rate' => rand(60, 85),
                'avg_monthly_return' => rand(5, 25),
                'max_drawdown' => rand(5, 20),
                'trades_count' => rand(100, 1000),
                'verified' => rand(0, 1),
            ]);
        }

        $this->command->info('Marketplace data seeded successfully!');
    }
}


