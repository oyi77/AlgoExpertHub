<?php

namespace App\Jobs;

use App\Models\Signal;
use App\Models\User;
use App\Services\QueueOptimizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DistributeSignalJob extends OptimizedJob
{
    protected string $priority = 'high';
    public int $tries = 3;
    public int $timeout = 300; // 5 minutes for large distributions

    protected int $signalId;
    protected int $batchSize = 1000; // Process 1000 users per batch

    public function __construct(int $signalId)
    {
        $this->signalId = $signalId;
        $this->tags = ['signal-distribution', 'high-priority'];
    }

    protected function process(): void
    {
        $signal = Signal::with(['pair:id,name', 'time:id,name', 'market:id,name'])
            ->find($this->signalId);

        if (!$signal) {
            Log::error('Signal not found for distribution', ['signal_id' => $this->signalId]);
            return;
        }

        Log::info('Starting signal distribution', [
            'signal_id' => $this->signalId,
            'signal_title' => $signal->title
        ]);

        // Get all users who should receive this signal
        $userIds = $this->getEligibleUsers($signal);
        
        if (empty($userIds)) {
            Log::info('No eligible users found for signal distribution', ['signal_id' => $this->signalId]);
            return;
        }

        Log::info('Found eligible users for signal distribution', [
            'signal_id' => $this->signalId,
            'user_count' => count($userIds)
        ]);

        // Process users in batches to avoid memory issues
        $batches = array_chunk($userIds, $this->batchSize);
        $queueOptimizer = app(QueueOptimizer::class);
        
        foreach ($batches as $batchIndex => $userBatch) {
            $jobs = [];
            
            foreach ($userBatch as $userId) {
                // Create individual notification jobs
                $jobs[] = new SendSignalNotificationJob($this->signalId, $userId);
            }
            
            // Dispatch batch of notification jobs
            $batchId = $queueOptimizer->dispatchBatch(
                $jobs,
                "signal-{$this->signalId}-batch-{$batchIndex}"
            );
            
            Log::info('Dispatched signal notification batch', [
                'signal_id' => $this->signalId,
                'batch_id' => $batchId,
                'batch_index' => $batchIndex,
                'user_count' => count($userBatch)
            ]);
        }

        // Create dashboard signals for all users in bulk
        $this->createDashboardSignals($userIds);
        
        // Create user signals for tracking in bulk
        $this->createUserSignals($userIds);

        Log::info('Signal distribution completed', [
            'signal_id' => $this->signalId,
            'total_users' => count($userIds),
            'total_batches' => count($batches)
        ]);
    }

    /**
     * Get users eligible to receive this signal
     */
    protected function getEligibleUsers(Signal $signal): array
    {
        return DB::table('plan_subscriptions')
            ->join('plan_signals', 'plan_subscriptions.plan_id', '=', 'plan_signals.plan_id')
            ->join('users', 'plan_subscriptions.user_id', '=', 'users.id')
            ->where('plan_signals.signal_id', $signal->id)
            ->where('plan_subscriptions.is_current', 1)
            ->where('plan_subscriptions.end_date', '>', now())
            ->where('users.status', 1)
            ->distinct()
            ->pluck('users.id')
            ->toArray();
    }

    /**
     * Create dashboard signals in bulk
     */
    protected function createDashboardSignals(array $userIds): void
    {
        $dashboardSignals = [];
        $now = now();
        
        foreach ($userIds as $userId) {
            $dashboardSignals[] = [
                'user_id' => $userId,
                'signal_id' => $this->signalId,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        
        // Insert in chunks to avoid query size limits
        $chunks = array_chunk($dashboardSignals, 500);
        foreach ($chunks as $chunk) {
            DB::table('dashboard_signals')->insertOrIgnore($chunk);
        }
        
        Log::info('Created dashboard signals', [
            'signal_id' => $this->signalId,
            'count' => count($dashboardSignals)
        ]);
    }

    /**
     * Create user signals for tracking in bulk
     */
    protected function createUserSignals(array $userIds): void
    {
        $userSignals = [];
        $now = now();
        
        foreach ($userIds as $userId) {
            $userSignals[] = [
                'user_id' => $userId,
                'signal_id' => $this->signalId,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        
        // Insert in chunks to avoid query size limits
        $chunks = array_chunk($userSignals, 500);
        foreach ($chunks as $chunk) {
            DB::table('user_signals')->insertOrIgnore($chunk);
        }
        
        Log::info('Created user signals', [
            'signal_id' => $this->signalId,
            'count' => count($userSignals)
        ]);
    }

    /**
     * Handle job failure
     */
    protected function onFailure(\Throwable $exception): void
    {
        Log::error('Signal distribution job failed permanently', [
            'signal_id' => $this->signalId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Optionally notify administrators about the failure
        // You could dispatch a notification job here
    }
}