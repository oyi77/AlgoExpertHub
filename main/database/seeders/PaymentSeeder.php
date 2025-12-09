<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\User;
use App\Models\Plan;
use App\Models\Gateway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo payments/subscriptions for investor presentations
     */
    public function run()
    {
        $users = User::where('email', '!=', 'admin@admin.com')->get();
        $plans = Plan::all();
        $gateways = Gateway::all();

        if ($users->isEmpty() || $plans->isEmpty() || $gateways->isEmpty()) {
            $this->command->warn('Users, plans, or gateways not found. Skipping payment seeding.');
            return;
        }

        $payments = [];
        $statuses = [0 => 'pending', 1 => 'approved', 2 => 'rejected'];
        $types = [0 => 'manual', 1 => 'automatic'];

        // Create 30 demo payments
        for ($i = 0; $i < 30; $i++) {
            $user = $users->random();
            $plan = $plans->random();
            $gateway = $gateways->random();
            
            // Ensure amount is never null (amount column is NOT NULL)
            $amount = $plan->price ?? 0;
            $rate = $gateway->rate ?? 1;
            $charge = $gateway->charge ?? 0;
            $total = ($amount * $rate) + $charge;
            
            $status = array_rand($statuses);
            $type = array_rand($types);
            
            $trx = strtoupper(Str::random(16));
            
            // Ensure unique transaction ID
            while (Payment::where('trx', $trx)->exists()) {
                $trx = strtoupper(Str::random(16));
            }

            $createdAt = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23));
            $expiredAt = $status === 1 
                ? $createdAt->copy()->addDays($plan->duration ?? 30)
                : null;

            $payment = Payment::create([
                'trx' => $trx,
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'gateway_id' => $gateway->id,
                'amount' => $amount,
                'rate' => $rate,
                'charge' => $charge,
                'total' => $total,
                'status' => $status,
                'type' => $type,
                'plan_expired_at' => $expiredAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $payments[] = $payment;
        }

        $this->command->info('Created ' . count($payments) . ' demo payments successfully!');
    }
}
