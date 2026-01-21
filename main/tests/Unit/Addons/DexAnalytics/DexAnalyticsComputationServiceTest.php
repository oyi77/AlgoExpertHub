<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\DexAnalytics;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Addons\DexAnalyticsAddon\App\Services\DexAnalyticsComputationService;

class DexAnalyticsComputationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DexAnalyticsComputationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DexAnalyticsComputationService();
    }

    public function test_compute_metrics_for_watchlist_with_trades(): void
    {
        $watchlistId = 1;

        DB::table('dex_trader_watchlist')->insert([
            'id' => $watchlistId,
            'wallet_address' => '0x123',
            'platform' => 'gmx',
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('dex_pnl_records')->insert([
            [
                'watchlist_id' => $watchlistId,
                'wallet_address' => '0x123',
                'platform' => 'gmx',
                'symbol' => 'BTC-USD',
                'realized_pnl' => 1000.0,
                'size' => 1.0,
                'funding_cost' => -10.0,
                'closed_at' => now()->subHours(2),
                'raw_payload' => json_encode(['opened_at' => now()->subHours(4)->timestamp, 'closed_at' => now()->subHours(2)->timestamp]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'watchlist_id' => $watchlistId,
                'wallet_address' => '0x123',
                'platform' => 'gmx',
                'symbol' => 'ETH-USD',
                'realized_pnl' => -500.0,
                'size' => 10.0,
                'funding_cost' => -5.0,
                'closed_at' => now()->subHours(1),
                'raw_payload' => json_encode(['opened_at' => now()->subHours(3)->timestamp, 'closed_at' => now()->subHours(1)->timestamp]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'watchlist_id' => $watchlistId,
                'wallet_address' => '0x123',
                'platform' => 'gmx',
                'symbol' => 'SOL-USD',
                'realized_pnl' => 500.0,
                'size' => 100.0,
                'funding_cost' => -2.0,
                'closed_at' => now(),
                'raw_payload' => json_encode(['opened_at' => now()->subHours(1)->timestamp, 'closed_at' => now()->timestamp]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('dex_position_snapshots')->insert([
            'watchlist_id' => $watchlistId,
            'wallet_address' => '0x123',
            'platform' => 'gmx',
            'symbol' => 'BTC-USD',
            'size' => 2.0,
            'snapshot_at' => now(),
            'raw_payload' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $metrics = $this->service->computeMetricsForWatchlist($watchlistId);

        $this->assertArrayHasKey('total_pnl', $metrics);
        $this->assertArrayHasKey('win_rate', $metrics);
        $this->assertArrayHasKey('avg_holding_time', $metrics);
        $this->assertArrayHasKey('profit_factor', $metrics);
        $this->assertArrayHasKey('max_drawdown', $metrics);
        $this->assertArrayHasKey('avg_trade_size', $metrics);
        $this->assertArrayHasKey('funding_cost_ratio', $metrics);
        $this->assertArrayHasKey('liquidation_rate', $metrics);
        $this->assertArrayHasKey('total_exposure', $metrics);

        $this->assertEquals(1000.0, $metrics['total_pnl']);
        $this->assertEquals(66.67, $metrics['win_rate']);
        $this->assertEquals(2.0, $metrics['total_exposure']);
    }

    public function test_compute_metrics_for_empty_watchlist(): void
    {
        $watchlistId = 999;

        DB::table('dex_trader_watchlist')->insert([
            'id' => $watchlistId,
            'wallet_address' => '0x999',
            'platform' => 'gmx',
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $metrics = $this->service->computeMetricsForWatchlist($watchlistId);

        $this->assertEquals(0.0, $metrics['total_pnl']);
        $this->assertEquals(0.0, $metrics['win_rate']);
        $this->assertEquals(0.0, $metrics['avg_holding_time']);
    }

    public function test_compute_and_cache_metrics(): void
    {
        $watchlistId = 2;

        DB::table('dex_trader_watchlist')->insert([
            'id' => $watchlistId,
            'wallet_address' => '0x456',
            'platform' => 'hyperliquid',
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('dex_pnl_records')->insert([
            'watchlist_id' => $watchlistId,
            'wallet_address' => '0x456',
            'platform' => 'hyperliquid',
            'symbol' => 'BTC-USD',
            'realized_pnl' => 2000.0,
            'size' => 1.5,
            'funding_cost' => -15.0,
            'closed_at' => now(),
            'raw_payload' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $metrics = $this->service->computeAndCacheMetrics($watchlistId);

        $this->assertArrayHasKey('total_pnl', $metrics);

        $cached = DB::table('dex_analytics_cache')
            ->where('watchlist_id', $watchlistId)
            ->get();

        $this->assertGreaterThan(0, $cached->count());
    }

    public function test_compute_all_metrics(): void
    {
        DB::table('dex_trader_watchlist')->insert([
            [
                'id' => 10,
                'wallet_address' => '0xaaa',
                'platform' => 'gmx',
                'status' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'wallet_address' => '0xbbb',
                'platform' => 'hyperliquid',
                'status' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'wallet_address' => '0xccc',
                'platform' => 'aster',
                'status' => 'inactive',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->service->computeAllMetrics();

        $cached = DB::table('dex_analytics_cache')->get();

        $activeWallets = $cached->pluck('wallet_address')->unique()->all();
        $this->assertContains('0xaaa', $activeWallets);
        $this->assertContains('0xbbb', $activeWallets);
        $this->assertNotContains('0xccc', $activeWallets);
    }
}
