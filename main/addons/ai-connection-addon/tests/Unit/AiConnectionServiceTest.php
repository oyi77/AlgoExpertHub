<?php

namespace Addons\AiConnectionAddon\Tests\Unit;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Models\AiConnectionUsage;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class AiConnectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $provider;
    protected $connection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(AiConnectionService::class);

        $this->provider = AiProvider::create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'status' => 'active',
        ]);

        $this->connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test Connection',
            'credentials' => ['api_key' => 'test-key-123'],
            'settings' => ['model' => 'gpt-3.5-turbo'],
            'status' => 'active',
            'priority' => 1,
        ]);
    }

    /** @test */
    public function it_gets_available_connections_for_provider()
    {
        $connections = $this->service->getAvailableConnections('openai', true);

        $this->assertCount(1, $connections);
        $this->assertEquals($this->connection->id, $connections->first()->id);
    }

    /** @test */
    public function it_filters_inactive_connections_when_requested()
    {
        // Add inactive connection
        AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Inactive',
            'credentials' => ['api_key' => 'test'],
            'status' => 'inactive',
        ]);

        $active = $this->service->getAvailableConnections('openai', true);
        $all = $this->service->getAvailableConnections('openai', false);

        $this->assertCount(1, $active); // Only active
        $this->assertCount(2, $all); // All connections
    }

    /** @test */
    public function it_executes_ai_call_and_tracks_usage()
    {
        // Mock HTTP response
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Test response']],
                ],
                'usage' => [
                    'total_tokens' => 100,
                ],
            ], 200),
        ]);

        $result = $this->service->execute(
            connectionId: $this->connection->id,
            prompt: 'Test prompt',
            options: [],
            feature: 'test_feature'
        );

        // Verify result
        $this->assertTrue($result['success']);
        $this->assertEquals('Test response', $result['response']);
        $this->assertEquals(100, $result['tokens_used']);
        $this->assertGreaterThan(0, $result['cost']);
        $this->assertEquals($this->connection->id, $result['connection_id']);

        // Verify usage was tracked
        $this->assertDatabaseHas('ai_connection_usage', [
            'connection_id' => $this->connection->id,
            'feature' => 'test_feature',
            'tokens_used' => 100,
            'success' => true,
        ]);

        // Verify connection success recorded
        $this->connection->refresh();
        $this->assertEquals(1, $this->connection->success_count);
        $this->assertEquals(0, $this->connection->error_count);
        $this->assertNotNull($this->connection->last_used_at);
    }

    /** @test */
    public function it_records_errors_on_failure()
    {
        // Mock HTTP error
        Http::fake([
            'api.openai.com/*' => Http::response('Error', 500),
        ]);

        try {
            $this->service->execute(
                connectionId: $this->connection->id,
                prompt: 'Test',
                options: [],
                feature: 'test'
            );
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            // Expected
        }

        // Verify error recorded
        $this->connection->refresh();
        $this->assertEquals(1, $this->connection->error_count);
        $this->assertNotNull($this->connection->last_error_at);

        // Verify failed usage tracked
        $this->assertDatabaseHas('ai_connection_usage', [
            'connection_id' => $this->connection->id,
            'feature' => 'test',
            'success' => false,
        ]);
    }

    /** @test */
    public function it_rotates_to_backup_on_rate_limit_error()
    {
        // Create backup connection
        $backup = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Backup',
            'credentials' => ['api_key' => 'backup-key'],
            'settings' => ['model' => 'gpt-3.5-turbo'],
            'status' => 'active',
            'priority' => 2,
        ]);

        // Mock primary failure with rate limit error
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push('Rate limit exceeded', 429) // Primary fails
                ->push([ // Backup succeeds
                    'choices' => [
                        ['message' => ['content' => 'Success from backup']],
                    ],
                    'usage' => ['total_tokens' => 50],
                ], 200),
        ]);

        $result = $this->service->execute(
            connectionId: $this->connection->id,
            prompt: 'Test',
            options: [],
            feature: 'test'
        );

        // Should succeed with backup
        $this->assertTrue($result['success']);
        $this->assertEquals('Success from backup', $result['response']);
        $this->assertEquals($backup->id, $result['connection_id']);

        // Verify primary error recorded
        $this->connection->refresh();
        $this->assertEquals(1, $this->connection->error_count);

        // Verify backup success recorded
        $backup->refresh();
        $this->assertEquals(1, $backup->success_count);
    }

    /** @test */
    public function it_tests_connection_successfully()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Test']],
                ],
                'usage' => ['total_tokens' => 5],
            ], 200),
        ]);

        $result = $this->service->testConnection($this->connection->id);

        $this->assertTrue($result['success']);
        $this->assertStringContains('successful', strtolower($result['message']));
        $this->assertArrayHasKey('response_time_ms', $result);
    }

    /** @test */
    public function it_handles_test_connection_failure()
    {
        Http::fake([
            'api.openai.com/*' => Http::response('Unauthorized', 401),
        ]);

        $result = $this->service->testConnection($this->connection->id);

        $this->assertFalse($result['success']);
        $this->assertStringContains('error', strtolower($result['message']));
    }

    /** @test */
    public function it_tracks_usage_manually()
    {
        $this->service->trackUsage([
            'connection_id' => $this->connection->id,
            'feature' => 'manual_test',
            'tokens_used' => 200,
            'cost' => 0.0006,
            'success' => true,
            'response_time_ms' => 1500,
        ]);

        $this->assertDatabaseHas('ai_connection_usage', [
            'connection_id' => $this->connection->id,
            'feature' => 'manual_test',
            'tokens_used' => 200,
            'cost' => 0.0006,
            'success' => true,
            'response_time_ms' => 1500,
        ]);
    }

    /** @test */
    public function it_gets_usage_statistics()
    {
        // Create some usage logs
        for ($i = 0; $i < 5; $i++) {
            AiConnectionUsage::log(
                $this->connection->id,
                'feature_a',
                100,
                0.0003,
                true,
                1000
            );
        }

        for ($i = 0; $i < 3; $i++) {
            AiConnectionUsage::log(
                $this->connection->id,
                'feature_b',
                50,
                0.00015,
                true,
                500
            );
        }

        $stats = $this->service->getUsageStatistics($this->connection->id, 30);

        $this->assertEquals(0.0015 + 0.00045, $stats['total_cost']); // 5*0.0003 + 3*0.00015
        $this->assertEquals(500 + 150, $stats['total_tokens']); // 5*100 + 3*50
        $this->assertIsArray($stats['by_feature']);
        $this->assertGreaterThan(0, $stats['avg_response_time']);
    }

    /** @test */
    public function it_throws_exception_when_connection_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Connection not found');

        $this->service->execute(
            connectionId: 99999, // Non-existent
            prompt: 'Test',
            options: [],
            feature: 'test'
        );
    }
}

