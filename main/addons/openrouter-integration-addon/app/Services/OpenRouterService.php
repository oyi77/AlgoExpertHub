<?php

namespace Addons\OpenRouterIntegration\App\Services;

use Addons\OpenRouterIntegration\App\Contracts\OpenRouterServiceInterface;
use Addons\OpenRouterIntegration\App\DTOs\OpenRouterRequest;
use Addons\OpenRouterIntegration\App\DTOs\OpenRouterResponse;
use Addons\OpenRouterIntegration\App\Models\OpenRouterConfiguration;
use Addons\OpenRouterIntegration\App\Models\OpenRouterModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService implements OpenRouterServiceInterface
{
    protected string $apiUrl;
    protected string $modelsEndpoint;
    protected string $chatEndpoint;
    protected int $timeout;
    protected $aiConnectionService;

    public function __construct(?\Addons\AiConnectionAddon\App\Services\AiConnectionService $aiConnectionService = null)
    {
        $this->apiUrl = config('openrouter.api_url');
        $this->modelsEndpoint = config('openrouter.models_endpoint');
        $this->chatEndpoint = config('openrouter.chat_endpoint');
        $this->timeout = config('openrouter.default_timeout');
        $this->aiConnectionService = $aiConnectionService ?? app(\Addons\AiConnectionAddon\App\Services\AiConnectionService::class);
    }

    /**
     * Send request to OpenRouter API.
     */
    public function sendRequest(OpenRouterRequest $request): OpenRouterResponse
    {
        try {
            $config = OpenRouterConfiguration::with('aiConnection')
                ->where('model_id', $request->model)
                ->where('enabled', true)
                ->first();

            if (!$config) {
                return OpenRouterResponse::error('Configuration not found for model: ' . $request->model);
            }

            // Use centralized AI Connection if available
            if ($config->usesCentralizedConnection()) {
                return $this->sendRequestViaCentralizedConnection($config, $request);
            }

            // DEPRECATED: Fallback to direct API call for backward compatibility
            return $this->sendRequestDirect($config, $request);

        } catch (\Exception $e) {
            Log::error('OpenRouter API exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return OpenRouterResponse::error('Exception: ' . $e->getMessage());
        }
    }

    /**
     * Send request using centralized AI Connection Service (NEW METHOD)
     */
    protected function sendRequestViaCentralizedConnection(
        OpenRouterConfiguration $config,
        OpenRouterRequest $request
    ): OpenRouterResponse {
        try {
            // Build prompt from messages
            $prompt = $this->buildPromptFromMessages($request->messages);

            // Get effective settings
            $settings = $config->getEffectiveSettings();

            // Execute through centralized service
            $result = $this->aiConnectionService->execute(
                connectionId: $config->ai_connection_id,
                prompt: $prompt,
                options: $settings,
                feature: 'openrouter_api'
            );

            if ($result['success']) {
                return OpenRouterResponse::success(
                    content: $result['response'],
                    fullResponse: [
                        'choices' => [
                            ['message' => ['content' => $result['response']]],
                        ],
                        'usage' => [
                            'total_tokens' => $result['tokens_used'],
                        ],
                    ],
                    model: $result['model'] ?? $config->model_id,
                    usage: [
                        'total_tokens' => $result['tokens_used'],
                        'cost' => $result['cost'],
                    ]
                );
            }

            return OpenRouterResponse::error('AI Connection execution failed');

        } catch (\Exception $e) {
            Log::error('OpenRouter centralized connection error: ' . $e->getMessage());
            return OpenRouterResponse::error('Connection error: ' . $e->getMessage());
        }
    }

    /**
     * Send request directly to API (DEPRECATED - for backward compatibility)
     */
    protected function sendRequestDirect(
        OpenRouterConfiguration $config,
        OpenRouterRequest $request
    ): OpenRouterResponse {
        $apiKey = $config->getDecryptedApiKey();
        if (!$apiKey) {
            return OpenRouterResponse::error('API key not configured or invalid');
        }

        $url = $this->apiUrl . $this->chatEndpoint;

        $response = Http::timeout($config->timeout ?? $this->timeout)
            ->withHeaders($request->getHeaders($apiKey))
            ->post($url, $request->toArray());

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if ($content) {
                return OpenRouterResponse::success(
                    $content,
                    $data,
                    $data['model'] ?? null,
                    $data['usage'] ?? null
                );
            }

            return OpenRouterResponse::error('No content in response', $data);
        }

        Log::warning('OpenRouter API request failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return OpenRouterResponse::error(
            'API request failed: ' . $response->status(),
            $response->json()
        );
    }

    /**
     * Build simple prompt from messages array
     */
    protected function buildPromptFromMessages(array $messages): string
    {
        $prompt = '';
        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';
            $prompt .= "{$role}: {$content}\n";
        }
        return trim($prompt);
    }

    /**
     * Fetch and sync available models from OpenRouter API.
     */
    public function fetchAvailableModels(): Collection
    {
        try {
            $url = $this->apiUrl . $this->modelsEndpoint;

            // Use first available API key for fetching models
            $config = OpenRouterConfiguration::where('enabled', true)->first();
            if (!$config) {
                Log::warning('No active OpenRouter configuration found for model sync');
                return collect();
            }

            $apiKey = $config->getDecryptedApiKey();
            if (!$apiKey) {
                Log::warning('No valid API key found for model sync');
                return collect();
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::error('Failed to fetch OpenRouter models', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return collect();
            }

            $data = $response->json();
            $models = collect($data['data'] ?? []);

            // Sync models to database
            foreach ($models as $modelData) {
                OpenRouterModel::updateOrCreate(
                    ['model_id' => $modelData['id']],
                    [
                        'name' => $modelData['name'] ?? $modelData['id'],
                        'provider' => $this->extractProvider($modelData['id']),
                        'context_length' => $modelData['context_length'] ?? null,
                        'pricing' => $modelData['pricing'] ?? null,
                        'modalities' => $modelData['architecture']['modality'] ?? null,
                        'is_available' => true,
                        'last_synced_at' => now(),
                    ]
                );
            }

            // Mark models not in response as unavailable
            OpenRouterModel::whereNotIn('model_id', $models->pluck('id')->toArray())
                ->update(['is_available' => false]);

            Log::info('Successfully synced OpenRouter models', [
                'count' => $models->count(),
            ]);

            return $models;

        } catch (\Exception $e) {
            Log::error('Exception fetching OpenRouter models: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return collect();
        }
    }

    /**
     * Test connection with given configuration.
     */
    public function testConnection(OpenRouterConfiguration $config): bool
    {
        try {
            $apiKey = $config->getDecryptedApiKey();
            if (!$apiKey) {
                return false;
            }

            $request = new OpenRouterRequest(
                $config->model_id,
                [
                    [
                        'role' => 'user',
                        'content' => 'Test connection. Reply with "OK".',
                    ],
                ],
                0.1,
                10,
                $config->site_url,
                $config->site_name
            );

            $response = $this->sendRequest($request);

            return $response->success;

        } catch (\Exception $e) {
            Log::error('OpenRouter connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get model information.
     */
    public function getModelInfo(string $modelId): ?OpenRouterModel
    {
        return OpenRouterModel::where('model_id', $modelId)->first();
    }

    /**
     * Extract provider from model ID.
     */
    protected function extractProvider(string $modelId): string
    {
        $parts = explode('/', $modelId);
        return $parts[0] ?? 'unknown';
    }

    /**
     * Get cached models.
     */
    public function getCachedModels(): Collection
    {
        $cacheKey = 'openrouter_models';
        $cacheDuration = config('openrouter.cache_models_for');

        return Cache::remember($cacheKey, $cacheDuration, function () {
            return OpenRouterModel::getAvailable();
        });
    }

    /**
     * Clear models cache.
     */
    public function clearModelsCache(): void
    {
        Cache::forget('openrouter_models');
    }
}

