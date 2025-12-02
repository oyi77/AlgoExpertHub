<?php

namespace Tests\Unit\Addons\OpenRouterIntegration;

use Addons\OpenRouterIntegration\App\DTOs\OpenRouterRequest;
use Addons\OpenRouterIntegration\App\Models\OpenRouterConfiguration;
use Addons\OpenRouterIntegration\App\Services\OpenRouterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OpenRouterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OpenRouterService();
    }

    public function test_send_request_with_valid_configuration()
    {
        $config = OpenRouterConfiguration::create([
            'name' => 'Test Config',
            'api_key' => 'test-key',
            'model_id' => 'openai/gpt-4',
            'temperature' => 0.3,
            'max_tokens' => 500,
            'timeout' => 30,
            'enabled' => true,
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '{"test": "response"}',
                        ],
                    ],
                ],
                'model' => 'openai/gpt-4',
                'usage' => ['total_tokens' => 100],
            ], 200),
        ]);

        $request = new OpenRouterRequest(
            'openai/gpt-4',
            [['role' => 'user', 'content' => 'test']],
            0.3,
            500
        );

        $response = $this->service->sendRequest($request);

        $this->assertTrue($response->success);
        $this->assertNotNull($response->content);
    }

    public function test_send_request_with_invalid_configuration()
    {
        $request = new OpenRouterRequest(
            'invalid-model',
            [['role' => 'user', 'content' => 'test']],
            0.3,
            500
        );

        $response = $this->service->sendRequest($request);

        $this->assertFalse($response->success);
        $this->assertStringContainsString('Configuration not found', $response->error);
    }

    public function test_test_connection_success()
    {
        $config = OpenRouterConfiguration::create([
            'name' => 'Test Config',
            'api_key' => 'test-key',
            'model_id' => 'openai/gpt-4',
            'temperature' => 0.3,
            'max_tokens' => 500,
            'timeout' => 30,
            'enabled' => true,
        ]);

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'OK']],
                ],
            ], 200),
        ]);

        $result = $this->service->testConnection($config);

        $this->assertTrue($result);
    }

    public function test_extract_provider_from_model_id()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractProvider');
        $method->setAccessible(true);

        $provider = $method->invoke($this->service, 'openai/gpt-4');
        $this->assertEquals('openai', $provider);

        $provider = $method->invoke($this->service, 'anthropic/claude-2');
        $this->assertEquals('anthropic', $provider);
    }
}

