<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class HorizonSupervisor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if Horizon is running and restart it if not';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        
        // Skip if Horizon is not available
        if (!class_exists(\Laravel\Horizon\Horizon::class)) {
            $this->warn('Horizon is not installed.');
            return Command::SUCCESS;
        }

        // Skip if application is in maintenance mode
        if (app()->isDownForMaintenance()) {
            $this->info('Application is in maintenance mode. Skipping Horizon supervisor check.');
            return Command::SUCCESS;
        }

        // Skip if queue connection is not Redis
        if (config('queue.default') !== 'redis') {
            $this->warn('Queue connection is not Redis. Horizon requires Redis.');
            return Command::SUCCESS;
        }

        // Check if Horizon is running
        if ($this->isHorizonRunning()) {
            $this->info('Horizon is running.');
            return Command::SUCCESS;
        }

        // Horizon is not running, restart it
        $this->warn('Horizon is not running. Attempting to restart...');

        try {
            // Start Horizon in background
            $this->startHorizon();
            $this->info('Horizon restarted successfully.');
            
            // Log the restart
            \Log::info('Horizon restarted by supervisor', [
                'timestamp' => now()->toIso8601String(),
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to restart Horizon: ' . $e->getMessage());
            
            \Log::error('Failed to restart Horizon', [
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Check if Horizon is running
     *
     * @return bool
     */
    protected function isHorizonRunning(): bool
    {
        try {
            // Method 1: Check Redis for active supervisors
            $redis = app('redis')->connection(config('horizon.use', 'default'));
            $prefix = config('horizon.prefix', 'horizon:');
            
            $supervisors = $redis->keys($prefix . 'supervisors:*');
            
            if (!empty($supervisors)) {
                foreach ($supervisors as $key) {
                    $data = $redis->get($key);
                    if ($data) {
                        $supervisor = json_decode($data, true);
                        if (isset($supervisor['processes']) && $supervisor['processes'] > 0) {
                            return true;
                        }
                    }
                }
            }
            
            // Method 2: Check process list as fallback
            $processCount = $this->getHorizonProcessCount();
            return $processCount > 0;
        } catch (\Throwable $e) {
            // If Redis check fails, use process check
            return $this->getHorizonProcessCount() > 0;
        }
    }

    /**
     * Get count of running Horizon processes
     *
     * @return int
     */
    protected function getHorizonProcessCount(): int
    {
        try {
            $command = "ps aux | grep 'artisan horizon' | grep -v grep | wc -l";
            $count = (int) trim(shell_exec($command) ?: '0');
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Start Horizon in background
     *
     * @return void
     */
    protected function startHorizon(): void
    {
        $artisanPath = base_path('artisan');
        $logPath = storage_path('logs/horizon.log');
        
        // Start Horizon in background
        $command = sprintf(
            'cd %s && nohup php %s horizon > %s 2>&1 &',
            escapeshellarg(base_path()),
            escapeshellarg($artisanPath),
            escapeshellarg($logPath)
        );
        
        exec($command);
        
        // Wait a moment for Horizon to start
        sleep(2);
        
        // Verify it started
        if (!$this->isHorizonRunning()) {
            throw new \RuntimeException('Horizon failed to start after restart attempt.');
        }
    }
}
