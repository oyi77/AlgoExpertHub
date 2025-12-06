<?php

namespace Addons\AiConnectionAddon\App\Providers;

use Addons\AiConnectionAddon\App\Contracts\AiProviderInterface;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Illuminate\Support\Facades\Http;

class OpenRouterAdapter implements AiProviderInterface
{
    /**
     * Default base URL (fallback if not provided in connection)
     */
    protected $defaultBaseUrl = 'https://openrouter.ai/api/v1';

    /**
     * Get base URL for connection (custom or default)
     */
    protected function getBaseUrl(AiConnection $connection): string
    {
        return $connection->getBaseUrl() ?? $this->defaultBaseUrl;
    }

    /**
     * Execute AI call
     */
    public function execute(AiConnection $connection, string $prompt, array $options = []): array
    {
        $apiKey = $connection->getApiKey();
        $baseUrl = $this->getBaseUrl($connection);
        $model = $options['model'] ?? $connection->getModel() ?? 'openai/gpt-3.5-turbo';
        $temperature = $options['temperature'] ?? $connection->settings['temperature'] ?? 0.3;
        $maxTokens = $options['max_tokens'] ?? $connection->settings['max_tokens'] ?? 500;
        $timeout = $options['timeout'] ?? $connection->settings['timeout'] ?? 30;

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
            ->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

        if (!$response->successful()) {
            throw new \Exception("OpenRouter API error: " . $response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? null;
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;
        
        // OpenRouter provides cost in response if available
        $cost = $data['usage']['cost'] ?? $this->estimateCost($tokensUsed, $model);

        return [
            'response' => $content,
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
            'model' => $model,
        ];
    }

    /**
     * Test connection
     */
    public function test(AiConnection $connection): array
    {
        try {
            $apiKey = $connection->getApiKey();

            $baseUrl = $this->getBaseUrl($connection);
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => config('app.url'),
                ])
                ->post($baseUrl . '/chat/completions', [
                    'model' => 'openai/gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'user', 'content' => 'Test'],
                    ],
                    'max_tokens' => 5,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'OpenRouter connection successful',
                ];
            }

            return [
                'success' => false,
                'message' => 'OpenRouter API error: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get available models (fetch from OpenRouter API)
     */
    public function getAvailableModels(AiConnection $connection): array
    {
        try {
            $apiKey = $connection->getApiKey();
            
            $baseUrl = $this->getBaseUrl($connection);
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get($baseUrl . '/models');

            if ($response->successful()) {
                $data = $response->json();
                $models = [];
                
                foreach ($data['data'] ?? [] as $model) {
                    $modelId = $model['id'];
                    $models[$modelId] = $model['name'] ?? $modelId;
                }
                
                return $models;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch OpenRouter models: ' . $e->getMessage());
        }

        // Fallback to popular models
        return [
            'openai/gpt-4' => 'GPT-4',
            'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'anthropic/claude-2' => 'Claude 2',
            'anthropic/claude-instant' => 'Claude Instant',
            'google/palm-2' => 'PaLM 2',
            'meta-llama/llama-2-70b' => 'Llama 2 70B',
            'mistralai/mistral-7b' => 'Mistral 7B',
        ];
    }
    
    /**
     * Fetch and sync all available models from OpenRouter
     * Returns detailed model information
     */
    public function fetchModelMarketplace(AiConnection $connection): array
    {
        $apiKey = $connection->getApiKey();
        
        $baseUrl = $this->getBaseUrl($connection);
        $response = Http::timeout(15)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->get($baseUrl . '/models');

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch models: " . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Estimate cost
     */
    public function estimateCost(int $tokens, string $model): float
    {
        // OpenRouter pricing varies by model
        // These are approximate rates per 1K tokens
        $pricing = [
            'openai/gpt-4' => 0.03,
            'openai/gpt-3.5-turbo' => 0.0015,
            'anthropic/claude-2' => 0.01,
            'anthropic/claude-instant' => 0.001,
            'google/palm-2' => 0.0005,
            'meta-llama/llama-2-70b' => 0.0007,
            'mistralai/mistral-7b' => 0.0002,
        ];

        $pricePerThousand = $pricing[$model] ?? 0.001; // Default estimate
        return ($tokens / 1000) * $pricePerThousand;
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'OpenRouter';
    }

    /**
     * Get provider slug
     */
    public function getSlug(): string
    {
        return 'openrouter';
    }
}

