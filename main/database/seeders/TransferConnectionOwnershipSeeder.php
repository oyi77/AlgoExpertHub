<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;

class TransferConnectionOwnershipSeeder extends Seeder
{
    /**
     * Transfer Exchange Connection ownership from Admin to User
     * 
     * Usage: php artisan db:seed --class=TransferConnectionOwnershipSeeder
     * 
     * Or modify the variables below and run:
     * php artisan db:seed --class=TransferConnectionOwnershipSeeder
     */
    public function run()
    {
        // ============================================
        // CONFIGURATION - Update these values
        // ============================================
        $connectionName = 'DemoExness1';
        $userEmail = 'user1@user.com';
        // ============================================

        $this->command->info("🔄 Transferring Exchange Connection ownership...");
        $this->command->info("   Connection: {$connectionName}");
        $this->command->info("   Target User: {$userEmail}");

        try {
            // Find the connection
            $connection = ExchangeConnection::where('name', $connectionName)->first();
            
            if (!$connection) {
                $this->command->error("❌ Connection '{$connectionName}' not found!");
                $this->command->info("   Available connections:");
                ExchangeConnection::select('id', 'name', 'user_id', 'admin_id', 'is_admin_owned')
                    ->get()
                    ->each(function ($conn) {
                        $owner = $conn->is_admin_owned 
                            ? "Admin ID: {$conn->admin_id}" 
                            : "User ID: {$conn->user_id}";
                        $this->command->info("   - {$conn->name} (ID: {$conn->id}, {$owner})");
                    });
                return;
            }

            // Find the user
            $user = User::where('email', $userEmail)->first();
            
            if (!$user) {
                $this->command->error("❌ User '{$userEmail}' not found!");
                $this->command->info("   Available users:");
                User::select('id', 'username', 'email')
                    ->limit(10)
                    ->get()
                    ->each(function ($u) {
                        $this->command->info("   - {$u->email} (ID: {$u->id}, Username: {$u->username})");
                    });
                return;
            }

            // Show current state
            $this->command->info("\n📋 Current Connection State:");
            $this->command->info("   ID: {$connection->id}");
            $this->command->info("   Name: {$connection->name}");
            $this->command->info("   Current User ID: " . ($connection->user_id ?? 'NULL'));
            $this->command->info("   Current Admin ID: " . ($connection->admin_id ?? 'NULL'));
            $this->command->info("   Is Admin Owned: " . ($connection->is_admin_owned ? 'Yes' : 'No'));

            // Show target user
            $this->command->info("\n👤 Target User:");
            $this->command->info("   ID: {$user->id}");
            $this->command->info("   Email: {$user->email}");
            $this->command->info("   Username: {$user->username}");

            // Confirm transfer
            $this->command->warn("\n⚠️  This will transfer ownership from Admin to User!");
            $this->command->warn("   Connection will be owned by: {$user->email} (ID: {$user->id})");
            
            // Perform transfer
            DB::beginTransaction();
            
            try {
                $connection->update([
                    'user_id' => $user->id,
                    'admin_id' => null,
                    'is_admin_owned' => false,
                ]);

                DB::commit();

                // Verify
                $connection->refresh();
                
                $this->command->info("\n✅ Transfer completed successfully!");
                $this->command->info("\n📋 Updated Connection State:");
                $this->command->info("   ID: {$connection->id}");
                $this->command->info("   Name: {$connection->name}");
                $this->command->info("   User ID: {$connection->user_id}");
                $this->command->info("   Admin ID: " . ($connection->admin_id ?? 'NULL'));
                $this->command->info("   Is Admin Owned: " . ($connection->is_admin_owned ? 'Yes' : 'No'));
                $this->command->info("   Owner: {$user->email} (ID: {$user->id})");

                Log::info("Exchange Connection ownership transferred", [
                    'connection_id' => $connection->id,
                    'connection_name' => $connection->name,
                    'from' => 'admin',
                    'to_user_id' => $user->id,
                    'to_user_email' => $user->email,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->command->error("❌ Error transferring ownership: " . $e->getMessage());
            $this->command->error("   File: " . $e->getFile() . ':' . $e->getLine());
            Log::error("Failed to transfer Exchange Connection ownership", [
                'connection_name' => $connectionName,
                'user_email' => $userEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}

