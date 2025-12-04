<?php

namespace App\Jobs;

use App\Models\Signal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DistributeSignalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $signalId;

    public function __construct(int $signalId)
    {
        $this->signalId = $signalId;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $signal = Signal::with(['plans' => function ($q) {
            $q->where('status', 1);
        }, 'plans.subscriptions' => function ($q) {
            $q->where('is_current', 1);
        }, 'plans.subscriptions.user', 'market', 'pair', 'time'])->find($this->signalId);

        if (!$signal) {
            return;
        }

        foreach ($signal->plans as $plan) {
            $plan->subscriptions()->where('is_current', 1)->orderBy('id')->chunkById(100, function ($subs) use ($plan, $signal) {
                foreach ($subs as $subscription) {
                    $user = $subscription->user;
                    if (!$user) {
                        continue;
                    }
                    if ($subscription->plan_expired_at && $subscription->plan_expired_at->isPast()) {
                        continue;
                    }

                    if ($plan->dashboard) {
                        SendChannelMessageJob::dispatch('dashboard', $user->id, $signal->id, $plan->id)->onQueue('notifications');
                    }
                    if ($plan->telegram) {
                        SendChannelMessageJob::dispatch('telegram', $user->id, $signal->id, $plan->id)->onQueue('notifications');
                    }
                    if ($plan->email) {
                        SendChannelMessageJob::dispatch('email', $user->id, $signal->id, $plan->id)->onQueue('notifications');
                    }
                    if ($plan->sms) {
                        SendChannelMessageJob::dispatch('sms', $user->id, $signal->id, $plan->id)->onQueue('notifications');
                    }
                    if (env('ALLOW_ULTRA') === 'on') {
                        SendChannelMessageJob::dispatch('whatsapp', $user->id, $signal->id, $plan->id)->onQueue('notifications');
                    }
                }
            });
        }
    }
}

