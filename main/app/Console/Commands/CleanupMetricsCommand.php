<?php

namespace App\Console\Commands;

use App\Services\Analytics\MetricsCollector;
use Illuminate\Console\Command;

class CleanupMetricsCommand extends Command
{
    protected $signature = 'metrics:cleanup {--days=90 : Number of days to keep}';
    protected $description = 'Clean up old metrics data';

    protected MetricsCollector $collector;

    public function __construct(MetricsCollector $collector)
    {
        parent::__construct();
        $this->collector = $collector;
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up metrics older than {$days} days...");

        $deleted = $this->collector->cleanup($days);

        $this->info("Deleted {$deleted} old metric records.");

        return 0;
    }
}
