<?php

namespace Addons\AiConnectionAddon\Tests\Unit;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CredentialHandlingTest extends TestCase
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
    public function it_stores_and_retrieves_credentials_as_array()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => [
                'api_key' => 'test-key-123',
                'base_url' => 'https://api.test.com',
                'secret' => 'secret-value',
            ],
            'status' => 'active',
        ]);

        // Retrieve and verify it's an array
        $credentials = $connection->credentials;
        
        $this->assertIsArray($credentials, 'Credentials should always be an array');
        $this->assertEquals('test-key-123', $credentials['api_key']);
        $this->assertEquals('https://api.test.com', $credentials['base_url']);
        $this->assertEquals('secret-value', $credentials['secret']);
    }

    /** @test */
    public function it_handles_legacy_string_credentials_gracefully()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test-key'],
            'status' => 'active',
        ]);

        // Simulate legacy data: manually set credentials as plain encrypted string
        DB::table('ai_connections')
            ->where('id', $connection->id)
            ->update([
                'credentials' => encrypt('plain-api-key-string') // Not JSON
            ]);

        // Reload from database
        $connection = AiConnection::find($connection->id);
        
        // Should wrap string as api_key in array
        $credentials = $connection->credentials;
        
        $this->assertIsArray($credentials, 'Even string credentials should be wrapped as array');
        $this->assertEquals('plain-api-key-string', $credentials['api_key'], 'String should be wrapped as api_key');
    }

    /** @test */
    public function getCredential_returns_correct_value()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => [
                'api_key' => 'my-key',
                'endpoint' => 'https://api.example.com',
            ],
            'status' => 'active',
        ]);

        $this->assertEquals('my-key', $connection->getCredential('api_key'));
        $this->assertEquals('https://api.example.com', $connection->getCredential('endpoint'));
        $this->assertNull($connection->getCredential('nonexistent'));
        $this->assertEquals('default', $connection->getCredential('nonexistent', 'default'));
    }

    /** @test */
    public function getApiKey_helper_returns_correct_value()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'secret-key-xyz'],
            'status' => 'active',
        ]);

        $this->assertEquals('secret-key-xyz', $connection->getApiKey());
    }

    /** @test */
    public function getBaseUrl_helper_returns_correct_value()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => [
                'api_key' => 'key',
                'base_url' => 'https://custom.api.com',
            ],
            'status' => 'active',
        ]);

        $this->assertEquals('https://custom.api.com', $connection->getBaseUrl());
    }

    /** @test */
    public function it_handles_corrupted_credentials_gracefully()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
        ]);

        // Simulate corrupted encrypted data
        DB::table('ai_connections')
            ->where('id', $connection->id)
            ->update([
                'credentials' => 'invalid-encrypted-data-that-cannot-be-decrypted'
            ]);

        // Reload
        $connection = AiConnection::find($connection->id);
        
        // Should return empty array on decryption failure
        $credentials = $connection->credentials;
        
        $this->assertIsArray($credentials, 'Should return empty array on decryption failure');
        $this->assertEmpty($credentials, 'Should be empty array');
        $this->assertNull($connection->getApiKey(), 'getApiKey should return null for corrupted data');
    }

    /** @test */
    public function credentials_are_encrypted_in_database()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => [
                'api_key' => 'super-secret-key-12345',
            ],
            'status' => 'active',
        ]);

        // Check raw database value
        $rawValue = DB::table('ai_connections')
            ->where('id', $connection->id)
            ->value('credentials');

        // Should NOT contain the plain text key
        $this->assertIsString($rawValue);
        $this->assertStringNotContainsString('super-secret-key-12345', $rawValue, 'Credentials should be encrypted in DB');
        
        // But model should decrypt it
        $this->assertEquals('super-secret-key-12345', $connection->getApiKey(), 'Model should decrypt credentials');
    }

    /** @test */
    public function it_handles_empty_credentials()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => [],
            'status' => 'active',
        ]);

        $credentials = $connection->credentials;
        
        $this->assertIsArray($credentials);
        $this->assertEmpty($credentials);
        $this->assertNull($connection->getApiKey());
        $this->assertNull($connection->getBaseUrl());
    }

    /** @test */
    public function getCredential_with_default_works_correctly()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => [
                'api_key' => 'key123',
            ],
            'status' => 'active',
        ]);

        // Existing key
        $this->assertEquals('key123', $connection->getCredential('api_key', 'default-key'));
        
        // Non-existent key with default
        $this->assertEquals('default-value', $connection->getCredential('missing_key', 'default-value'));
        
        // Non-existent key without default
        $this->assertNull($connection->getCredential('another_missing_key'));
    }

    /** @test */
    public function credentials_always_return_array_type()
    {
        $connection = AiConnection::create([
            'provider_id' => $this->provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
        ]);

        // Get credentials
        $credentials = $connection->credentials;
        
        // CRITICAL: Must ALWAYS be array, never string or null
        $this->assertIsArray($credentials, 'Credentials getter must ALWAYS return array');
        
        // Fresh instance should also return array
        $fresh = AiConnection::find($connection->id);
        $this->assertIsArray($fresh->credentials, 'Fresh instance must also return array');
    }
}

