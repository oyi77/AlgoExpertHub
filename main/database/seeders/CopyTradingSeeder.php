<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;

class CopyTradingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo copy trading settings
     */
    public function run()
    {
        if (!class_exists(\Addons\CopyTrading\App\Models\CopyTradingSetting::class)) {
            $this->command->warn('Copy Trading Setting model not found. Skipping seeder.');
            return;
        }

        $modelClass = \Addons\CopyTrading\App\Models\CopyTradingSetting::class;

        // Get users
        $users = User::where('email', '!=', 'admin@admin.com')->take(3)->get();
        $admin = Admin::first();

        if ($users->isEmpty()) {
            $this->command->warn('Users not found. Skipping copy trading seeding.');
            return;
        }

        $settings = [
            [
                'user_id' => $users->first()->id,
                'admin_id' => null,
                'is_admin_owned' => false,
                'is_enabled' => true,
                'min_followers_balance' => 100.00,
                'max_copiers' => 50,
                'risk_multiplier_default' => 1.0,
                'allow_manual_trades' => true,
                'allow_auto_trades' => true,
                'settings' => [
                    'copy_stop_loss' => true,
                    'copy_take_profit' => true,
                    'copy_lot_size' => false,
                    'max_lot_size' => 10.0,
                ],
            ],
            [
                'user_id' => $users->skip(1)->first()->id ?? $users->first()->id,
                'admin_id' => null,
                'is_admin_owned' => false,
                'is_enabled' => true,
                'min_followers_balance' => 500.00,
                'max_copiers' => 20,
                'risk_multiplier_default' => 0.5,
                'allow_manual_trades' => false,
                'allow_auto_trades' => true,
                'settings' => [
                    'copy_stop_loss' => true,
                    'copy_take_profit' => true,
                    'copy_lot_size' => true,
                    'max_lot_size' => 5.0,
                ],
            ],
            [
                'user_id' => null,
                'admin_id' => $admin->id ?? null,
                'is_admin_owned' => true,
                'is_enabled' => true,
                'min_followers_balance' => 1000.00,
                'max_copiers' => 100,
                'risk_multiplier_default' => 1.0,
                'allow_manual_trades' => true,
                'allow_auto_trades' => true,
                'settings' => [
                    'copy_stop_loss' => true,
                    'copy_take_profit' => true,
                    'copy_lot_size' => false,
                    'max_lot_size' => 20.0,
                ],
            ],
            [
                'user_id' => $users->skip(2)->first()->id ?? $users->first()->id,
                'admin_id' => null,
                'is_admin_owned' => false,
                'is_enabled' => false,
                'min_followers_balance' => 200.00,
                'max_copiers' => 10,
                'risk_multiplier_default' => 0.75,
                'allow_manual_trades' => true,
                'allow_auto_trades' => false,
                'settings' => [
                    'copy_stop_loss' => true,
                    'copy_take_profit' => false,
                    'copy_lot_size' => false,
                ],
            ],
        ];

        foreach ($settings as $setting) {
            // Check if setting already exists for this user/admin
            $existing = $modelClass::where('user_id', $setting['user_id'])
                ->where('admin_id', $setting['admin_id'])
                ->where('is_admin_owned', $setting['is_admin_owned'])
                ->first();
            
            if (!$existing) {
                $modelClass::create($setting);
            }
        }

        $this->command->info('Copy Trading Settings seeded successfully!');
    }
}
