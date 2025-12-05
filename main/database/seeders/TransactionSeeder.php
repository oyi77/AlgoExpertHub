<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo transactions for investor presentations
     */
    public function run()
    {
        $users = User::where('email', '!=', 'admin@admin.com')->get();

        if ($users->isEmpty()) {
            $this->command->warn('Users not found. Skipping transaction seeding.');
            return;
        }

        $transactions = [];
        $types = ['deposit', 'withdraw', 'referral_commission', 'subscription', 'refund', 'bonus'];
        $statuses = [0 => 'pending', 1 => 'completed', 2 => 'failed'];

        // Create 40 demo transactions
        for ($i = 0; $i < 40; $i++) {
            $user = $users->random();
            $type = $types[array_rand($types)];
            $status = array_rand($statuses);
            
            $amount = rand(10, 2000);
            $charge = rand(0, 50);

            $descriptions = [
                'deposit' => 'Wallet deposit transaction',
                'withdraw' => 'Withdrawal request',
                'referral_commission' => 'Referral commission earned',
                'subscription' => 'Plan subscription payment',
                'refund' => 'Payment refund',
                'bonus' => 'Welcome bonus credited',
            ];

            $createdAt = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23));

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'charge' => $charge,
                'description' => $descriptions[$type] ?? 'Transaction',
                'status' => $status,
                'trx' => strtoupper(uniqid('TRX')),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $transactions[] = $transaction;
        }

        $this->command->info('Created ' . count($transactions) . ' demo transactions successfully!');
    }
}
