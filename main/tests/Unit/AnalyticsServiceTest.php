<?php

namespace Tests\Unit;

use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test analytics service methods exist.
     */
    public function test_analytics_service_has_export_methods()
    {
        $service = app(\Addons\TradingExecutionEngine\App\Services\AnalyticsService::class);
        $reflection = new \ReflectionClass($service);
        
        $this->assertTrue($reflection->hasMethod('exportToCsv'));
        $this->assertTrue($reflection->hasMethod('exportToJson'));
        $this->assertTrue($reflection->hasMethod('compareChannels'));
        $this->assertTrue($reflection->hasMethod('calculateSharpeRatio'));
    }
}
