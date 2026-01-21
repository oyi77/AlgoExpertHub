<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Console\Commands;

use Addons\DexAnalyticsAddon\App\Jobs\PollDexPositionsJob;
use Illuminate\Console\Command;

class DexAnalyticsPollCommand extends Command
{
    protected $signature = 'dex-analytics:poll';
    protected $description = 'Poll DEX platforms for trader positions';

    public function handle(): int
    {
        $this->info('Starting DEX position polling...');

        PollDexPositionsJob::dispatch();

        $this->info('Position polling job dispatched successfully.');

        return 0;
    }
}
