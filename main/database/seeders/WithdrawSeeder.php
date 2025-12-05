<?php

namespace Database\Seeders;

use App\Models\Withdraw;
use App\Models\User;
use App\Models\WithdrawGateway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class WithdrawSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo withdrawal requests for investor presentations
     */
    public function run()
    {
        $users = User::where('email', '!=', 'admin@admin.com')->get();
        $gateways = WithdrawGateway::all();

        if ($users->isEmpty() || $gateways->isEmpty()) {
            $this->command->warn('Users or withdraw gateways not found. Skipping withdraw seeding.');
            return;
        }

        $withdraws = [];
        $statuses = [0 => 'pending', 1 => 'approved', 2 => 'rejected'];

        // Create 20 demo withdrawal requests
        for ($i = 0; $i < 20; $i++) {
            $user = $users->random();
            $gateway = $gateways->random();
            
            $amount = rand(10, 1000);
            $charge = $gateway->charge ?? 0;
            $netAmount = $amount - $charge;
            
            $status = array_rand($statuses);

            $createdAt = Carbon::now()->subDays(rand(0, 45))->subHours(rand(0, 23));

            $withdraw = Withdraw::create([
                'user_id' => $user->id,
                'withdraw_method_id' => $gateway->id,
                'trx' => strtoupper(uniqid('WD')),
                'withdraw_amount' => $amount,
                'withdraw_charge' => $charge,
                'total' => $netAmount,
                'status' => $status,
                'proof' => $status === 1 ? json_encode(['note' => 'Demo withdrawal processed successfully']) : null,
                'reject_reason' => $status === 2 ? 'Demo rejection reason' : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $withdraws[] = $withdraw;
        }

        $this->command->info('Created ' . count($withdraws) . ' demo withdrawals successfully!');
    }
}
