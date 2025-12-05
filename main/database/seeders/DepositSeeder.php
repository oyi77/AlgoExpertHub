<?php

namespace Database\Seeders;

use App\Models\Deposit;
use App\Models\User;
use App\Models\Gateway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo deposits for investor presentations
     */
    public function run()
    {
        $users = User::where('email', '!=', 'admin@admin.com')->get();
        $gateways = Gateway::all();

        if ($users->isEmpty() || $gateways->isEmpty()) {
            $this->command->warn('Users or gateways not found. Skipping deposit seeding.');
            return;
        }

        $deposits = [];
        $statuses = [1 => 'approved', 2 => 'pending', 3 => 'rejected'];
        $types = [0 => 'manual', 1 => 'automatic'];

        // Create 25 demo deposits
        for ($i = 0; $i < 25; $i++) {
            $user = $users->random();
            $gateway = $gateways->random();
            
            $amount = rand(50, 5000);
            $rate = $gateway->rate ?? 1;
            $charge = $gateway->charge ?? 0;
            $total = ($amount * $rate) + $charge;
            
            $status = array_rand($statuses);
            $type = array_rand($types);
            
            $trx = strtoupper(Str::random(16));
            
            // Ensure unique transaction ID
            while (Deposit::where('trx', $trx)->exists()) {
                $trx = strtoupper(Str::random(16));
            }

            $createdAt = Carbon::now()->subDays(rand(0, 60))->subHours(rand(0, 23));

            $deposit = Deposit::create([
                'trx' => $trx,
                'user_id' => $user->id,
                'gateway_id' => $gateway->id,
                'amount' => $amount,
                'rate' => $rate,
                'charge' => $charge,
                'total' => $total,
                'status' => $status,
                'type' => $type,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $deposits[] = $deposit;
        }

        $this->command->info('Created ' . count($deposits) . ' demo deposits successfully!');
    }
}
