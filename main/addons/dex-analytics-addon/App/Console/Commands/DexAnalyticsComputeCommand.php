<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Console\Commands;

use Addons\DexAnalyticsAddon\App\Services\DexAnalyticsComputationService;
use Addons\DexAnalyticsAddon\App\Services\DexCopySuitabilityService;
use Addons\DexAnalyticsAddon\App\Services\DexTimeBasedMetricsService;
use Illuminate\Console\Command;

class DexAnalyticsComputeCommand extends Command
{
    protected $signature = 'dex-analytics:compute {--period=all : Time period for metrics (1d, 7d, 30d, all)}';
    protected $description = 'Compute advanced DEX analytics metrics including Sharpe ratio, copy suitability, and time-based metrics';

    public function __construct(
        private readonly DexAnalyticsComputationService $computationService,
        private readonly DexCopySuitabilityService $copySuitabilityService,
        private readonly DexTimeBasedMetricsService $timeBasedMetricsService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Computing DEX analytics metrics...');
        $this->newLine();

        // Step 1: Compute basic metrics for all traders
        $this->info('1/4 Computing basic metrics...');
        $startTime = microtime(true);
        $this->computationService->computeAllMetrics();
        $duration = round(microtime(true) - $startTime, 2);
        $this->info("   ✓ Basic metrics computed in {$duration}s");
        $this->newLine();

        // Step 2: Compute copy suitability scores
        $this->info('2/4 Computing copy suitability scores...');
        $startTime = microtime(true);
        $scores = $this->copySuitabilityService->calculateAllScores();
        $duration = round(microtime(true) - $startTime, 2);
        $this->info("   ✓ Copy suitability computed for " . count($scores) . " traders in {$duration}s");
        $this->newLine();

        // Step 3: Compute time-based metrics
        $period = $this->option('period');
        $this->info("3/4 Computing time-based metrics ({$period})...");
        $startTime = microtime(true);
        $this->timeBasedMetricsService->computeAllTimeBasedMetrics();
        $duration = round(microtime(true) - $startTime, 2);
        $this->info("   ✓ Time-based metrics computed in {$duration}s");
        $this->newLine();

        // Step 4: Summary
        $this->info('4/4 Summary');
        $totalTraders = \Illuminate\Support\Facades\DB::table('dex_trader_watchlist')->where('is_active', true)->count();
        $avgScore = count($scores) > 0 ? round(array_sum(array_column($scores, 'overall_score')) / count($scores), 1) : 0;

        $this->info("   Total active traders: {$totalTraders}");
        $this->info("   Average copy suitability score: {$avgScore}");

        $excellentTraders = count(array_filter($scores, fn ($s) => $s['overall_score'] >= 80));
        $goodTraders = count(array_filter($scores, fn ($s) => $s['overall_score'] >= 60 && $s['overall_score'] < 80));
        $poorTraders = count(array_filter($scores, fn ($s) => $s['overall_score'] < 40));

        $this->info("   Excellent (A): {$excellentTraders}");
        $this->info("   Good (B): {$goodTraders}");
        $this->info("   Poor (D-F): {$poorTraders}");
        $this->newLine();

        $this->info('✓ DEX analytics metrics computation complete!');

        return 0;
    }
}
