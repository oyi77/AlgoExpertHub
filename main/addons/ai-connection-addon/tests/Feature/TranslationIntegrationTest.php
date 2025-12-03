<?php

namespace Addons\AiConnectionAddon\Tests\Feature;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use App\Models\TranslationSetting;
use App\Services\TranslationService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class TranslationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_translates_using_centralized_connection()
    {
        // Create provider and connection
        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Translation Connection',
            'credentials' => ['api_key' => 'test-key'],
            'settings' => ['model' => 'gpt-3.5-turbo'],
            'status' => 'active',
            'priority' => 1,
        ]);

        // Configure translation settings
        TranslationSetting::create([
            'ai_connection_id' => $connection->id,
            'batch_size' => 10,
            'delay_between_requests_ms' => 100,
        ]);

        // Mock AI response
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Hola']],
                ],
                'usage' => ['total_tokens' => 20],
            ], 200),
        ]);

        $translationService = new TranslationService();
        $result = $translationService->translateWithAi('Hello', 'Spanish');

        $this->assertEquals('Hola', $result);

        // Verify usage tracked
        $this->assertDatabaseHas('ai_connection_usage', [
            'connection_id' => $connection->id,
            'feature' => 'translation',
            'success' => true,
        ]);
    }

    /** @test */
    public function it_uses_fallback_connection_on_primary_failure()
    {
        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'status' => 'active',
        ]);

        $primary = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Primary',
            'credentials' => ['api_key' => 'primary-key'],
            'status' => 'active',
            'priority' => 1,
        ]);

        $fallback = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Fallback',
            'credentials' => ['api_key' => 'fallback-key'],
            'status' => 'active',
            'priority' => 2,
        ]);

        TranslationSetting::create([
            'ai_connection_id' => $primary->id,
            'fallback_connection_id' => $fallback->id,
        ]);

        // Mock: Primary fails, fallback succeeds
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push('Rate limit exceeded', 429) // Primary fails
                ->push([ // Fallback succeeds
                    'choices' => [['message' => ['content' => 'Bonjour']]],
                    'usage' => ['total_tokens' => 15],
                ], 200),
        ]);

        $translationService = new TranslationService();
        $result = $translationService->translateWithAi('Hello', 'French');

        $this->assertEquals('Bonjour', $result);

        // Verify primary error recorded
        $primary->refresh();
        $this->assertEquals(1, $primary->error_count);

        // Verify fallback success recorded
        $fallback->refresh();
        $this->assertEquals(1, $fallback->success_count);
    }

    /** @test */
    public function it_returns_null_when_translation_not_configured()
    {
        // No translation settings
        $translationService = new TranslationService();
        $result = $translationService->translateWithAi('Hello', 'Spanish');

        $this->assertNull($result);
    }

    /** @test */
    public function it_translates_batch_with_configurable_delay()
    {
        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test-key'],
            'status' => 'active',
        ]);

        TranslationSetting::create([
            'ai_connection_id' => $connection->id,
            'delay_between_requests_ms' => 50, // Custom delay
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Translated']]],
                'usage' => ['total_tokens' => 10],
            ], 200),
        ]);

        $translationService = new TranslationService();
        
        $startTime = microtime(true);
        $results = $translationService->translateBatch([
            'key1' => 'value1',
            'key2' => 'value2',
        ], 'Spanish');
        $elapsed = (microtime(true) - $startTime) * 1000; // ms

        // Should have translated both
        $this->assertCount(2, $results);

        // Should have delay between requests (at least 50ms)
        $this->assertGreaterThanOrEqual(50, $elapsed);
    }
}

