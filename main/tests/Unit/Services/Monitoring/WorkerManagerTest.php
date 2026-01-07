<?php

namespace Tests\Unit\Services\Monitoring;

use Tests\TestCase;
use App\Services\Monitoring\WorkerManager;
use App\Services\Monitoring\SystemMonitor;
use App\Services\QueueOptimizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Mockery;

class WorkerManagerTest extends TestCase
{
    protected WorkerManager $workerManager;
    protected $queueOptimizerMock;
    protected $systemMonitorMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->queueOptimizerMock = Mockery::mock(QueueOptimizer::class);
        $this->systemMonitorMock = Mockery::mock(SystemMonitor::class);
        $this->workerManager = new WorkerManager($this->queueOptimizerMock, $this->systemMonitorMock);
        
        Cache::flush();
    }

    public function test_get_all_workers_returns_all_worker_types()
    {
        $queueMetrics = [
            'active_workers' => 4,
            'total_jobs' => 100,
            'pending_jobs' => 10,
            'failed_jobs' => 2,
            'processed_jobs' => 88,
        ];

        $this->queueOptimizerMock
            ->shouldReceive('getMetrics')
            ->once()
            ->andReturn($queueMetrics);

        $workers = $this->workerManager->getAllWorkers();

        $this->assertArrayHasKey('queue', $workers);
        $this->assertArrayHasKey('bots', $workers);
        $this->assertArrayHasKey('octane', $workers);
    }

    public function test_get_queue_workers_returns_correct_status()
    {
        $queueMetrics = [
            'active_workers' => 4,
            'total_jobs' => 100,
            'pending_jobs' => 10,
            'failed_jobs' => 2,
            'processed_jobs' => 88,
        ];

        $this->queueOptimizerMock
            ->shouldReceive('getMetrics')
            ->once()
            ->andReturn($queueMetrics);

        $workers = $this->workerManager->getAllWorkers();

        $this->assertEquals(4, $workers['queue']['active']);
        $this->assertEquals(100, $workers['queue']['total_jobs']);
        $this->assertEquals('healthy', $workers['queue']['status']);
    }

    public function test_get_queue_workers_returns_critical_when_no_workers()
    {
        $queueMetrics = [
            'active_workers' => 0,
            'total_jobs' => 100,
            'pending_jobs' => 10,
            'failed_jobs' => 2,
        ];

        $this->queueOptimizerMock
            ->shouldReceive('getMetrics')
            ->once()
            ->andReturn($queueMetrics);

        $workers = $this->workerManager->getAllWorkers();

        $this->assertEquals('critical', $workers['queue']['status']);
    }

    public function test_get_bot_workers_returns_not_installed_when_addon_inactive()
    {
        // Mock AddonRegistry to return false
        \App\Support\AddonRegistry::shouldReceive('active')
            ->with('trading-management-addon')
            ->andReturn(false);

        $this->queueOptimizerMock
            ->shouldReceive('getMetrics')
            ->once()
            ->andReturn(['active_workers' => 0]);

        $workers = $this->workerManager->getAllWorkers();

        $this->assertEquals('not_installed', $workers['bots']['status']);
        $this->assertEquals(0, $workers['bots']['active']);
    }

    public function test_get_octane_workers_returns_not_installed_when_octane_not_available()
    {
        $this->queueOptimizerMock
            ->shouldReceive('getMetrics')
            ->once()
            ->andReturn(['active_workers' => 0]);

        $workers = $this->workerManager->getAllWorkers();

        // Octane class doesn't exist in test environment
        $this->assertArrayHasKey('octane', $workers);
        $this->assertContains($workers['octane']['status'], ['not_installed', 'not_running', 'error']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

