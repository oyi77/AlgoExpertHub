<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\DexAnalytics;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Addons\DexAnalyticsAddon\App\Services\DexAiIntelligenceService;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Mockery;

class DexAiIntelligenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DexAiIntelligenceService $service;
    protected $aiConnectionServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aiConnectionServiceMock = Mockery::mock(AiConnectionService::class);
        $this->service = new DexAiIntelligenceService($this->aiConnectionServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_insights_for_trader_with_valid_connection(): void
    {
        config(['dex-analytics.ai.default_connection_id' => 1]);
        config(['dex-analytics.ai.model' => 'gpt-4']);

        $metrics = [
            'total_pnl' => 5000.0,
            'win_rate' => 75.0,
            'profit_factor' => 2.5,
        ];

        $this->aiConnectionServiceMock
            ->shouldReceive('execute')
            ->once()
            ->with(1, Mockery::type('string'), Mockery::type('array'))
            ->andReturn(['insight' => 'Strong performance']);

        $result = $this->service->generateInsightsForTrader(1, $metrics);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_generate_insights_for_trader_without_connection(): void
    {
        config(['dex-analytics.ai.default_connection_id' => null]);

        $metrics = ['total_pnl' => 1000.0];

        $result = $this->service->generateInsightsForTrader(1, $metrics);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No AI connection', $result['message']);
    }

    public function test_generate_insights_for_trader_with_ai_error(): void
    {
        config(['dex-analytics.ai.default_connection_id' => 1]);

        $metrics = ['total_pnl' => 1000.0];

        $this->aiConnectionServiceMock
            ->shouldReceive('execute')
            ->once()
            ->andThrow(new \Exception('API rate limit exceeded'));

        $result = $this->service->generateInsightsForTrader(1, $metrics);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('API rate limit', $result['message']);
    }

    public function test_generate_insights_processes_all_active_watchlists(): void
    {
        DB::table('dex_trader_watchlist')->insert([
            [
                'id' => 1,
                'wallet_address' => '0x111',
                'platform' => 'gmx',
                'is_active' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'wallet_address' => '0x222',
                'platform' => 'hyperliquid',
                'is_active' => false,
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('dex_analytics_cache')->insert([
            'watchlist_id' => 1,
            'wallet_address' => '0x111',
            'platform' => 'gmx',
            'metric_key' => 'total_pnl',
            'metric_value' => json_encode(1000.0),
            'computed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config(['dex-analytics.ai.default_connection_id' => 1]);

        $this->aiConnectionServiceMock
            ->shouldReceive('execute')
            ->once()
            ->andReturn(['insight' => 'Test']);

        $this->service->generateInsights();

        $this->assertTrue(true);
    }

    public function test_cluster_behaviors_returns_array(): void
    {
        $traders = [
            ['win_rate' => 75.0, 'avg_holding_time' => 3600],
            ['win_rate' => 80.0, 'avg_holding_time' => 7200],
        ];

        $result = $this->service->clusterBehaviors($traders);

        $this->assertIsArray($result);
    }
}
