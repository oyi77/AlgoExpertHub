<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;

class TestFilterStrategySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if Test filter already exists
        $existingTest = FilterStrategy::where('filter_type', 'test')->first();
        
        if (!$existingTest) {
            FilterStrategy::create([
                'name' => 'Test Mode (Immediate Trade)',
                'filter_type' => 'test',
                'description' => 'Testing filter that immediately executes buy/sell orders to test if trading bot is working correctly. Use this to verify your bot connection and order execution.',
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'config' => [
                    'indicators' => [],
                    'rules' => [],
                    'test_mode' => true,
                    'note' => 'This filter bypasses all technical analysis and immediately triggers trades for testing purposes.',
                ],
            ]);

            $this->command->info('✅ Test filter strategy created successfully');
        } else {
            $this->command->info('ℹ️  Test filter strategy already exists');
        }

        // Also create a "No Filter" strategy for users who want to skip analysis
        $existingNone = FilterStrategy::where('filter_type', 'none')->first();
        
        if (!$existingNone) {
            FilterStrategy::create([
                'name' => 'No Filter (Skip Analysis)',
                'filter_type' => 'none',
                'description' => 'This filter skips all technical indicator calculations. Use when you want the bot to monitor but not analyze market data.',
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'config' => [
                    'indicators' => [],
                    'rules' => [],
                ],
            ]);

            $this->command->info('✅ No Filter strategy created successfully');
        } else {
            $this->command->info('ℹ️  No Filter strategy already exists');
        }
    }
}
