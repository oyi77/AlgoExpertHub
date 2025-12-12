<?php

namespace App\Jobs;

use App\Models\UserPlan;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessSubscriptionRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function handle(): void
    {
        Log::info('Processing subscription renewals...');

        $expiringPlans = UserPlan::where('expire_date', '<=', Carbon::now()->addDays(1))
            ->where('expire_date', '>', Carbon::now())
            ->where('status', 'active')
            ->where('auto_renewal', true)
            ->get();

        foreach ($expiringPlans as $plan) {
            try {
                $this->processRenewal($plan);
            } catch (\Exception $e) {
                Log::error("Failed to renew plan {$plan->id}: {$e->getMessage()}");
            }
        }

        Log::info("Processed {$expiringPlans->count()} subscription renewals");
    }

    protected function processRenewal(UserPlan $plan): void
    {
        $paymentService = app(PaymentService::class);

        // Attempt payment
        $result = $paymentService->processRenewal($plan);

        if ($result['success']) {
            // Extend subscription
            $plan->update([
                'expire_date' => Carbon::now()->addDays($plan->duration_days),
                'last_renewed_at' => now()
            ]);

            Log::info("Successfully renewed plan {$plan->id} for user {$plan->user_id}");

            // Send notification
            $plan->user->notify(new \App\Notifications\SubscriptionRenewedNotification($plan));
        } else {
            Log::warning("Failed to renew plan {$plan->id}: {$result['message']}");

            // Send failed notification
            $plan->user->notify(new \App\Notifications\SubscriptionRenewalFailedNotification($plan));
        }
    }
}
