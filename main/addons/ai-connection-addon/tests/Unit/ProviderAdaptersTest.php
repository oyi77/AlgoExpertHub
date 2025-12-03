<?php

namespace Addons\AiConnectionAddon\Tests\Unit;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Providers\OpenAiAdapter;
use Addons\AiConnectionAddon\App\Providers\GeminiAdapter;
use Addons\AiConnectionAddon\App\Providers\OpenRouterAdapter;
use Addons\AiConnectionAddon\App\Services\ProviderAdapterFactory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class ProviderAdaptersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function factory_creates_openai_adapter()
    {
        $factory = new ProviderAdapterFactory();
        $adapter = $factory->make('openai');

        $this->assertInstanceOf(OpenAiAdapter::class, $adapter);
        $this->assertEquals('OpenAI', $adapter->getName());
        $this->assertEquals('openai', $adapter->getSlug());
    }

    /** @test */
    public function factory_creates_gemini_adapter()
    {
        $factory = new ProviderAdapterFactory();
        $adapter = $factory->make('gemini');

        $this->assertInstanceOf(GeminiAdapter::class, $adapter);
        $this->assertEquals('Google Gemini', $adapter->getName());
        $this->assertEquals('gemini', $adapter->getSlug());
    }

    /** @test */
    public function factory_creates_openrouter_adapter()
    {
        $factory = new ProviderAdapterFactory();
        $adapter = $factory->make('openrouter');

        $this->assertInstanceOf(OpenRouterAdapter::class, $adapter);
        $this->assertEquals('OpenRouter', $adapter->getName());
        $this->assertEquals('openrouter', $adapter->getSlug());
    }

    /** @test */
    public function factory_throws_exception_for_unsupported_provider()
    {
        $factory = new ProviderAdapterFactory();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported provider');

        $factory->make('unsupported-provider');
    }

    /** @test */
    public function factory_caches_adapters()
    {
        $factory = new ProviderAdapterFactory();

        $adapter1 = $factory->make('openai');
        $adapter2 = $factory->make('openai');

        // Should return same instance (cached)
        $this->assertSame($adapter1, $adapter2);
    }

    /** @test */
    public function factory_checks_if_provider_supported()
    {
        $factory = new ProviderAdapterFactory();

        $this->assertTrue($factory->isSupported('openai'));
        $this->assertTrue($factory->isSupported('gemini'));
        $this->assertTrue($factory->isSupported('openrouter'));
        $this->assertFalse($factory->isSupported('unsupported'));
    }

    /** @test */
    public function factory_lists_supported_providers()
    {
        $factory = new ProviderAdapterFactory();
        $providers = $factory->getSupportedProviders();

        $this->assertIsArray($providers);
        $this->assertArrayHasKey('openai', $providers);
        $this->assertArrayHasKey('gemini', $providers);
        $this->assertArrayHasKey('openrouter', $providers);
    }

    /** @test */
    public function openai_adapter_executes_call()
    {
        $provider = AiProvider::create(['name' => 'OpenAI', 'slug' => 'openai', 'status' => 'active']);
        
        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test-key'],
            'settings' => ['model' => 'gpt-3.5-turbo'],
            'status' => 'active',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'AI response']]],
                'usage' => ['total_tokens' => 75],
            ], 200),
        ]);

        $adapter = new OpenAiAdapter();
        $result = $adapter->execute($connection, 'Test prompt');

        $this->assertEquals('AI response', $result['response']);
        $this->assertEquals(75, $result['tokens_used']);
        $this->assertGreaterThan(0, $result['cost']);
        $this->assertEquals('gpt-3.5-turbo', $result['model']);
    }

    /** @test */
    public function openai_adapter_tests_connection()
    {
        $provider = AiProvider::create(['name' => 'OpenAI', 'slug' => 'openai', 'status' => 'active']);
        
        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test-key'],
            'status' => 'active',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Test']]],
                'usage' => ['total_tokens' => 5],
            ], 200),
        ]);

        $adapter = new OpenAiAdapter();
        $result = $adapter->test($connection);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('successful', strtolower($result['message']));
    }

    /** @test */
    public function openai_adapter_gets_available_models()
    {
        $provider = AiProvider::create(['name' => 'OpenAI', 'slug' => 'openai', 'status' => 'active']);
        
        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
        ]);

        $adapter = new OpenAiAdapter();
        $models = $adapter->getAvailableModels($connection);

        $this->assertIsArray($models);
        $this->assertArrayHasKey('gpt-4', $models);
        $this->assertArrayHasKey('gpt-3.5-turbo', $models);
    }

    /** @test */
    public function openai_adapter_estimates_cost()
    {
        $adapter = new OpenAiAdapter();

        $cost1 = $adapter->estimateCost(1000, 'gpt-3.5-turbo');
        $this->assertEquals(0.0015, $cost1); // 1K tokens * $0.0015

        $cost2 = $adapter->estimateCost(1000, 'gpt-4');
        $this->assertEquals(0.03, $cost2); // 1K tokens * $0.03
    }

    /** @test */
    public function gemini_adapter_estimates_cost_correctly()
    {
        $adapter = new GeminiAdapter();
        
        $cost = $adapter->estimateCost(1000, 'gemini-pro');
        $this->assertEquals(0.00025, $cost); // Very cheap!
    }

    /** @test */
    public function openrouter_adapter_supports_multiple_models()
    {
        $provider = AiProvider::create(['name' => 'OpenRouter', 'slug' => 'openrouter', 'status' => 'active']);
        
        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
        ]);

        $adapter = new OpenRouterAdapter();
        $models = $adapter->getAvailableModels($connection);

        $this->assertIsArray($models);
        $this->assertArrayHasKey('openai/gpt-4', $models);
        $this->assertArrayHasKey('anthropic/claude-2', $models);
        $this->assertArrayHasKey('meta-llama/llama-2-70b', $models);
    }

    /** @test */
    public function adapters_handle_timeout_errors()
    {
        $provider = AiProvider::create(['name' => 'OpenAI', 'slug' => 'openai', 'status' => 'active']);
        
        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test'],
            'settings' => ['timeout' => 1], // 1 second timeout
            'status' => 'active',
        ]);

        // Mock slow response (will timeout)
        Http::fake([
            'api.openai.com/*' => function () {
                sleep(2); // Longer than timeout
                return Http::response([], 200);
            },
        ]);

        $adapter = new OpenAiAdapter();

        $this->expectException(\Exception::class);
        $adapter->execute($connection, 'Test');
    }
}

