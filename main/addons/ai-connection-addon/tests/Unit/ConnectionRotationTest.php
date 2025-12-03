<?php

namespace Addons\AiConnectionAddon\Tests\Unit;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Services\ConnectionRotationService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConnectionRotationTest extends TestCase
{
    use RefreshDatabase;

    protected $rotationService;
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rotationService = app(ConnectionRotationService::class);

        // Create test provider
        $this->provider = AiProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test-provider',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_selects_connection_by_priority()
    {
        // Create connections with different priorities
        $conn1 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 1',
            'credentials' => ['api_key' => 'test1'],
            'status' => 'active',
            'priority' => 1, // Highest priority
        ]);

        $conn2 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 2',
            'credentials' => ['api_key' => 'test2'],
            'status' => 'active',
            'priority' => 2,
        ]);

        $conn3 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 3',
            'credentials' => ['api_key' => 'test3'],
            'status' => 'active',
            'priority' => 3,
        ]);

        // Should select connection with lowest priority number (highest priority)
        $selected = $this->rotationService->getNextConnection($this->provider->id);

        $this->assertEquals($conn1->id, $selected->id);
        $this->assertEquals(1, $selected->priority);
    }

    /** @test */
    public function it_excludes_specified_connection()
    {
        $conn1 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 1',
            'credentials' => ['api_key' => 'test1'],
            'status' => 'active',
            'priority' => 1,
        ]);

        $conn2 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 2',
            'credentials' => ['api_key' => 'test2'],
            'status' => 'active',
            'priority' => 2,
        ]);

        // Get next connection excluding conn1
        $selected = $this->rotationService->getNextConnection($this->provider->id, $conn1->id);

        $this->assertEquals($conn2->id, $selected->id);
    }

    /** @test */
    public function it_skips_inactive_connections()
    {
        $conn1 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Inactive Connection',
            'credentials' => ['api_key' => 'test1'],
            'status' => 'inactive', // Inactive
            'priority' => 1,
        ]);

        $conn2 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Active Connection',
            'credentials' => ['api_key' => 'test2'],
            'status' => 'active',
            'priority' => 2,
        ]);

        $selected = $this->rotationService->getNextConnection($this->provider->id);

        // Should skip inactive and select active
        $this->assertEquals($conn2->id, $selected->id);
    }

    /** @test */
    public function it_skips_unhealthy_connections()
    {
        $conn1 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Unhealthy Connection',
            'credentials' => ['api_key' => 'test1'],
            'status' => 'active',
            'priority' => 1,
            'error_count' => 15, // Unhealthy (>10 errors)
        ]);

        $conn2 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Healthy Connection',
            'credentials' => ['api_key' => 'test2'],
            'status' => 'active',
            'priority' => 2,
            'error_count' => 0,
        ]);

        $selected = $this->rotationService->getNextConnection($this->provider->id);

        // Should skip unhealthy and select healthy
        $this->assertEquals($conn2->id, $selected->id);
    }

    /** @test */
    public function it_returns_null_when_no_connections_available()
    {
        // No connections created
        $selected = $this->rotationService->getNextConnection($this->provider->id);

        $this->assertNull($selected);
    }

    /** @test */
    public function it_returns_null_when_all_connections_unhealthy()
    {
        $conn1 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 1',
            'credentials' => ['api_key' => 'test1'],
            'status' => 'active',
            'priority' => 1,
            'error_count' => 20,
        ]);

        $conn2 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 2',
            'credentials' => ['api_key' => 'test2'],
            'status' => 'active',
            'priority' => 2,
            'error_count' => 25,
        ]);

        $selected = $this->rotationService->getNextConnection($this->provider->id);

        $this->assertNull($selected);
    }

    /** @test */
    public function it_can_reorder_connections()
    {
        $conn1 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 1',
            'credentials' => ['api_key' => 'test1'],
            'status' => 'active',
            'priority' => 1,
        ]);

        $conn2 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 2',
            'credentials' => ['api_key' => 'test2'],
            'status' => 'active',
            'priority' => 2,
        ]);

        // Reorder: conn2 should become priority 1
        $this->rotationService->reorderConnections($this->provider->id, [$conn2->id, $conn1->id]);

        $conn1->refresh();
        $conn2->refresh();

        $this->assertEquals(2, $conn1->priority);
        $this->assertEquals(1, $conn2->priority);
    }

    /** @test */
    public function it_can_reset_error_counts()
    {
        $conn1 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 1',
            'credentials' => ['api_key' => 'test1'],
            'status' => 'error',
            'error_count' => 15,
        ]);

        $conn2 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 2',
            'credentials' => ['api_key' => 'test2'],
            'status' => 'active',
            'error_count' => 5,
        ]);

        $resetCount = $this->rotationService->resetErrorCounts($this->provider->id);

        $conn1->refresh();
        $conn2->refresh();

        $this->assertEquals(2, $resetCount);
        $this->assertEquals(0, $conn1->error_count);
        $this->assertEquals(0, $conn2->error_count);
        $this->assertEquals('active', $conn1->status);
    }

    /** @test */
    public function it_provides_connection_statistics()
    {
        AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Active',
            'credentials' => ['api_key' => 'test1'],
            'status' => 'active',
            'error_count' => 2,
        ]);

        AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Inactive',
            'credentials' => ['api_key' => 'test2'],
            'status' => 'inactive',
            'error_count' => 0,
        ]);

        AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Error',
            'credentials' => ['api_key' => 'test3'],
            'status' => 'error',
            'error_count' => 15,
        ]);

        $stats = $this->rotationService->getConnectionStatistics($this->provider->id);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['active']);
        $this->assertEquals(1, $stats['inactive']);
        $this->assertEquals(1, $stats['error']);
        $this->assertEquals(2, $stats['healthy']); // error_count < 5
        $this->assertEquals(1, $stats['critical']); // error_count >= 10
    }

    /** @test */
    public function it_checks_if_provider_has_available_connections()
    {
        $this->assertFalse($this->rotationService->hasAvailableConnections($this->provider->id));

        AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
        ]);

        $this->assertTrue($this->rotationService->hasAvailableConnections($this->provider->id));
    }
}

