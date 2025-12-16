<?php

namespace App\Console\Commands;

use App\Services\Monitoring\SystemMonitor;
use Illuminate\Console\Command;

class MonitorSystemCommand extends Command
{
    protected $signature = 'monitor:system {--alert : Check and send alerts}';
    protected $description = 'Monitor system metrics and performance';

    protected SystemMonitor $monitor;

    public function __construct(SystemMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    public function handle(): int
    {
        $this->info('Collecting system metrics...');

        $metrics = $this->monitor->collectMetrics();

        $this->displayMetrics($metrics);

        if ($this->option('alert')) {
            $this->checkAlerts();
        }

        return 0;
    }

    protected function displayMetrics(array $metrics): void
    {
        $this->info('Response Times:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Average', number_format($metrics['response_times']['avg'], 2) . 'ms'],
                ['P50', number_format($metrics['response_times']['p50'], 2) . 'ms'],
                ['P95', number_format($metrics['response_times']['p95'], 2) . 'ms'],
                ['P99', number_format($metrics['response_times']['p99'], 2) . 'ms'],
            ]
        );

        $this->info('Error Rates:');
        $this->line('Total Requests: ' . $metrics['error_rates']['total_requests']);
        $this->line('Error Requests: ' . $metrics['error_rates']['error_requests']);
        $this->line('Error Rate: ' . number_format($metrics['error_rates']['error_rate'], 2) . '%');

        $this->info('Resource Utilization:');
        $this->line('CPU Load (1m): ' . $metrics['resource_utilization']['cpu_load_1m']);
        $this->line('Memory Usage: ' . number_format($metrics['resource_utilization']['memory_usage_mb'], 2) . 'MB');

        $this->info('Queue Health:');
        $this->line('Pending Jobs: ' . $metrics['queue_health']['pending_jobs']);
        $this->line('Failed Jobs: ' . $metrics['queue_health']['failed_jobs']);
    }

    protected function checkAlerts(): void
    {
        $this->info('Checking alert thresholds...');

        $alerts = $this->monitor->checkAlerts();

        if (empty($alerts)) {
            $this->info('No alerts triggered.');
            return;
        }

        $this->warn('Alerts detected:');
        foreach ($alerts as $alert) {
            $this->line("[{$alert['severity']}] {$alert['message']} (Value: {$alert['value']})");
        }
    }
}
