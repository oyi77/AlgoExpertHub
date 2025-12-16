<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueueOptimizer;
use Illuminate\Support\Facades\Cache;

class QueueManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:manage 
                            {action : The action to perform (monitor|scale|health|metrics|clear)}
                            {--queue= : Specific queue to target}
                            {--workers= : Number of workers for scaling}
                            {--json : Output in JSON format}';

    /**
     * The console command description.
     */
    protected $description = 'Manage and monitor queue system performance';

    protected QueueOptimizer $queueOptimizer;

    public function __construct(QueueOptimizer $queueOptimizer)
    {
        parent::__construct();
        $this->queueOptimizer = $queueOptimizer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'monitor' => $this->monitorQueues(),
            'scale' => $this->scaleWorkers(),
            'health' => $this->showHealth(),
            'metrics' => $this->showMetrics(),
            'clear' => $this->clearMetrics(),
            default => $this->showUsage()
        };
    }

    /**
     * Monitor queue status
     */
    protected function monitorQueues(): int
    {
        $this->info('Monitoring queue system...');
        
        $metrics = $this->queueOptimizer->monitorHealth();
        
        if ($this->option('json')) {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->displayHealthTable($metrics);
        
        return 0;
    }

    /**
     * Scale workers
     */
    protected function scaleWorkers(): int
    {
        $workers = $this->option('workers');
        
        if (!$workers) {
            $this->error('Please specify the number of workers with --workers option');
            return 1;
        }

        $this->info("Scaling to $workers workers...");
        
        $success = $this->queueOptimizer->scaleWorkers((int) $workers);
        
        if ($success) {
            $this->info('Workers scaled successfully');
            return 0;
        } else {
            $this->error('Failed to scale workers');
            return 1;
        }
    }

    /**
     * Show queue health
     */
    protected function showHealth(): int
    {
        $metrics = $this->queueOptimizer->monitorHealth();
        
        if ($this->option('json')) {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->info('Queue Health Status');
        $this->line('==================');
        
        foreach ($metrics as $queue => $data) {
            if ($queue === 'overall') {
                continue;
            }
            
            $healthColor = $this->getHealthColor($data['health_score']);
            $this->line("<fg=$healthColor>$queue: {$data['health_score']}% healthy</>");
            $this->line("  Size: {$data['size']} jobs");
            $this->line("  Processing Rate: {$data['processing_rate']} jobs/min");
            $this->line("  Failure Rate: {$data['failure_rate']}%");
            $this->line('');
        }
        
        $overall = $metrics['overall'];
        $this->info('Overall Status:');
        $this->line("Total Jobs: {$overall['total_jobs']}");
        $this->line("Active Workers: {$overall['active_workers']}");
        $this->line("Recommended Workers: {$overall['recommended_workers']}");
        
        return 0;
    }

    /**
     * Show detailed metrics
     */
    protected function showMetrics(): int
    {
        $metrics = $this->queueOptimizer->getMetrics();
        
        if ($this->option('json')) {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->displayMetricsTable($metrics);
        
        return 0;
    }

    /**
     * Clear cached metrics
     */
    protected function clearMetrics(): int
    {
        $this->info('Clearing queue metrics...');
        
        $keys = [
            'queue_health_metrics',
            'queue_metrics:*',
            'system_cpu_usage'
        ];
        
        foreach ($keys as $key) {
            if (str_contains($key, '*')) {
                // Clear pattern-based keys
                $pattern = str_replace('*', '', $key);
                $allKeys = Cache::getRedis()->keys($pattern . '*');
                foreach ($allKeys as $cacheKey) {
                    Cache::forget($cacheKey);
                }
            } else {
                Cache::forget($key);
            }
        }
        
        $this->info('Queue metrics cleared successfully');
        
        return 0;
    }

    /**
     * Show command usage
     */
    protected function showUsage(): int
    {
        $this->error('Invalid action. Available actions: monitor, scale, health, metrics, clear');
        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan queue:manage monitor');
        $this->line('  php artisan queue:manage scale --workers=5');
        $this->line('  php artisan queue:manage health --json');
        $this->line('  php artisan queue:manage metrics');
        $this->line('  php artisan queue:manage clear');
        
        return 1;
    }

    /**
     * Display health metrics in table format
     */
    protected function displayHealthTable(array $metrics): void
    {
        $headers = ['Queue', 'Size', 'Rate (jobs/min)', 'Failure %', 'Health %', 'ETA (min)'];
        $rows = [];
        
        foreach ($metrics as $queue => $data) {
            if ($queue === 'overall') {
                continue;
            }
            
            $eta = $data['estimated_completion'] ? round($data['estimated_completion'], 1) : 'N/A';
            
            $rows[] = [
                $queue,
                $data['size'],
                round($data['processing_rate'], 1),
                round($data['failure_rate'], 1),
                round($data['health_score'], 1),
                $eta
            ];
        }
        
        $this->table($headers, $rows);
        
        // Overall summary
        $overall = $metrics['overall'];
        $this->line('');
        $this->info('Summary:');
        $this->line("Total Jobs: {$overall['total_jobs']}");
        $this->line("Average Health: " . round($overall['average_health'], 1) . '%');
        $this->line("Active Workers: {$overall['active_workers']}");
        $this->line("Recommended Workers: {$overall['recommended_workers']}");
    }

    /**
     * Display detailed metrics
     */
    protected function displayMetricsTable(array $metrics): void
    {
        $this->info('Queue Performance Metrics');
        $this->line('========================');
        
        // Performance metrics
        if (isset($metrics['performance'])) {
            $perf = $metrics['performance'];
            $this->line("Average Job Duration: " . round($perf['average_job_duration'], 2) . 'ms');
            $this->line("Memory Usage: " . $this->formatBytes($perf['memory_usage']['current']));
            $this->line("Peak Memory: " . $this->formatBytes($perf['memory_usage']['peak']));
            $this->line("CPU Usage: " . round($perf['cpu_usage'], 1) . '%');
            $this->line('');
        }
        
        // Historical data
        if (isset($metrics['historical'])) {
            $this->info('Historical Data:');
            $historical = $metrics['historical'];
            
            if (!empty($historical['hourly_throughput'])) {
                $this->line('Hourly Throughput: ' . count($historical['hourly_throughput']) . ' data points');
            }
            
            if (!empty($historical['daily_averages'])) {
                $this->line('Daily Averages: ' . count($historical['daily_averages']) . ' days');
            }
            
            if (!empty($historical['peak_times'])) {
                $this->line('Peak Times: ' . implode(', ', array_keys($historical['peak_times'])));
            }
        }
    }

    /**
     * Get color for health score
     */
    protected function getHealthColor(float $score): string
    {
        if ($score >= 80) {
            return 'green';
        } elseif ($score >= 60) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}