<?php

namespace Database\Seeders;

use App\Models\PlanSubscription;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PlanSubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo active subscriptions for investor presentations
     */
    public function run()
    {
        $users = User::where('email', '!=', 'admin@admin.com')->get();
        $plans = Plan::all();

        if ($users->isEmpty() || $plans->isEmpty()) {
            $this->command->warn('Users or plans not found. Skipping subscription seeding.');
            return;
        }

        $subscriptions = [];

        // Create active subscriptions for demo users
        foreach ($users->take(3) as $user) {
            $plan = $plans->random();
            
            $startDate = Carbon::now()->subDays(rand(1, 30));
            $endDate = $startDate->copy()->addDays($plan->duration ?? 30);
            $isCurrent = $endDate->isFuture();

            $subscription = PlanSubscription::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'is_current' => 1,
                ],
                [
                    'plan_expired_at' => $endDate,
                    'created_at' => $startDate,
                    'updated_at' => Carbon::now(),
                ]
            );

            $subscriptions[] = $subscription;
        }

        // Create some expired subscriptions
        for ($i = 0; $i < 5; $i++) {
            $user = $users->random();
            $plan = $plans->random();
            
            $startDate = Carbon::now()->subDays(rand(60, 120));
            $endDate = $startDate->copy()->addDays($plan->duration ?? 30);

            $subscription = PlanSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'plan_expired_at' => $endDate,
                'is_current' => 0,
                'created_at' => $startDate,
                'updated_at' => $endDate,
            ]);

            $subscriptions[] = $subscription;
        }

        $this->command->info('Created ' . count($subscriptions) . ' demo subscriptions successfully!');
    }
}
