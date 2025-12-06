<?php

namespace Addons\TradingExecutionEngine\App\Observers;

use Addons\TradingExecutionEngine\App\Jobs\ExecuteSignalJob;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Services\SignalExecutionService;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

class SignalObserver
{
    protected SignalExecutionService $executionService;

    public function __construct(SignalExecutionService $executionService)
    {
        $this->executionService = $executionService;
    }

    /**
     * Handle the Signal "updated" event.
     */
    public function updated(Signal $signal): void
    {
        // Check if signal was just published
        if ($signal->is_published && $signal->wasChanged('is_published')) {
            $this->handleSignalPublished($signal);
        }
    }

    /**
     * Handle signal published.
     */
    protected function handleSignalPublished(Signal $signal): void
    {
        try {
            // Get all active connections
            $connections = ExecutionConnection::active()->get();

            foreach ($connections as $connection) {
                // Check if this connection should execute this signal
                if ($this->shouldExecute($signal, $connection)) {
                    // Dispatch job for async execution
                    ExecuteSignalJob::dispatch($signal, $connection->id);
                }
            }
        } catch (\Exception $e) {
            Log::error("Signal observer error", [
                'error' => $e->getMessage(),
                'signal_id' => $signal->id,
            ]);
        }
    }

    /**
     * Check if connection should execute this signal.
     */
    protected function shouldExecute(Signal $signal, ExecutionConnection $connection): bool
    {
        // Admin connections execute all signals
        if ($connection->isAdminOwned()) {
            return true;
        }

        // User connections: check if user has access to signal's plans
        if ($connection->user_id) {
            $user = $connection->user;
            if (!$user) {
                return false;
            }

            // Check if user's current plan has access to this signal
            $subscription = $user->currentplan()->where('is_current', 1)->first();
            if (!$subscription) {
                return false;
            }

            // Check if signal is assigned to user's plan
            $signalPlans = $signal->plans()->pluck('plan_id')->toArray();
            return in_array($subscription->plan_id, $signalPlans);
        }

        return false;
    }
}

