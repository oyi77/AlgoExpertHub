<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PositionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PositionService $positionService;

    /**
     * Test trailing stop functionality exists.
     */
    public function test_trailing_stop_service_method_exists()
    {
        $service = app(\Addons\TradingExecutionEngine\App\Services\PositionService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('handleTrailingStop'));
    }

    /**
     * Test breakeven functionality exists.
     */
    public function test_breakeven_service_method_exists()
    {
        $service = app(\Addons\TradingExecutionEngine\App\Services\PositionService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('handleBreakeven'));
    }
}
