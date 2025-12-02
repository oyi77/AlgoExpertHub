<?php

namespace Addons\MultiChannelSignalAddon\App\Jobs;

use App\Models\Signal;
use App\Services\SignalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoPublishSignalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    protected Signal $signal;

    public function __construct(Signal $signal)
    {
        $this->signal = $signal;
    }

    public function handle()
    {
        try {
            $this->signal->refresh();

            // Ensure signal is still a draft
            if ($this->signal->is_published) {
                Log::info("Signal {$this->signal->id} already published, skipping auto-publish");
                return;
            }

            // Ensure signal has at least one plan assigned
            $planCount = $this->signal->plans()->count();
            if ($planCount === 0) {
                Log::warning("Signal {$this->signal->id} has no plans assigned, cannot auto-publish. Waiting for distribution job to complete.");
                // Retry after delay if no plans assigned yet
                $this->release(30); // Release back to queue for 30 seconds
                return;
            }

            // Publish signal using original SignalService
            $signalService = app(SignalService::class);
            $signalService->sent($this->signal->id);

            Log::info("Auto-published signal {$this->signal->id} with {$planCount} plan(s) assigned");
        } catch (\Exception $e) {
            Log::error("Failed to auto-publish signal {$this->signal->id}: " . $e->getMessage(), [
                'exception' => $e,
                'signal_id' => $this->signal->id,
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("AutoPublishSignalJob failed permanently for signal {$this->signal->id}", [
            'exception' => $exception,
            'signal_id' => $this->signal->id,
        ]);
    }
}
