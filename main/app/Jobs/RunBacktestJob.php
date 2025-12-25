<?php

namespace App\Jobs;

use App\Models\Backtest;
use App\Services\BacktestingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunBacktestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600; // 10 minutes

    protected Backtest $backtest;

    /**
     * Create a new job instance.
     */
    public function __construct(Backtest $backtest)
    {
        $this->backtest = $backtest;
    }

    /**
     * Execute the job.
     */
    public function handle(BacktestingService $service): void
    {
        try {
            Log::info('Starting backtest execution', [
                'backtest_id' => $this->backtest->id,
                'user_id' => $this->backtest->user_id,
            ]);

            $result = $service->runBacktest($this->backtest);

            if (!$result['success']) {
                Log::error('Backtest execution failed', [
                    'backtest_id' => $this->backtest->id,
                    'error' => $result['message'],
                ]);
            } else {
                Log::info('Backtest execution completed', [
                    'backtest_id' => $this->backtest->id,
                    'total_return' => $this->backtest->fresh()->total_return,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Backtest job failed', [
                'backtest_id' => $this->backtest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->backtest->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Backtest job failed permanently', [
            'backtest_id' => $this->backtest->id,
            'error' => $exception->getMessage(),
        ]);

        $this->backtest->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }
}

