<?php

namespace Addons\MultiChannelSignalAddon\App\Jobs;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use App\Models\Plan;
use App\Models\Signal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributeAdminSignalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected Signal $signal;
    protected ChannelSource $channelSource;
    protected Collection $recipients;

    public function __construct(Signal $signal, ChannelSource $channelSource, Collection $recipients)
    {
        $this->signal = $signal;
        $this->channelSource = $channelSource;
        $this->recipients = $recipients;
    }

    public function handle()
    {
        try {
            $this->signal->refresh();
            $this->channelSource->refresh();

            if (!$this->channelSource->isAdminOwned()) {
                Log::warning("Signal {$this->signal->id} distribution skipped - channel is not admin-owned");
                return;
            }

            $distributed = 0;
            $skipped = 0;

            foreach ($this->recipients as $user) {
                try {
                    // Check for duplicate signal for this user
                    $userPlanIds = $user->currentplan()->where('is_current', 1)->pluck('plan_id')->toArray();
                    $duplicate = Signal::where('message_hash', $this->signal->message_hash)
                        ->whereHas('plans', function ($query) use ($userPlanIds) {
                            $query->whereIn('plan_id', $userPlanIds);
                        })
                        ->where('created_at', '>=', now()->subDay())
                        ->exists();

                    if ($duplicate) {
                        $skipped++;
                        continue;
                    }

                    // Determine target plan(s) for this user
                    $targetPlans = $this->getTargetPlans($user);

                    if ($targetPlans->isEmpty()) {
                        // Use channel's default plan or user's current plan
                        $defaultPlan = $this->channelSource->default_plan_id 
                            ?? $user->currentplan()->where('is_current', 1)->first()?->plan_id;

                        if ($defaultPlan) {
                            $targetPlans = collect([$defaultPlan]);
                        } else {
                            Log::warning("No target plan found for user {$user->id}, skipping signal distribution");
                            $skipped++;
                            continue;
                        }
                    }

                    // Attach signal to plan(s)
                    DB::transaction(function () use ($targetPlans, $user) {
                        $this->signal->plans()->syncWithoutDetaching($targetPlans->toArray());
                        
                        // Track analytics for each distribution
                        $analyticsService = app(\Addons\MultiChannelSignalAddon\App\Services\SignalAnalyticsService::class);
                        foreach ($targetPlans as $planId) {
                            $analyticsService->trackDistribution($this->signal, $planId, $user->id);
                        }
                    });

                    $distributed++;

                } catch (\Exception $e) {
                    Log::error("Failed to distribute signal to user {$user->id}: " . $e->getMessage(), [
                        'exception' => $e,
                        'signal_id' => $this->signal->id,
                        'user_id' => $user->id,
                    ]);
                    $skipped++;
                }
            }

            Log::info("Signal {$this->signal->id} distribution complete: {$distributed} distributed, {$skipped} skipped");

        } catch (\Exception $e) {
            Log::error("Failed to distribute admin signal {$this->signal->id}: " . $e->getMessage(), [
                'exception' => $e,
                'signal_id' => $this->signal->id,
                'channel_source_id' => $this->channelSource->id,
            ]);
            throw $e;
        }
    }

    /**
     * Get target plans for user based on channel assignment.
     *
     * @param User $user
     * @return Collection
     */
    protected function getTargetPlans(User $user): Collection
    {
        $plans = collect();

        switch ($this->channelSource->scope) {
            case 'plan':
                // Use assigned plans
                $plans = $this->channelSource->assignedPlans()->pluck('id');
                break;

            case 'global':
                // Use channel's default plan or all active plans
                if ($this->channelSource->default_plan_id) {
                    $plans = collect([$this->channelSource->default_plan_id]);
                } else {
                    // Attach to all active plans
                    $plans = Plan::whereStatus(true)->pluck('id');
                }
                break;

            case 'user':
                // Use user's current plan or channel default
                $userPlan = $user->currentplan()->where('is_current', 1)->first()?->plan_id;
                if ($userPlan) {
                    $plans = collect([$userPlan]);
                } elseif ($this->channelSource->default_plan_id) {
                    $plans = collect([$this->channelSource->default_plan_id]);
                }
                break;
        }

        return $plans;
    }

    public function failed(\Throwable $exception)
    {
        Log::error("DistributeAdminSignalJob failed permanently for signal {$this->signal->id}", [
            'exception' => $exception,
            'signal_id' => $this->signal->id,
            'channel_source_id' => $this->channelSource->id,
        ]);
    }
}

