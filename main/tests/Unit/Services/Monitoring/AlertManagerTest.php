<?php

namespace Tests\Unit\Services\Monitoring;

use Tests\TestCase;
use App\Services\Monitoring\AlertManager;
use App\Services\Monitoring\SystemMonitor;
use Illuminate\Support\Facades\Cache;
use Mockery;

class AlertManagerTest extends TestCase
{
    protected AlertManager $alertManager;
    protected $systemMonitorMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->systemMonitorMock = Mockery::mock(SystemMonitor::class);
        $this->alertManager = new AlertManager($this->systemMonitorMock);
        
        Cache::flush();
    }

    public function test_get_active_alerts_returns_cached_alerts()
    {
        $expectedAlerts = [
            [
                'type' => 'cpu',
                'severity' => 'warning',
                'message' => 'CPU load exceeds threshold',
            ]
        ];

        Cache::put('monitoring:alerts', $expectedAlerts, 5);

        $alerts = $this->alertManager->getActiveAlerts();

        $this->assertEquals($expectedAlerts, $alerts);
    }

    public function test_check_thresholds_generates_cpu_alert_when_exceeded()
    {
        $metrics = [
            'resource_utilization' => [
                'cpu_load_1m' => 5.0, // Exceeds default threshold of 4.0
                'memory_usage_mb' => 100,
            ],
            'queue_health' => [
                'failed_jobs' => 0,
            ],
            'cache_performance' => [
                'hit_rate' => 80,
            ],
            'database_performance' => [
                'slow_queries' => 0,
            ],
            'error_rates' => [
                'error_rate' => 0,
            ],
        ];

        $this->systemMonitorMock
            ->shouldReceive('collectMetrics')
            ->once()
            ->andReturn($metrics);

        $alerts = $this->invokeMethod($this->alertManager, 'checkThresholds');

        $this->assertCount(1, $alerts);
        $this->assertEquals('cpu', $alerts[0]['type']);
        $this->assertEquals('warning', $alerts[0]['severity']);
    }

    public function test_check_thresholds_generates_critical_cpu_alert_when_severely_exceeded()
    {
        $metrics = [
            'resource_utilization' => [
                'cpu_load_1m' => 8.0, // Exceeds threshold by 2x (critical)
                'memory_usage_mb' => 100,
            ],
            'queue_health' => [
                'failed_jobs' => 0,
            ],
            'cache_performance' => [
                'hit_rate' => 80,
            ],
            'database_performance' => [
                'slow_queries' => 0,
            ],
            'error_rates' => [
                'error_rate' => 0,
            ],
        ];

        $this->systemMonitorMock
            ->shouldReceive('collectMetrics')
            ->once()
            ->andReturn($metrics);

        $alerts = $this->invokeMethod($this->alertManager, 'checkThresholds');

        $this->assertCount(1, $alerts);
        $this->assertEquals('cpu', $alerts[0]['type']);
        $this->assertEquals('critical', $alerts[0]['severity']);
    }

    public function test_check_thresholds_generates_memory_alert_when_exceeded()
    {
        $metrics = [
            'resource_utilization' => [
                'cpu_load_1m' => 2.0,
                'memory_usage_mb' => 500, // 500MB / 512MB threshold = 97.6% (exceeds 85%)
            ],
            'queue_health' => [
                'failed_jobs' => 0,
            ],
            'cache_performance' => [
                'hit_rate' => 80,
            ],
            'database_performance' => [
                'slow_queries' => 0,
            ],
            'error_rates' => [
                'error_rate' => 0,
            ],
        ];

        $this->systemMonitorMock
            ->shouldReceive('collectMetrics')
            ->once()
            ->andReturn($metrics);

        $alerts = $this->invokeMethod($this->alertManager, 'checkThresholds');

        $this->assertCount(1, $alerts);
        $this->assertEquals('memory', $alerts[0]['type']);
    }

    public function test_check_thresholds_generates_failed_jobs_alert()
    {
        $metrics = [
            'resource_utilization' => [
                'cpu_load_1m' => 2.0,
                'memory_usage_mb' => 100,
            ],
            'queue_health' => [
                'failed_jobs' => 150, // Exceeds default threshold of 100
            ],
            'cache_performance' => [
                'hit_rate' => 80,
            ],
            'database_performance' => [
                'slow_queries' => 0,
            ],
            'error_rates' => [
                'error_rate' => 0,
            ],
        ];

        $this->systemMonitorMock
            ->shouldReceive('collectMetrics')
            ->once()
            ->andReturn($metrics);

        $alerts = $this->invokeMethod($this->alertManager, 'checkThresholds');

        $this->assertCount(1, $alerts);
        $this->assertEquals('failed_jobs', $alerts[0]['type']);
    }

    public function test_check_thresholds_generates_cache_hit_rate_alert()
    {
        $metrics = [
            'resource_utilization' => [
                'cpu_load_1m' => 2.0,
                'memory_usage_mb' => 100,
            ],
            'queue_health' => [
                'failed_jobs' => 0,
            ],
            'cache_performance' => [
                'hit_rate' => 50, // Below threshold of 60
            ],
            'database_performance' => [
                'slow_queries' => 0,
            ],
            'error_rates' => [
                'error_rate' => 0,
            ],
        ];

        $this->systemMonitorMock
            ->shouldReceive('collectMetrics')
            ->once()
            ->andReturn($metrics);

        $alerts = $this->invokeMethod($this->alertManager, 'checkThresholds');

        $this->assertCount(1, $alerts);
        $this->assertEquals('cache_hit_rate', $alerts[0]['type']);
    }

    public function test_clear_alerts_removes_cached_alerts()
    {
        Cache::put('monitoring:alerts', [['type' => 'test']], 3600);

        $this->alertManager->clearAlerts();

        $this->assertNull(Cache::get('monitoring:alerts'));
    }

    /**
     * Invoke protected/private method for testing
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

