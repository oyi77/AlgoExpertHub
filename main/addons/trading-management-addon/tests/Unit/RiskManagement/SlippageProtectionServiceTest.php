<?php

namespace Tests\Unit\RiskManagement;

use Tests\TestCase;
use Addons\TradingManagement\Modules\RiskManagement\Services\SlippageProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SlippageProtectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SlippageProtectionService::class);
    }

    /** @test */
    public function it_calculates_slippage_for_buy_order()
    {
        // Expected price 1.1000, executed at 1.1002 = 2 pips slippage
        $slippage = $this->service->calculateSlippage(
            1.1000,  // expected
            1.1002,  // executed
            'buy',
            'EURUSD'
        );

        $this->assertEquals(2.0, $slippage);
    }

    /** @test */
    public function it_calculates_slippage_for_sell_order()
    {
        // Expected price 1.1000, executed at 0.9998 = 2 pips slippage
        $slippage = $this->service->calculateSlippage(
            1.1000,  // expected
            0.9998,  // executed (lower is worse for sell)
            'sell',
            'EURUSD'
        );

        $this->assertEquals(2.0, $slippage);
    }

    /** @test */
    public function it_validates_acceptable_slippage()
    {
        $slippagePips = 2.0;
        $maxAllowed = 5.0;

        $result = $this->service->validateSlippage($slippagePips, $maxAllowed);

        $this->assertTrue($result['acceptable']);
    }

    /** @test */
    public function it_rejects_excessive_slippage()
    {
        $slippagePips = 10.0;
        $maxAllowed = 5.0;

        $result = $this->service->validateSlippage($slippagePips, $maxAllowed);

        $this->assertFalse($result['acceptable']);
        $this->assertStringContainsString('exceeded', strtolower($result['reason']));
    }

    /** @test */
    public function it_predicts_slippage()
    {
        // Prediction is estimated, just verify it returns a non-negative value
        $slippage = $this->service->predictSlippage('EURUSD', 1.0);

        $this->assertGreaterThanOrEqual(0, $slippage);
    }

    /** @test */
    public function it_adjusts_stop_loss_for_slippage()
    {
        // For buy order, slippage increases SL (worse exit)
        $adjustedSL = $this->service->adjustStopLossForSlippage(
            1.0900,  // original SL
            2.0,     // 2 pips slippage
            'sell',  // exit direction
            'EURUSD'
        );

        // SL should be adjusted by slippage (2 pips = 0.0002 for EUR/USD)
        $this->assertGreaterThan(1.0900, $adjustedSL);
    }

    /** @test */
    public function it_gets_max_allowed_slippage()
    {
        $config = ['max_slippage_pips' => 5.0];
        $maxSlippage = $this->service->getMaxAllowedSlippage($config);

        $this->assertEquals(5.0, $maxSlippage);
    }
}

