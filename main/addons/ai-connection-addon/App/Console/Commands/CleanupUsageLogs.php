<?php

namespace Addons\AiConnectionAddon\App\Console\Commands;

use Addons\AiConnectionAddon\App\Models\AiConnectionUsage;
use Illuminate\Console\Command;

class CleanupUsageLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-connections:cleanup-usage
                            {--days=30 : Delete logs older than this many days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old AI connection usage logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up usage logs older than {$days} days...");

        $query = AiConnectionUsage::where('created_at', '<', now()->subDays($days));
        
        $count = $query->count();

        if ($count === 0) {
            $this->info('No old logs to cleanup.');
            return 0;
        }

        if ($dryRun) {
            $this->warn("[DRY RUN] Would delete {$count} usage log records.");
            
            // Show sample of what would be deleted
            $samples = $query->limit(5)->get();
            if ($samples->isNotEmpty()) {
                $this->newLine();
                $this->info('Sample records that would be deleted:');
                $headers = ['ID', 'Connection ID', 'Feature', 'Date'];
                $rows = $samples->map(function ($log) {
                    return [
                        $log->id,
                        $log->connection_id,
                        $log->feature,
                        $log->created_at->format('Y-m-d H:i:s'),
                    ];
                })->toArray();
                $this->table($headers, $rows);
            }

            return 0;
        }

        if ($this->confirm("Delete {$count} old usage log records?", true)) {
            $deleted = $query->delete();
            $this->info("✓ Deleted {$deleted} usage log records.");
        } else {
            $this->warn('Cleanup cancelled.');
        }

        return 0;
    }
}

