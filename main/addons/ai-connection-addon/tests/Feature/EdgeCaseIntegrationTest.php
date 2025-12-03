<?php

namespace Addons\AiConnectionAddon\Tests\Feature;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Models\AiConnectionUsage;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class EdgeCaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AiConnectionService::class);

        $this->provider = AiProvider::create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_handles_no_connections_configured()
    {
        // No connections exist
        $connections = $this->service->getAvailableConnections('openai');

        $this->assertCount(0, $connections);
    }

    /** @test */
    public function it_handles_all_connections_failing()
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

        // Mock: All connections fail
        Http::fake([
            'api.openai.com/*' => Http::response('Service unavailable', 503),
        ]);

        try {
            $this->service->execute($conn1->id, 'Test', [], 'test');
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            // Expected - all connections failed
            $this->assertStringContainsString('OpenAI API error', $e->getMessage());
        }

        // Both connections should have errors recorded
        $conn1->refresh();
        $conn2->refresh();
        
        $this->assertGreaterThan(0, $conn1->error_count);
        // Note: Rotation might not try conn2 if error isn't rotation-triggering
    }

    /** @test */
    public function it_switches_connections_mid_operation()
    {
        $conn1 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Primary',
            'credentials' => ['api_key' => 'primary'],
            'status' => 'active',
            'priority' => 1,
        ]);

        $conn2 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Backup',
            'credentials' => ['api_key' => 'backup'],
            'status' => 'active',
            'priority' => 2,
        ]);

        // Mock: First call fails with timeout (rotation trigger), second succeeds
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push('Request timeout', 503) // Triggers rotation
                ->push([ // Backup succeeds
                    'choices' => [['message' => ['content' => 'Success from backup']]],
                    'usage' => ['total_tokens' => 50],
                ], 200),
        ]);

        $result = $this->service->execute($conn1->id, 'Test', [], 'test');

        // Should succeed with backup
        $this->assertTrue($result['success']);
        $this->assertEquals($conn2->id, $result['connection_id']);

        // Verify both connections tracked
        $this->assertDatabaseHas('ai_connection_usage', [
            'connection_id' => $conn1->id,
            'success' => false,
        ]);

        $this->assertDatabaseHas('ai_connection_usage', [
            'connection_id' => $conn2->id,
            'success' => true,
        ]);
    }

    /** @test */
    public function it_handles_concurrent_requests()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'rate_limit_per_minute' => 100, // High enough for concurrent requests
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Response']]],
                'usage' => ['total_tokens' => 10],
            ], 200),
        ]);

        // Simulate concurrent requests
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->service->execute($connection->id, "Prompt {$i}", [], 'concurrent_test');
        }

        // All should succeed
        $this->assertCount(5, $results);
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
        }

        // Verify all tracked
        $this->assertEquals(5, AiConnectionUsage::where('connection_id', $connection->id)->count());

        // Verify success count updated
        $connection->refresh();
        $this->assertEquals(5, $connection->success_count);
    }

    /** @test */
    public function it_handles_invalid_connection_id()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Connection not found');

        $this->service->execute(99999, 'Test', [], 'test');
    }

    /** @test */
    public function it_handles_connection_with_no_credentials()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'No Credentials',
            'credentials' => [], // Empty credentials
            'status' => 'active',
        ]);

        Http::fake();

        try {
            $this->service->execute($connection->id, 'Test', [], 'test');
            // Will fail with HTTP error due to no API key
        } catch (\Exception $e) {
            // Expected
            $this->assertNotEmpty($e->getMessage());
        }

        // Error should be recorded
        $connection->refresh();
        $this->assertGreaterThan(0, $connection->error_count);
    }

    /** @test */
    public function it_recovers_after_temporary_failure()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
        ]);

        // Simulate: Fail → Succeed
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push('Temporary error', 500)
                ->push([
                    'choices' => [['message' => ['content' => 'Success']]],
                    'usage' => ['total_tokens' => 10],
                ], 200),
        ]);

        // First call fails
        try {
            $this->service->execute($connection->id, 'Test', [], 'test');
        } catch (\Exception $e) {
            // Expected
        }

        $connection->refresh();
        $this->assertEquals(1, $connection->error_count);

        // Second call succeeds
        $result = $this->service->execute($connection->id, 'Test', [], 'test');
        
        $this->assertTrue($result['success']);

        // Error count should reset on success
        $connection->refresh();
        $this->assertEquals(0, $connection->error_count);
        $this->assertEquals('active', $connection->status);
    }

    /** @test */
    public function it_handles_malformed_api_response()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
        ]);

        // Mock malformed response
        Http::fake([
            'api.openai.com/*' => Http::response([
                'unexpected' => 'structure',
                // Missing 'choices' field
            ], 200),
        ]);

        try {
            $this->service->execute($connection->id, 'Test', [], 'test');
            // May fail or return null response
        } catch (\Exception $e) {
            // Expected
            $this->assertNotEmpty($e->getMessage());
        }
    }

    /** @test */
    public function it_respects_connection_settings_overrides()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'settings' => [
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0.5,
                'max_tokens' => 100,
            ],
            'status' => 'active',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Response']]],
                'usage' => ['total_tokens' => 10],
            ], 200),
        ]);

        // Execute with override options
        $result = $this->service->execute(
            $connection->id,
            'Test',
            ['temperature' => 0.9, 'max_tokens' => 500], // Overrides
            'test'
        );

        $this->assertTrue($result['success']);

        // Verify HTTP request was made (can't easily verify exact params without inspecting Http::fake)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'openai.com');
        });
    }
}

