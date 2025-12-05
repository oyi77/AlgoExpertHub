<?php

namespace Tests\Feature;

use Tests\TestCase;

class SignalExecutionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test signal execution service has risk calculator integration.
     */
    public function test_signal_execution_service_has_risk_calculator_integration()
    {
        $service = app(\Addons\TradingExecutionEngine\App\Services\SignalExecutionService::class);
        $reflection = new \ReflectionClass($service);
        
        $this->assertTrue($reflection->hasMethod('calculatePositionSize'));
        $this->assertTrue($reflection->hasMethod('getPresetForConnection'));
    }
}
