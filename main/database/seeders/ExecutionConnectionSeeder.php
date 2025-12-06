<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class ExecutionConnectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo execution connections for trading execution engine
     */
    public function run()
    {
        if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            $this->command->warn('Execution Connection model not found. Skipping seeder.');
            return;
        }

        $modelClass = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class;

        // Get admin and users
        $admin = Admin::first();
        $users = User::where('email', '!=', 'admin@admin.com')->take(2)->get();

        if (!$admin) {
            $this->command->warn('Admin not found. Skipping execution connection seeding.');
            return;
        }

        $connections = [
            [
                'user_id' => null,
                'admin_id' => $admin->id,
                'name' => 'Binance Demo Connection',
                'type' => 'crypto',
                'exchange_name' => 'binance',
                'credentials' => [
                    'api_key' => 'demo_api_key_binance',
                    'api_secret' => 'demo_api_secret_binance',
                    'testnet' => true,
                ],
                'status' => 'active',
                'is_active' => true,
                'is_admin_owned' => true,
                'last_error' => null,
                'last_tested_at' => now()->subHours(1),
                'last_used_at' => now()->subMinutes(30),
                'settings' => [
                    'position_sizing_strategy' => 'percentage',
                    'position_sizing_value' => 2.0,
                    'max_position_size' => 1000,
                ],
            ],
            [
                'user_id' => null,
                'admin_id' => $admin->id,
                'name' => 'Coinbase Pro Connection',
                'type' => 'crypto',
                'exchange_name' => 'coinbasepro',
                'credentials' => [
                    'api_key' => 'demo_api_key_coinbase',
                    'api_secret' => 'demo_api_secret_coinbase',
                    'passphrase' => 'demo_passphrase',
                ],
                'status' => 'active',
                'is_active' => true,
                'is_admin_owned' => true,
                'last_error' => null,
                'last_tested_at' => now()->subHours(2),
                'last_used_at' => now()->subHours(1),
                'settings' => [
                    'position_sizing_strategy' => 'fixed_amount',
                    'position_sizing_value' => 100,
                ],
            ],
            [
                'user_id' => null,
                'admin_id' => $admin->id,
                'name' => 'MT4 Demo Account',
                'type' => 'fx',
                'exchange_name' => 'mt4',
                'credentials' => [
                    'account_id' => '12345678',
                    'password' => 'demo_password',
                    'server' => 'demo.server.com',
                ],
                'status' => 'active',
                'is_active' => true,
                'is_admin_owned' => true,
                'last_error' => null,
                'last_tested_at' => now()->subHours(3),
                'last_used_at' => now()->subMinutes(45),
                'settings' => [
                    'position_sizing_strategy' => 'percentage',
                    'position_sizing_value' => 1.0,
                    'lot_size' => 0.01,
                ],
            ],
            [
                'user_id' => $users->first()->id ?? null,
                'admin_id' => null,
                'name' => 'User Binance Connection',
                'type' => 'crypto',
                'exchange_name' => 'binance',
                'credentials' => [
                    'api_key' => 'user_api_key',
                    'api_secret' => 'user_api_secret',
                    'testnet' => true,
                ],
                'status' => 'active',
                'is_active' => true,
                'is_admin_owned' => false,
                'last_error' => null,
                'last_tested_at' => now()->subDays(1),
                'last_used_at' => now()->subHours(2),
                'settings' => [
                    'position_sizing_strategy' => 'percentage',
                    'position_sizing_value' => 1.5,
                ],
            ],
            [
                'user_id' => null,
                'admin_id' => $admin->id,
                'name' => 'Kraken Connection (Paused)',
                'type' => 'crypto',
                'exchange_name' => 'kraken',
                'credentials' => [
                    'api_key' => 'demo_api_key_kraken',
                    'api_secret' => 'demo_api_secret_kraken',
                ],
                'status' => 'error',
                'is_active' => false,
                'is_admin_owned' => true,
                'last_error' => 'API key invalid',
                'last_tested_at' => now()->subDays(2),
                'last_used_at' => now()->subDays(3),
                'settings' => [],
            ],
        ];

        foreach ($connections as $connection) {
            $existing = $modelClass::where('name', $connection['name'])->first();
            
            if (!$existing) {
                $conn = new $modelClass();
                $conn->fill($connection);
                $conn->save();
            }
        }

        $this->command->info('Execution Connections seeded successfully!');
    }
}
