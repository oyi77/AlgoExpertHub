<?php

namespace Addons\AlgoExpertPlus\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TestJob
 * 
 * Simple test job to verify queue is working correctly.
 * This job just logs a message and completes successfully.
 */
class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Test job executed successfully', [
            'timestamp' => now()->toIso8601String(),
            'queue' => $this->queue,
            'connection' => config('queue.default'),
            'job_id' => $this->job->getJobId() ?? 'N/A',
        ]);
    }
}
