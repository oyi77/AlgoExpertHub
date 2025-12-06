<?php

namespace Addons\AiConnectionAddon\App\Providers;

use Addons\AiConnectionAddon\App\Contracts\AiProviderInterface;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Illuminate\Support\Facades\Http;

class GeminiAdapter implements AiProviderInterface
{
    /**
     * Default base URL (fallback if not provided in connection)
     */
    protected $defaultBaseUrl = 'https://generativelanguage.googleapis.com/v1';

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
        $model = $options['model'] ?? $connection->getModel() ?? 'gemini-pro';
        $temperature = $options['temperature'] ?? $connection->settings['temperature'] ?? 0.3;
        $maxTokens = $options['max_tokens'] ?? $connection->settings['max_tokens'] ?? 500;
        $timeout = $options['timeout'] ?? $connection->settings['timeout'] ?? 30;

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($baseUrl . "/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception("Gemini API error: " . $response->body());
        }

        $data = $response->json();
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        
        // Gemini doesn't provide exact token counts, estimate based on characters
        $tokensUsed = (int) (strlen($prompt . $content) / 4);
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
            $model = 'gemini-pro';

            $baseUrl = $this->getBaseUrl($connection);
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl . "/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'Test'],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 5,
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Gemini connection successful',
                ];
            }

            return [
                'success' => false,
                'message' => 'Gemini API error: ' . $response->body(),
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
            'gemini-pro' => 'Gemini Pro (Best for text)',
            'gemini-pro-vision' => 'Gemini Pro Vision (Multimodal)',
        ];
    }

    /**
     * Estimate cost
     */
    public function estimateCost(int $tokens, string $model): float
    {
        // Gemini pricing per 1K tokens (as of 2024)
        $pricing = [
            'gemini-pro' => 0.00025, // $0.00025 per 1K tokens (very cheap)
            'gemini-pro-vision' => 0.00025,
        ];

        $pricePerThousand = $pricing[$model] ?? 0.00025;
        return ($tokens / 1000) * $pricePerThousand;
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'Google Gemini';
    }

    /**
     * Get provider slug
     */
    public function getSlug(): string
    {
        return 'gemini';
    }
}

