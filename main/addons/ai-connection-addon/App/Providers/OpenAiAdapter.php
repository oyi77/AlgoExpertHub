<?php

namespace Addons\AiConnectionAddon\App\Providers;

use Addons\AiConnectionAddon\App\Contracts\AiProviderInterface;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiAdapter implements AiProviderInterface
{
    protected $baseUrl = 'https://api.openai.com/v1';

    /**
     * Execute AI call
     */
    public function execute(AiConnection $connection, string $prompt, array $options = []): array
    {
        $apiKey = $connection->getApiKey();
        $model = $options['model'] ?? $connection->getModel() ?? 'gpt-3.5-turbo';
        $temperature = $options['temperature'] ?? $connection->settings['temperature'] ?? 0.3;
        $maxTokens = $options['max_tokens'] ?? $connection->settings['max_tokens'] ?? 500;
        $timeout = $options['timeout'] ?? $connection->settings['timeout'] ?? 30;

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/chat/completions', [
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
            throw new \Exception("OpenAI API error: " . $response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? null;
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;
        $cost = $this->estimateCost($tokensUsed, $model);

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

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'user', 'content' => 'Test'],
                    ],
                    'max_tokens' => 5,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'OpenAI connection successful',
                ];
            }

            return [
                'success' => false,
                'message' => 'OpenAI API error: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get available models
     */
    public function getAvailableModels(AiConnection $connection): array
    {
        return [
            'gpt-4' => 'GPT-4 (Most capable)',
            'gpt-4-turbo-preview' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast & economical)',
            'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K (Extended context)',
        ];
    }

    /**
     * Estimate cost
     */
    public function estimateCost(int $tokens, string $model): float
    {
        // Pricing per 1K tokens (as of 2024)
        $pricing = [
            'gpt-4' => 0.03, // $0.03 per 1K tokens
            'gpt-4-turbo-preview' => 0.01,
            'gpt-3.5-turbo' => 0.0015, // $0.0015 per 1K tokens
            'gpt-3.5-turbo-16k' => 0.003,
        ];

        $pricePerThousand = $pricing[$model] ?? 0.0015;
        return ($tokens / 1000) * $pricePerThousand;
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'OpenAI';
    }

    /**
     * Get provider slug
     */
    public function getSlug(): string
    {
        return 'openai';
    }
}

