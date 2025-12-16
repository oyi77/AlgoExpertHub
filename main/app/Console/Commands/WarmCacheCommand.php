<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheManager;

class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm {--force : Force cache warming even if cache exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up the application cache with frequently accessed data';

    protected $cacheManager;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheManager $cacheManager)
    {
        parent::__construct();
        $this->cacheManager = $cacheManager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting cache warming...');

        $startTime = microtime(true);

        try {
            // Clear cache if force option is used
            if ($this->option('force')) {
                $this->info('Force option detected, clearing existing cache...');
                $this->cacheManager->clearAll();
            }

            // Warm the cache
            $this->cacheManager->warmCache();

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $this->info("Cache warming completed successfully in {$duration}ms");

            // Display cache statistics
            $stats = $this->cacheManager->getStats();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Cache Hits', $stats['hits']],
                    ['Cache Misses', $stats['misses']],
                    ['Hit Rate', round($stats['hit_rate'], 2) . '%'],
                    ['Total Requests', $stats['total_requests']]
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cache warming failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}