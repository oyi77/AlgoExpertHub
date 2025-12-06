<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CopyTradingSubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo copy trading subscriptions for trading operations dashboard
     */
    public function run()
    {
        // Check which model class exists
        $modelClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class)) {
            $modelClass = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class;
        } elseif (class_exists(\Addons\CopyTrading\App\Models\CopyTradingSubscription::class)) {
            $modelClass = \Addons\CopyTrading\App\Models\CopyTradingSubscription::class;
        }

        if (!$modelClass) {
            $this->command->warn('CopyTradingSubscription model not found. Skipping seeder.');
            return;
        }

        // Get execution connections
        $connectionClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
            $connectionClass = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class;
        } elseif (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            $connectionClass = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class;
        }

        if (!$connectionClass) {
            $this->command->warn('ExecutionConnection model not found. Skipping seeder.');
            return;
        }

        $connections = $connectionClass::where('is_active', 1)->get();
        if ($connections->isEmpty()) {
            $this->command->warn('No active execution connections found. Skipping subscription seeding.');
            return;
        }

        // Get users (need at least 5 for traders and followers)
        $users = User::where('email', '!=', 'admin@admin.com')->take(10)->get();
        if ($users->count() < 5) {
            $this->command->warn('Not enough users found. Need at least 5 users for copy trading subscriptions.');
            return;
        }

        $copyModes = ['easy', 'advanced'];
        $subscriptions = [];

        // Create 12 active subscriptions
        for ($i = 0; $i < 12; $i++) {
            $trader = $users->random();
            $follower = $users->where('id', '!=', $trader->id)->random();
            $connection = $connections->random();
            $copyMode = $copyModes[array_rand($copyModes)];
            $riskMultiplier = rand(50, 200) / 100; // 0.5 to 2.0
            $maxPositionSize = rand(0, 1) ? rand(100, 5000) : null;

            // Check if subscription already exists
            $existing = $modelClass::where('trader_id', $trader->id)
                ->where('follower_id', $follower->id)
                ->first();

            if ($existing) {
                continue; // Skip if already exists
            }

            $subscribedAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23));

            $subscriptionData = [
                'trader_id' => $trader->id,
                'follower_id' => $follower->id,
                'copy_mode' => $copyMode,
                'risk_multiplier' => $riskMultiplier,
                'max_position_size' => $maxPositionSize,
                'is_active' => true,
                'subscribed_at' => $subscribedAt,
                'unsubscribed_at' => null,
                'stats' => [
                    'copied_trades' => rand(5, 50),
                    'total_pnl' => rand(-500, 2000),
                    'success_rate' => rand(60, 85),
                ],
                'created_at' => $subscribedAt,
                'updated_at' => now(),
            ];

            // Handle different model field names
            if ($modelClass === \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class) {
                $subscriptionData['execution_connection_id'] = $connection->id;
            } else {
                $subscriptionData['connection_id'] = $connection->id;
            }

            // Add copy_settings for advanced mode
            if ($copyMode === 'advanced') {
                $subscriptionData['copy_settings'] = [
                    'method' => ['percentage', 'fixed_quantity'][array_rand(['percentage', 'fixed_quantity'])],
                    'percentage' => rand(10, 100),
                    'fixed_quantity' => rand(1, 50) / 10,
                    'min_quantity' => 0.1,
                    'max_quantity' => 10.0,
                ];
            }

            $subscription = $modelClass::create($subscriptionData);
            $subscriptions[] = $subscription;
        }

        // Create 7 inactive subscriptions
        for ($i = 0; $i < 7; $i++) {
            $trader = $users->random();
            $follower = $users->where('id', '!=', $trader->id)->random();
            $connection = $connections->random();
            $copyMode = $copyModes[array_rand($copyModes)];
            $riskMultiplier = rand(50, 200) / 100; // 0.5 to 2.0

            // Check if subscription already exists
            $existing = $modelClass::where('trader_id', $trader->id)
                ->where('follower_id', $follower->id)
                ->first();

            if ($existing) {
                continue; // Skip if already exists
            }

            $subscribedAt = now()->subDays(rand(30, 90));
            $unsubscribedAt = $subscribedAt->copy()->addDays(rand(1, 30));

            $subscriptionData = [
                'trader_id' => $trader->id,
                'follower_id' => $follower->id,
                'copy_mode' => $copyMode,
                'risk_multiplier' => $riskMultiplier,
                'max_position_size' => null,
                'is_active' => false,
                'subscribed_at' => $subscribedAt,
                'unsubscribed_at' => $unsubscribedAt,
                'stats' => [
                    'copied_trades' => rand(1, 20),
                    'total_pnl' => rand(-200, 500),
                    'success_rate' => rand(50, 80),
                ],
                'created_at' => $subscribedAt,
                'updated_at' => $unsubscribedAt,
            ];

            // Handle different model field names
            if ($modelClass === \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class) {
                $subscriptionData['execution_connection_id'] = $connection->id;
            } else {
                $subscriptionData['connection_id'] = $connection->id;
            }

            // Add copy_settings for advanced mode
            if ($copyMode === 'advanced') {
                $subscriptionData['copy_settings'] = [
                    'method' => ['percentage', 'fixed_quantity'][array_rand(['percentage', 'fixed_quantity'])],
                    'percentage' => rand(10, 100),
                    'fixed_quantity' => rand(1, 50) / 10,
                    'min_quantity' => 0.1,
                    'max_quantity' => 10.0,
                ];
            }

            $subscription = $modelClass::create($subscriptionData);
            $subscriptions[] = $subscription;
        }

        $this->command->info('Created ' . count($subscriptions) . ' copy trading subscriptions successfully!');
    }
}
