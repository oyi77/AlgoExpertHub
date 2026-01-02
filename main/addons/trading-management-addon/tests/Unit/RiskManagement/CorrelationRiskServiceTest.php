<?php

namespace Tests\Unit\RiskManagement;

use Tests\TestCase;
use Addons\TradingManagement\Modules\RiskManagement\Services\CorrelationRiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CorrelationRiskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CorrelationRiskService::class);
    }

    /** @test */
    public function it_returns_correlation_matrix()
    {
        $matrix = $this->service->getCorrelationMatrix();

        $this->assertIsArray($matrix);
        $this->assertNotEmpty($matrix);
    }

    /** @test */
    public function it_gets_correlated_symbols()
    {
        // EUR/USD is highly correlated with GBP/USD
        $correlated = $this->service->getCorrelatedSymbols('EURUSD', 0.7);

        $this->assertIsArray($correlated);
        // Should include GBPUSD if correlation > 0.7
    }

    /** @test */
    public function it_calculates_exposure()
    {
        $existingPositions = [
            ['symbol' => 'GBPUSD', 'value' => 5000], // Highly correlated with EUR/USD
        ];

        $exposure = $this->service->calculateExposure(
            'EURUSD',
            $existingPositions,
            3000.0, // new position value
            10000.0 // equity
        );

        $this->assertIsArray($exposure);
        $this->assertArrayHasKey('total_exposure_pct', $exposure);
    }

    /** @test */
    public function it_prevents_trade_with_high_correlation_exposure()
    {
        // Existing position in GBP/USD (highly correlated with EUR/USD)
        $existingPositions = [
            ['symbol' => 'GBPUSD', 'value' => 6000], // 60% of equity
        ];

        $result = $this->service->shouldPreventTrade(
            'EURUSD', // New symbol (correlated)
            $existingPositions,
            2000.0,   // New position value (20% of equity)
            10000.0,  // Equity
            50.0      // Max 50% correlation exposure
        );

        // Total exposure should exceed 50% (60% existing + 20% new = 80%)
        $this->assertTrue($result['should_prevent']);
    }

    /** @test */
    public function it_allows_trade_with_low_correlation_exposure()
    {
        $existingPositions = [
            ['symbol' => 'USDJPY', 'value' => 2000], // Low correlation with EUR/USD
        ];

        $result = $this->service->shouldPreventTrade(
            'EURUSD',
            $existingPositions,
            2000.0,
            10000.0,
            50.0
        );

        $this->assertFalse($result['should_prevent']);
    }
}

