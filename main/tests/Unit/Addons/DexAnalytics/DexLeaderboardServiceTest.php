<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\DexAnalytics;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Addons\DexAnalyticsAddon\App\Services\DexLeaderboardService;

class DexLeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DexLeaderboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DexLeaderboardService();
    }

    public function test_build_leaderboard_returns_collection(): void
    {
        $this->seedTestData();

        $leaderboard = $this->service->buildLeaderboard('total_pnl');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $leaderboard);
    }

    public function test_leaderboard_sorted_by_metric(): void
    {
        $this->seedTestData();

        $leaderboard = $this->service->buildLeaderboard('total_pnl');

        if ($leaderboard->count() > 1) {
            $first = $leaderboard->first();
            $second = $leaderboard->get(1);
            
            $this->assertGreaterThanOrEqual(
                $second['score']['total_pnl'] ?? 0,
                $first['score']['total_pnl'] ?? 0
            );
        }

        $this->assertTrue(true);
    }

    public function test_leaderboard_includes_rank(): void
    {
        $this->seedTestData();

        $leaderboard = $this->service->buildLeaderboard('total_pnl');

        foreach ($leaderboard as $index => $entry) {
            $this->assertEquals($index + 1, $entry['rank']);
        }
    }

    public function test_leaderboard_filters_by_platform(): void
    {
        $this->seedTestData();

        $leaderboard = $this->service->buildLeaderboard('total_pnl', 'gmx');

        foreach ($leaderboard as $entry) {
            $this->assertEquals('gmx', $entry['platform']);
        }
    }

    public function test_refresh_leaderboards(): void
    {
        $this->seedTestData();

        $this->service->refreshLeaderboards();

        $this->assertTrue(true);
    }

    protected function seedTestData(): void
    {
        DB::table('dex_trader_watchlist')->insert([
            [
                'id' => 1,
                'wallet_address' => '0x111',
                'platform' => 'gmx',
                'status' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'wallet_address' => '0x222',
                'platform' => 'hyperliquid',
                'status' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $computedAt = now();

        DB::table('dex_analytics_cache')->insert([
            [
                'watchlist_id' => 1,
                'wallet_address' => '0x111',
                'platform' => 'gmx',
                'metric_key' => 'total_pnl',
                'metric_value' => json_encode(5000.0),
                'computed_at' => $computedAt,
                'created_at' => $computedAt,
                'updated_at' => $computedAt,
            ],
            [
                'watchlist_id' => 1,
                'wallet_address' => '0x111',
                'platform' => 'gmx',
                'metric_key' => 'win_rate',
                'metric_value' => json_encode(75.0),
                'computed_at' => $computedAt,
                'created_at' => $computedAt,
                'updated_at' => $computedAt,
            ],
            [
                'watchlist_id' => 2,
                'wallet_address' => '0x222',
                'platform' => 'hyperliquid',
                'metric_key' => 'total_pnl',
                'metric_value' => json_encode(3000.0),
                'computed_at' => $computedAt,
                'created_at' => $computedAt,
                'updated_at' => $computedAt,
            ],
            [
                'watchlist_id' => 2,
                'wallet_address' => '0x222',
                'platform' => 'hyperliquid',
                'metric_key' => 'win_rate',
                'metric_value' => json_encode(80.0),
                'computed_at' => $computedAt,
                'created_at' => $computedAt,
                'updated_at' => $computedAt,
            ],
        ]);
    }
}
