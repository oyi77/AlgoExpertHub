<?php

namespace Tests\Unit\RiskManagement;

use Tests\TestCase;
use Addons\TradingManagement\Modules\RiskManagement\Services\MarginManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarginManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarginManagementService::class);
    }

    /** @test */
    public function it_calculates_required_margin_with_leverage()
    {
        // For 1.0 lot EUR/USD at 1.1000 with 1:100 leverage
        // Notional value = 1.0 * 100000 * 1.1000 = 110,000
        // Required margin = 110,000 / 100 = 1,100
        $margin = $this->service->calculateRequiredMargin(
            1.0,      // lot size
            1.1000,   // entry price
            100,      // leverage (1:100)
            'EURUSD'
        );

        $this->assertEquals(1100.0, $margin);
    }

    /** @test */
    public function it_calculates_margin_with_different_leverage()
    {
        // Test with 1:50 leverage
        $margin = $this->service->calculateRequiredMargin(
            1.0,
            1.1000,
            50,
            'EURUSD'
        );

        $this->assertEquals(2200.0, $margin); // 110,000 / 50
    }

    /** @test */
    public function it_checks_margin_level()
    {
        $accountInfo = [
            'balance' => 10000,
            'equity' => 9500,
            'margin' => 1000,
            'free_margin' => 8500,
        ];

        $result = $this->service->checkMarginLevel($accountInfo);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('margin_level', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals(950.0, $result['margin_level']); // (9500 / 1000) * 100
        $this->assertEquals('safe', $result['status']);
    }

    /** @test */
    public function it_detects_margin_call()
    {
        // Margin level below 100% should trigger margin call
        $accountInfo = [
            'balance' => 10000,
            'equity' => 900,
            'margin' => 1000,
            'free_margin' => -100,
        ];

        $shouldCall = $this->service->shouldTriggerMarginCall($accountInfo, 100.0);

        $this->assertTrue($shouldCall);
    }

    /** @test */
    public function it_prevents_trade_with_insufficient_margin()
    {
        $accountInfo = [
            'balance' => 1000,
            'equity' => 1000,
            'margin' => 500,
            'free_margin' => 500,
        ];

        $requiredMargin = 600.0; // More than free margin
        $config = ['max_margin_usage_pct' => 80.0];

        $result = $this->service->shouldPreventTrade($accountInfo, $requiredMargin, $config);

        $this->assertTrue($result['should_prevent']);
        $this->assertStringContainsString('insufficient', strtolower($result['reason']));
    }

    /** @test */
    public function it_allows_trade_with_sufficient_margin()
    {
        $accountInfo = [
            'balance' => 10000,
            'equity' => 10000,
            'margin' => 1000,
            'free_margin' => 9000,
        ];

        $requiredMargin = 2000.0;
        $config = ['max_margin_usage_pct' => 80.0];

        $result = $this->service->shouldPreventTrade($accountInfo, $requiredMargin, $config);

        $this->assertFalse($result['should_prevent']);
    }
}

