<?php

namespace Tests\Feature\Backend;

use App\Services\Monitoring\AlertManager;
use App\Services\Monitoring\MonitoringHistory;
use App\Services\Monitoring\SystemMonitor;
use App\Services\Monitoring\WorkerManager;
use Tests\TestCase;

/**
 * System Monitoring Service Tests
 * 
 * Note: These tests verify service functionality without requiring full HTTP/database setup.
 * For full integration tests, see tests/Feature/Backend/README.md for setup instructions.
 */
class SystemMonitoringTest extends TestCase
{
    public function test_system_monitor_service_exists(): void
    {
        $service = app(SystemMonitor::class);
        $this->assertInstanceOf(SystemMonitor::class, $service);
    }

    public function test_worker_manager_service_exists(): void
    {
        $service = app(WorkerManager::class);
        $this->assertInstanceOf(WorkerManager::class, $service);
    }

    public function test_alert_manager_service_exists(): void
    {
        $service = app(AlertManager::class);
        $this->assertInstanceOf(AlertManager::class, $service);
    }

    public function test_monitoring_history_service_exists(): void
    {
        $service = app(MonitoringHistory::class);
        $this->assertInstanceOf(MonitoringHistory::class, $service);
    }

    public function test_system_monitor_collects_metrics(): void
    {
        $service = app(SystemMonitor::class);
        $metrics = $service->collectMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('timestamp', $metrics);
        $this->assertArrayHasKey('system', $metrics);
        $this->assertArrayHasKey('database', $metrics);
        $this->assertArrayHasKey('cache', $metrics);
        
        // Verify system metrics structure
        $this->assertIsArray($metrics['system']);
        $this->assertArrayHasKey('cpu_load_1m', $metrics['system']);
        $this->assertArrayHasKey('memory_usage_percent', $metrics['system']);
        $this->assertArrayHasKey('disk_usage_percent', $metrics['system']);
    }

    public function test_worker_manager_returns_workers(): void
    {
        $service = app(WorkerManager::class);
        $workers = $service->getAllWorkers();

        $this->assertIsArray($workers);
    }

    public function test_alert_manager_returns_alerts(): void
    {
        $service = app(AlertManager::class);
        $alerts = $service->getActiveAlerts();

        $this->assertIsArray($alerts);
    }

    public function test_monitoring_history_records_snapshot(): void
    {
        $service = app(MonitoringHistory::class);
        
        // Record a snapshot
        $service->recordSnapshot([
            'cpu' => ['load' => 1.0],
            'memory' => ['used_percent' => 50],
            'disk' => ['used_percent' => 40],
        ], [
            'queue' => ['active' => 1],
        ], [
            'connections' => 10,
        ]);

        // Get history
        $history = $service->getHistory();

        $this->assertIsArray($history);
        $this->assertArrayHasKey('labels', $history);
        $this->assertArrayHasKey('system_health', $history);
        $this->assertArrayHasKey('worker_activity', $history);
        $this->assertArrayHasKey('database', $history);
    }
}

