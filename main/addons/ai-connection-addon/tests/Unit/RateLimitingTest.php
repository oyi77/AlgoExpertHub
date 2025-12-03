<?php

namespace Addons\AiConnectionAddon\Tests\Unit;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Models\AiConnectionUsage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected $provider;
    protected $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = AiProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $this->connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test Connection',
            'credentials' => ['api_key' => 'test-key'],
            'status' => 'active',
            'priority' => 1,
            'rate_limit_per_minute' => 5, // 5 requests per minute
        ]);
    }

    /** @test */
    public function it_detects_rate_limit_exceeded()
    {
        // Create 5 usage logs in the last minute (at the limit)
        for ($i = 0; $i < 5; $i++) {
            AiConnectionUsage::log(
                connectionId: $this->connection->id,
                feature: 'test',
                tokensUsed: 10,
                cost: 0.0001,
                success: true
            );
        }

        $this->assertTrue($this->connection->isRateLimited());
    }

    /** @test */
    public function it_allows_requests_under_rate_limit()
    {
        // Create only 3 usage logs (under the limit of 5)
        for ($i = 0; $i < 3; $i++) {
            AiConnectionUsage::log(
                connectionId: $this->connection->id,
                feature: 'test',
                tokensUsed: 10,
                cost: 0.0001,
                success: true
            );
        }

        $this->assertFalse($this->connection->isRateLimited());
    }

    /** @test */
    public function it_ignores_old_usage_for_rate_limiting()
    {
        // Create 10 old usage logs (more than 1 minute ago)
        for ($i = 0; $i < 10; $i++) {
            AiConnectionUsage::create([
                'connection_id' => $this->connection->id,
                'feature' => 'test',
                'tokens_used' => 10,
                'cost' => 0.0001,
                'success' => true,
                'created_at' => now()->subMinutes(2), // 2 minutes ago
            ]);
        }

        // Should not be rate limited (old logs don't count)
        $this->assertFalse($this->connection->isRateLimited());
    }

    /** @test */
    public function it_handles_no_rate_limit_configured()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'No Limit Connection',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'rate_limit_per_minute' => null, // No limit
        ]);

        // Create many usage logs
        for ($i = 0; $i < 100; $i++) {
            AiConnectionUsage::log(
                connectionId: $connection->id,
                feature: 'test',
                tokensUsed: 10,
                cost: 0.0001,
                success: true
            );
        }

        // Should never be rate limited
        $this->assertFalse($connection->isRateLimited());
    }

    /** @test */
    public function it_rotates_to_backup_when_primary_rate_limited()
    {
        // Primary connection (rate limited)
        $primary = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Primary',
            'credentials' => ['api_key' => 'primary'],
            'status' => 'active',
            'priority' => 1,
            'rate_limit_per_minute' => 2,
        ]);

        // Backup connection (not rate limited)
        $backup = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Backup',
            'credentials' => ['api_key' => 'backup'],
            'status' => 'active',
            'priority' => 2,
        ]);

        // Make primary rate limited
        for ($i = 0; $i < 2; $i++) {
            AiConnectionUsage::log($primary->id, 'test', 10, 0.0001, true);
        }

        // Get next connection
        $rotationService = app(\Addons\AiConnectionAddon\App\Services\ConnectionRotationService::class);
        $selected = $rotationService->getNextConnection($this->provider->id);

        // Should select backup since primary is rate limited
        $this->assertEquals($backup->id, $selected->id);
    }

    /** @test */
    public function it_returns_null_when_all_connections_rate_limited()
    {
        $conn1 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 1',
            'credentials' => ['api_key' => 'test1'],
            'status' => 'active',
            'priority' => 1,
            'rate_limit_per_minute' => 1,
        ]);

        $conn2 = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Connection 2',
            'credentials' => ['api_key' => 'test2'],
            'status' => 'active',
            'priority' => 2,
            'rate_limit_per_minute' => 1,
        ]);

        // Rate limit both connections
        AiConnectionUsage::log($conn1->id, 'test', 10, 0.0001, true);
        AiConnectionUsage::log($conn2->id, 'test', 10, 0.0001, true);

        $rotationService = app(\Addons\AiConnectionAddon\App\Services\ConnectionRotationService::class);
        $selected = $rotationService->getNextConnection($this->provider->id);

        $this->assertNull($selected);
    }
}

