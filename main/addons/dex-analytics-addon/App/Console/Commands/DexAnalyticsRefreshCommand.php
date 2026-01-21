<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Console\Commands;

use Addons\DexAnalyticsAddon\App\Jobs\RefreshDexAnalyticsJob;
use Illuminate\Console\Command;

class DexAnalyticsRefreshCommand extends Command
{
    protected $signature = 'dex-analytics:refresh';
    protected $description = 'Refresh DEX analytics metrics and leaderboards';

    public function handle(): int
    {
        $this->info('Starting DEX analytics refresh...');

        RefreshDexAnalyticsJob::dispatch();

        $this->info('Analytics refresh job dispatched successfully.');

        return 0;
    }
}
