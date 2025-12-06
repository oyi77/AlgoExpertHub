<?php

namespace Addons\TradingExecutionEngine\App\Listeners;

use Addons\TradingExecutionEngine\App\Jobs\ExecuteSignalJob;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Services\SignalExecutionService;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

class SignalPublishedListener
{
    protected SignalExecutionService $executionService;

    public function __construct(SignalExecutionService $executionService)
    {
        $this->executionService = $executionService;
    }

    /**
     * Handle signal published event.
     */
    public function handle($event): void
    {
        try {
            $signal = $event->signal ?? null;
            
            if (!$signal instanceof Signal) {
                return;
            }

            // Only process published signals
            if (!$signal->is_published) {
                return;
            }

            // Get all active connections that should execute this signal
            // For now, we'll check all active connections
            // In future, can add signal-to-connection mapping
            $connections = ExecutionConnection::active()->get();

            foreach ($connections as $connection) {
                // Check if this connection should execute this signal
                // For admin connections, execute all signals
                // For user connections, check if user has access to signal's plan
                if ($this->shouldExecute($signal, $connection)) {
                    // Dispatch job for async execution
                    ExecuteSignalJob::dispatch($signal, $connection->id);
                }
            }
        } catch (\Exception $e) {
            Log::error("Signal published listener error", [
                'error' => $e->getMessage(),
                'signal_id' => $signal->id ?? null,
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

