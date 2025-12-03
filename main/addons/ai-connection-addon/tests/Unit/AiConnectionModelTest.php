<?php

namespace Addons\AiConnectionAddon\Tests\Unit;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Models\AiConnectionUsage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AiConnectionModelTest extends TestCase
{
    use RefreshDatabase;

    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = AiProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_encrypts_credentials_on_save()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => [
                'api_key' => 'secret-key-12345',
                'base_url' => 'https://api.example.com',
            ],
            'status' => 'active',
        ]);

        // Raw database value should be encrypted (not readable)
        $rawValue = \DB::table('ai_connections')
            ->where('id', $connection->id)
            ->value('credentials');

        $this->assertNotEquals('secret-key-12345', $rawValue);
        $this->assertStringNotContainsString('secret-key-12345', $rawValue);

        // Model should decrypt automatically
        $this->assertEquals('secret-key-12345', $connection->credentials['api_key']);
        $this->assertEquals('https://api.example.com', $connection->credentials['base_url']);
    }

    /** @test */
    public function it_decrypts_credentials_on_read()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test-key'],
            'status' => 'active',
        ]);

        // Fresh instance from database
        $fresh = AiConnection::find($connection->id);

        $this->assertEquals('test-key', $fresh->getApiKey());
        $this->assertEquals('test-key', $fresh->getCredential('api_key'));
    }

    /** @test */
    public function it_gets_specific_credential_value()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => [
                'api_key' => 'key123',
                'base_url' => 'https://api.test.com',
                'secret' => 'secret456',
            ],
            'status' => 'active',
        ]);

        $this->assertEquals('key123', $connection->getCredential('api_key'));
        $this->assertEquals('https://api.test.com', $connection->getCredential('base_url'));
        $this->assertEquals('secret456', $connection->getCredential('secret'));
        $this->assertNull($connection->getCredential('nonexistent'));
        $this->assertEquals('default', $connection->getCredential('nonexistent', 'default'));
    }

    /** @test */
    public function it_records_success()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 5, // Had previous errors
        ]);

        $connection->recordSuccess();
        $connection->refresh();

        $this->assertEquals(1, $connection->success_count);
        $this->assertEquals(0, $connection->error_count); // Reset on success
        $this->assertEquals('active', $connection->status);
        $this->assertNotNull($connection->last_used_at);
    }

    /** @test */
    public function it_records_error()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 0,
        ]);

        $connection->recordError('Test error message');
        $connection->refresh();

        $this->assertEquals(1, $connection->error_count);
        $this->assertNotNull($connection->last_error_at);
        $this->assertEquals('active', $connection->status); // Still active (< 10 errors)
    }

    /** @test */
    public function it_marks_connection_as_error_after_threshold()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 9, // Just below threshold
        ]);

        $connection->recordError('Another error');
        $connection->refresh();

        $this->assertEquals(10, $connection->error_count);
        $this->assertEquals('error', $connection->status); // Changed to error at 10 errors
    }

    /** @test */
    public function it_calculates_success_rate()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'success_count' => 95,
            'error_count' => 5,
        ]);

        // 95 successes out of 100 total = 95%
        $this->assertEquals(95.0, $connection->success_rate);
    }

    /** @test */
    public function it_calculates_health_status()
    {
        // Healthy: success_rate >= 95%, error_count < 5
        $healthy = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Healthy',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'success_count' => 100,
            'error_count' => 2,
        ]);
        $this->assertEquals('healthy', $healthy->health_status);

        // Degraded: success_rate < 95%
        $degraded = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Degraded',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'success_count' => 90,
            'error_count' => 10,
        ]);
        $this->assertEquals('degraded', $degraded->health_status);

        // Warning: error_count >= 5
        $warning = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Warning',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'success_count' => 100,
            'error_count' => 7,
        ]);
        $this->assertEquals('warning', $warning->health_status);

        // Critical: status = error
        $critical = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Critical',
            'credentials' => ['api_key' => 'test'],
            'status' => 'error',
            'success_count' => 0,
            'error_count' => 20,
        ]);
        $this->assertEquals('critical', $critical->health_status);
    }

    /** @test */
    public function it_checks_if_active()
    {
        $active = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Active',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
        ]);

        $inactive = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Inactive',
            'credentials' => ['api_key' => 'test'],
            'status' => 'inactive',
        ]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($inactive->isActive());
    }

    /** @test */
    public function it_checks_if_has_errors()
    {
        $noErrors = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'No Errors',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 0,
        ]);

        $withErrors = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'With Errors',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 5,
        ]);

        $this->assertFalse($noErrors->hasErrors());
        $this->assertTrue($withErrors->hasErrors());
    }

    /** @test */
    public function it_has_relationships()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
        ]);

        // Create usage log
        AiConnectionUsage::log($connection->id, 'test', 100, 0.0003, true);

        // Test relationships
        $this->assertEquals($this->provider->id, $connection->provider->id);
        $this->assertCount(1, $connection->usageLogs);
        $this->assertCount(1, $connection->recentUsage(7)->get());
    }

    /** @test */
    public function it_has_scopes()
    {
        $active = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Active',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'priority' => 2,
        ]);

        $inactive = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Inactive',
            'credentials' => ['api_key' => 'test'],
            'status' => 'inactive',
            'priority' => 1,
        ]);

        // Active scope
        $activeConnections = AiConnection::active()->get();
        $this->assertCount(1, $activeConnections);
        $this->assertEquals($active->id, $activeConnections->first()->id);

        // By provider scope
        $providerConnections = AiConnection::byProvider($this->provider->id)->get();
        $this->assertCount(2, $providerConnections);

        // By priority scope
        $prioritized = AiConnection::byPriority()->get();
        $this->assertEquals($inactive->id, $prioritized->first()->id); // Priority 1 first
        $this->assertEquals($active->id, $prioritized->last()->id); // Priority 2 last
    }
}

