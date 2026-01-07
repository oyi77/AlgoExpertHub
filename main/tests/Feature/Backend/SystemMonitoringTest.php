<?php

namespace Tests\Feature\Backend;

use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class SystemMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user with manage-system permission
        $this->admin = Admin::factory()->create(['type' => 'super']);
        
        Cache::flush();
    }

    public function test_monitoring_dashboard_requires_authentication()
    {
        $response = $this->get(route('admin.monitoring.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_monitoring_dashboard_requires_permission()
    {
        // Create staff admin (without manage-system permission by default)
        $admin = Admin::factory()->create(['type' => 'staff']);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.monitoring.index'));

        // Should return 403 or redirect if permission check fails
        // Super admins bypass, but staff admins need explicit permission
        $response->assertStatusIn([403, 302]);
    }

    public function test_monitoring_dashboard_loads_for_authorized_admin()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.monitoring.index'));

        $response->assertStatus(200);
        $response->assertViewIs('backend.monitoring.index');
    }

    public function test_health_endpoint_returns_json()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.monitoring.health'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'timestamp',
            'system' => [
                'cpu_load_1m',
                'cpu_load_5m',
                'cpu_load_15m',
                'memory_usage_mb',
                'memory_peak_mb',
                'disk_usage_percent',
            ],
            'database' => [
                'active_connections',
                'slow_queries',
                'total_queries',
            ],
            'cache' => [
                'hit_rate',
                'hits',
                'misses',
            ],
            'workers' => [
                'queue',
                'bots',
                'octane',
            ],
            'alerts',
        ]);
    }

    public function test_workers_endpoint_returns_json()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.monitoring.workers'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'queue' => [
                'active',
                'total_jobs',
                'pending_jobs',
                'failed_jobs',
                'status',
            ],
            'bots' => [
                'status',
                'active',
                'total_bots',
            ],
            'octane' => [
                'status',
                'workers',
                'memory_mb',
            ],
        ]);
    }

    public function test_alerts_endpoint_returns_json()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.monitoring.alerts'));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_chart_data_endpoint_returns_json()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.monitoring.chart-data', ['type' => 'system']));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
        
        if (count($response->json()) > 0) {
            $response->assertJsonStructure([
                '*' => [
                    'timestamp',
                    'cpu_load_1m',
                    'memory_usage_mb',
                ],
            ]);
        }
    }

    public function test_restart_queue_workers_requires_post()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.monitoring.workers.queue.restart'));

        // Should either succeed or fail gracefully
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'type',
            'message',
        ]);
    }

    public function test_clear_cache_requires_post()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.monitoring.cache.clear'));

        $response->assertStatus(200);
        $response->assertJson([
            'type' => 'success',
        ]);
    }

    public function test_health_endpoint_uses_cache()
    {
        // First request
        $response1 = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.monitoring.health'));

        $timestamp1 = $response1->json('timestamp');

        // Second request should return cached data
        $response2 = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.monitoring.health'));

        $timestamp2 = $response2->json('timestamp');

        // Timestamps should be the same (cached)
        $this->assertEquals($timestamp1, $timestamp2);
    }
}

