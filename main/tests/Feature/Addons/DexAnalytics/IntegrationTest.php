<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\DexAnalytics;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Addons\DexAnalyticsAddon\App\Jobs\PollDexPositionsJob;
use Addons\DexAnalyticsAddon\App\Jobs\RefreshDexAnalyticsJob;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_poll_positions_job_can_be_dispatched(): void
    {
        Queue::fake();

        PollDexPositionsJob::dispatch();

        Queue::assertPushed(PollDexPositionsJob::class);
    }

    public function test_refresh_analytics_job_can_be_dispatched(): void
    {
        Queue::fake();

        RefreshDexAnalyticsJob::dispatch();

        Queue::assertPushed(RefreshDexAnalyticsJob::class);
    }

    public function test_full_analytics_pipeline(): void
    {
        DB::table('dex_trader_watchlist')->insert([
            'id' => 1,
            'wallet_address' => '0x123',
            'platform' => 'gmx',
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('dex_pnl_records')->insert([
            'watchlist_id' => 1,
            'wallet_address' => '0x123',
            'platform' => 'gmx',
            'symbol' => 'BTC-USD',
            'realized_pnl' => 1000.0,
            'size' => 1.0,
            'funding_cost' => -10.0,
            'closed_at' => now(),
            'raw_payload' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $computationService = app(\Addons\DexAnalyticsAddon\App\Services\DexAnalyticsComputationService::class);
        $leaderboardService = app(\Addons\DexAnalyticsAddon\App\Services\DexLeaderboardService::class);

        $metrics = $computationService->computeAndCacheMetrics(1);

        $this->assertArrayHasKey('total_pnl', $metrics);
        $this->assertEquals(1000.0, $metrics['total_pnl']);

        $cached = DB::table('dex_analytics_cache')
            ->where('watchlist_id', 1)
            ->count();

        $this->assertGreaterThan(0, $cached);

        $leaderboard = $leaderboardService->buildLeaderboard('total_pnl');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $leaderboard);
    }

    public function test_position_snapshot_storage_with_provenance(): void
    {
        DB::table('dex_trader_watchlist')->insert([
            'id' => 1,
            'wallet_address' => '0xabc',
            'platform' => 'hyperliquid',
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $snapshotService = app(\Addons\DexAnalyticsAddon\App\Services\DexPositionSnapshotService::class);

        $position = [
            'watchlist_id' => 1,
            'wallet_address' => '0xabc',
            'platform' => 'hyperliquid',
            'symbol' => 'ETH-USD',
            'side' => 'long',
            'size' => 10.0,
            'entry_price' => 2000.0,
            'mark_price' => 2100.0,
            'unrealized_pnl' => 1000.0,
            'snapshot_at' => now()->toDateTimeString(),
            'raw_payload' => ['test' => 'data'],
        ];

        $snapshotService->capturePosition($position);

        $this->assertDatabaseHas('dex_position_snapshots', [
            'wallet_address' => '0xabc',
            'platform' => 'hyperliquid',
            'symbol' => 'ETH-USD',
        ]);

        $this->assertDatabaseHas('dex_provenance_logs', [
            'wallet_address' => '0xabc',
            'platform' => 'hyperliquid',
            'operation' => 'position_snapshot',
        ]);
    }

    public function test_performance_metrics_computation_speed(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            DB::table('dex_trader_watchlist')->insert([
                'id' => $i,
                'wallet_address' => "0x{$i}",
                'platform' => 'gmx',
                'status' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            for ($j = 1; $j <= 100; $j++) {
                DB::table('dex_pnl_records')->insert([
                    'watchlist_id' => $i,
                    'wallet_address' => "0x{$i}",
                    'platform' => 'gmx',
                    'symbol' => 'BTC-USD',
                    'realized_pnl' => rand(-1000, 5000),
                    'size' => rand(1, 10),
                    'funding_cost' => rand(-50, 50),
                    'closed_at' => now()->subMinutes(rand(1, 1000)),
                    'raw_payload' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $computationService = app(\Addons\DexAnalyticsAddon\App\Services\DexAnalyticsComputationService::class);

        $startTime = microtime(true);

        $computationService->computeAllMetrics();

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertLessThan(30, $duration, 'Metrics computation should complete within 30 seconds for 10 traders with 100 trades each');

        $cached = DB::table('dex_analytics_cache')->count();
        $this->assertGreaterThan(0, $cached);
    }

    public function test_leaderboard_generation_with_multiple_platforms(): void
    {
        $platforms = ['gmx', 'hyperliquid', 'aster'];

        foreach ($platforms as $index => $platform) {
            DB::table('dex_trader_watchlist')->insert([
                'id' => $index + 1,
                'wallet_address' => "0x{$platform}",
                'platform' => $platform,
                'status' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pnl = ($index + 1) * 1000;

            DB::table('dex_analytics_cache')->insert([
                'watchlist_id' => $index + 1,
                'wallet_address' => "0x{$platform}",
                'platform' => $platform,
                'metric_key' => 'total_pnl',
                'metric_value' => json_encode($pnl),
                'computed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $leaderboardService = app(\Addons\DexAnalyticsAddon\App\Services\DexLeaderboardService::class);

        $allPlatforms = $leaderboardService->buildLeaderboard('total_pnl');
        $this->assertEquals(3, $allPlatforms->count());

        $gmxOnly = $leaderboardService->buildLeaderboard('total_pnl', 'gmx');
        $this->assertEquals(1, $gmxOnly->count());
        $this->assertEquals('gmx', $gmxOnly->first()['platform']);
    }

    public function test_end_to_end_workflow(): void
    {
        DB::table('dex_trader_watchlist')->insert([
            'id' => 1,
            'wallet_address' => '0xe2e',
            'platform' => 'gmx',
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $normalizationService = app(\Addons\DexAnalyticsAddon\App\Services\DexAnalyticsNormalizationService::class);
        $snapshotService = app(\Addons\DexAnalyticsAddon\App\Services\DexPositionSnapshotService::class);
        $computationService = app(\Addons\DexAnalyticsAddon\App\Services\DexAnalyticsComputationService::class);
        $leaderboardService = app(\Addons\DexAnalyticsAddon\App\Services\DexLeaderboardService::class);

        $rawPosition = [
            'wallet' => '0xe2e',
            'symbol' => 'BTC-USD',
            'side' => 'long',
            'size' => 2.0,
            'entry_price' => 50000.0,
            'mark_price' => 52000.0,
            'unrealized_pnl' => 4000.0,
        ];

        $normalized = $normalizationService->normalizePosition('gmx', $rawPosition);
        $this->assertEquals('0xe2e', $normalized['wallet_address']);

        $normalized['watchlist_id'] = 1;
        $snapshotService->capturePosition($normalized);

        $this->assertDatabaseHas('dex_position_snapshots', [
            'wallet_address' => '0xe2e',
            'symbol' => 'BTC-USD',
        ]);

        DB::table('dex_pnl_records')->insert([
            'watchlist_id' => 1,
            'wallet_address' => '0xe2e',
            'platform' => 'gmx',
            'symbol' => 'BTC-USD',
            'realized_pnl' => 4000.0,
            'size' => 2.0,
            'funding_cost' => -50.0,
            'closed_at' => now(),
            'raw_payload' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $metrics = $computationService->computeAndCacheMetrics(1);
        $this->assertArrayHasKey('total_pnl', $metrics);

        $leaderboard = $leaderboardService->buildLeaderboard('total_pnl');
        $this->assertGreaterThan(0, $leaderboard->count());

        $topTrader = $leaderboard->first();
        $this->assertEquals('0xe2e', $topTrader['wallet_address']);
    }
}
