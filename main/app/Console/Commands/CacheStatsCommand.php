<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheManager;

class CacheStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display cache statistics and performance metrics';

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
        $this->info('Cache Statistics');
        $this->line('================');

        // Get basic cache stats
        $stats = $this->cacheManager->getStats();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Hits', number_format($stats['hits'])],
                ['Cache Misses', number_format($stats['misses'])],
                ['Hit Rate', round($stats['hit_rate'], 2) . '%'],
                ['Total Requests', number_format($stats['total_requests'])]
            ]
        );

        // Get cache size information
        $sizeInfo = $this->cacheManager->getCacheSize();
        if (!empty($sizeInfo)) {
            $this->line('');
            $this->info('Cache Size Information');
            $this->line('=====================');
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Keys', number_format($sizeInfo['keys_count'])],
                ]
            );

            // Display memory stats if available
            if (!empty($sizeInfo['memory_stats'])) {
                $memStats = $sizeInfo['memory_stats'];
                $this->line('');
                $this->info('Memory Usage');
                $this->line('============');
                
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Used Memory', $memStats['used_memory_human'] ?? 'N/A'],
                        ['Peak Memory', $memStats['used_memory_peak_human'] ?? 'N/A'],
                    ]
                );
            }
        }

        // Cache configuration info
        $this->line('');
        $this->info('Cache Configuration');
        $this->line('==================');
        
        $this->table(
            ['Setting', 'Value'],
            [
                ['Default Driver', config('cache.default')],
                ['Cache Prefix', config('cache.prefix')],
            ]
        );

        return Command::SUCCESS;
    }
}