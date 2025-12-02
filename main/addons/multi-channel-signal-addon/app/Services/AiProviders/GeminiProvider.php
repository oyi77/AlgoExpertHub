<?php

namespace Addons\MultiChannelSignalAddon\App\Services\AiProviders;

use Addons\MultiChannelSignalAddon\App\Contracts\AiProviderInterface;
use Addons\MultiChannelSignalAddon\App\Models\AiConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AiProviderInterface
{
    public function getName(): string
    {
        return 'Google Gemini';
    }

    public function getProvider(): string
    {
        return 'gemini';
    }

    public function parse(string $message, AiConfiguration $config): ?array
    {
        $apiKey = $config->getDecryptedApiKey();
        if (empty($apiKey)) {
            return null;
        }

        // Map deprecated model names to current ones
        $model = $this->normalizeModelName($config->model ?? 'gemini-2.5-flash');
        
        // Build API URL - remove key from URL if present, we'll use header instead
        if ($config->api_url) {
            $apiUrl = $config->api_url;
            
            // Update deprecated model names in custom URLs
            $apiUrl = $this->updateApiUrlModel($apiUrl, $model);
            
            // Ensure using v1beta (as per official docs)
            $apiUrl = str_replace('/v1/', '/v1beta/', $apiUrl);
            
            // Remove API key from URL if present (we'll use header instead)
            $apiUrl = preg_replace('/[?&]key=[^&]*/', '', $apiUrl);
            $apiUrl = rtrim($apiUrl, '?&');
        } else {
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        }

        $prompt = $this->buildPrompt($message);

        try {
            $response = Http::timeout($config->timeout ?? 30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey, // Use header as per official API docs
                ])
                ->post($apiUrl, [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => $config->temperature ?? 0.3,
                        'maxOutputTokens' => $config->max_tokens ?? 500,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                if ($content) {
                    return $this->extractJson($content);
                }
            }

            Log::warning("Gemini API request failed", [
                'status' => $response->status(),
                'body' => $response->body(),
                'api_url' => $apiUrl,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Gemini API call exception: " . $e->getMessage());
            return null;
        }
    }

    public function testConnection(AiConfiguration $config): bool
    {
        $apiKey = $config->getDecryptedApiKey();
        if (empty($apiKey)) {
            Log::warning("Gemini testConnection: API key is empty");
            return false;
        }

        // Map deprecated model names to current ones
        $model = $this->normalizeModelName($config->model ?? 'gemini-2.5-flash');
        
        // Try multiple model names (latest first)
        $modelsToTry = [
            $model,
            'gemini-2.5-flash',
            'gemini-2.5-pro',
            'gemini-1.5-flash',
            'gemini-1.5-pro',
        ];
        $modelsToTry = array_unique($modelsToTry);
        
        // Build API URL - remove key from URL if present, we'll use header instead
        $customApiUrl = null;
        if ($config->api_url) {
            $customApiUrl = $config->api_url;
            // Update deprecated model names in custom URLs
            $customApiUrl = $this->updateApiUrlModel($customApiUrl, $model);
            // Ensure using v1beta (as per official docs)
            $customApiUrl = str_replace('/v1/', '/v1beta/', $customApiUrl);
            // Remove API key from URL if present (we'll use header instead)
            $customApiUrl = preg_replace('/[?&]key=[^&]*/', '', $customApiUrl);
            $customApiUrl = rtrim($customApiUrl, '?&');
        }
        
        $lastError = null;
        
        try {
            // If custom URL is provided, try it first with all models
            if ($customApiUrl) {
                foreach ($modelsToTry as $tryModel) {
                    $testUrl = $this->updateApiUrlModel($customApiUrl, $tryModel);
                    
                    $response = Http::timeout($config->timeout ?? 10)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'x-goog-api-key' => $apiKey, // Use header as per official API docs
                        ])
                        ->post($testUrl, [
                            'contents' => [
                                [
                                    'parts' => [
                                        [
                                            'text' => 'Test',
                                        ],
                                    ],
                                ],
                            ],
                        ]);

                    if ($response->successful()) {
                        Log::info("Gemini testConnection succeeded with custom URL", [
                            'model' => $tryModel,
                            'api_url' => $testUrl,
                        ]);
                        return true;
                    }
                    
                    $lastError = [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'api_url' => $testUrl,
                        'model' => $tryModel,
                    ];
                }
            }
            
            // Try default URL with different models
            foreach ($modelsToTry as $tryModel) {
                $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$tryModel}:generateContent";
                
                $response = Http::timeout($config->timeout ?? 10)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $apiKey, // Use header as per official API docs
                    ])
                    ->post($apiUrl, [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => 'Test',
                                    ],
                                ],
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    Log::info("Gemini testConnection succeeded", [
                        'model' => $tryModel,
                        'api_url' => $apiUrl,
                    ]);
                    return true;
                }
                
                $lastError = [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'api_url' => $apiUrl,
                    'model' => $tryModel,
                ];
            }

            // Log error details for debugging
            Log::warning("Gemini testConnection failed after trying all models", array_merge($lastError ?? [], [
                'original_model' => $config->model,
                'normalized_model' => $model,
                'models_tried' => $modelsToTry,
            ]));

            return false;
        } catch (\Exception $e) {
            Log::error("Gemini testConnection exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_model' => $config->model,
            ]);
            return false;
        }
    }

    /**
     * Normalize deprecated model names to current ones.
     */
    protected function normalizeModelName(?string $model): string
    {
        if (empty($model)) {
            return 'gemini-2.5-flash';
        }

        // Map deprecated model names to latest models (as per official docs)
        $modelMappings = [
            'gemini-pro' => 'gemini-2.5-flash',
            'gemini-pro-vision' => 'gemini-2.5-flash',
            'gemini-1.5-flash' => 'gemini-2.5-flash',
            'gemini-1.5-pro' => 'gemini-2.5-pro',
            'text-bison-001' => 'gemini-2.5-flash',
            'chat-bison-001' => 'gemini-2.5-flash',
        ];

        return $modelMappings[strtolower($model)] ?? $model;
    }

    /**
     * Update deprecated model names in API URL.
     */
    protected function updateApiUrlModel(string $apiUrl, string $newModel): string
    {
        // Extract model from URL if present
        if (preg_match('/\/models\/([^\/:]+)/i', $apiUrl, $matches)) {
            $oldModel = $matches[1];
            $apiUrl = str_ireplace("/models/{$oldModel}", "/models/{$newModel}", $apiUrl);
        } else {
            // If no model in URL, add it before :generateContent
            if (strpos($apiUrl, ':generateContent') !== false) {
                $apiUrl = str_replace(':generateContent', "/models/{$newModel}:generateContent", $apiUrl);
            } else {
                // Append model if URL doesn't have it
                $apiUrl = rtrim($apiUrl, '/') . "/models/{$newModel}:generateContent";
            }
        }
        
        return $apiUrl;
    }

    /**
     * Fetch available models from Gemini API.
     */
    public function fetchAvailableModels(string $apiKey): array
    {
        if (empty($apiKey)) {
            return [];
        }

        try {
            $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                $models = [];
                
                if (isset($data['models']) && is_array($data['models'])) {
                    foreach ($data['models'] as $model) {
                        // Only include models that support generateContent
                        if (isset($model['supportedGenerationMethods']) && 
                            in_array('generateContent', $model['supportedGenerationMethods'])) {
                            $modelName = $model['name'] ?? '';
                            // Extract model name from full path (e.g., "models/gemini-2.5-flash" -> "gemini-2.5-flash")
                            if (preg_match('/models\/([^\/]+)$/', $modelName, $matches)) {
                                $models[] = [
                                    'name' => $matches[1],
                                    'displayName' => $model['displayName'] ?? $matches[1],
                                    'description' => $model['description'] ?? '',
                                ];
                            }
                        }
                    }
                }
                
                // Sort by name
                usort($models, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                
                return $models;
            }

            Log::warning("Gemini fetchAvailableModels failed", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Gemini fetchAvailableModels exception: " . $e->getMessage());
            return [];
        }
    }

    protected function buildPrompt(string $message): string
    {
        return <<<PROMPT
Parse the following trading signal message and extract structured data. Return ONLY a valid JSON object with these fields:
- currency_pair: string (e.g., "EUR/USD", "BTC/USDT", "Gold", "XAU/USD")
- direction: string ("buy" or "sell")
- open_price: float (entry price, use 0 if market entry)
- sl: float (stop loss, optional)
- tp: float (take profit, optional)
- tp_multiple: array (multiple TP levels like [4076, 4071, 4066], optional)
- sl_percentage: float (stop loss as percentage, optional)
- tp_percentage: float or array (take profit as percentage, optional)
- timeframe: string (optional, e.g., "H1", "M15", "D1")
- title: string (optional, short title)
- description: string (optional, full description)

Message to parse:
{$message}

Return JSON only, no explanations:
PROMPT;
    }

    protected function extractJson(string $content): ?array
    {
        // Try direct JSON parse
        $parsedJson = json_decode($content, true);
        if ($parsedJson && json_last_error() === JSON_ERROR_NONE) {
            return $parsedJson;
        }

        // Try to extract JSON from markdown code blocks
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $parsedJson = json_decode($matches[1], true);
            if ($parsedJson && json_last_error() === JSON_ERROR_NONE) {
                return $parsedJson;
            }
        }

        if (preg_match('/```\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $parsedJson = json_decode($matches[1], true);
            if ($parsedJson && json_last_error() === JSON_ERROR_NONE) {
                return $parsedJson;
            }
        }

        // Try to find JSON object in content
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $content, $matches)) {
            $parsedJson = json_decode($matches[0], true);
            if ($parsedJson && json_last_error() === JSON_ERROR_NONE) {
                return $parsedJson;
            }
        }

        Log::warning("Failed to extract JSON from Gemini response", ['content' => substr($content, 0, 200)]);
        return null;
    }
}

