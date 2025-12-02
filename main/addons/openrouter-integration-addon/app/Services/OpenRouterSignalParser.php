<?php

namespace Addons\OpenRouterIntegration\App\Services;

use Addons\MultiChannelSignalAddon\App\Contracts\AiProviderInterface;
use Addons\MultiChannelSignalAddon\App\Models\AiConfiguration;
use Addons\OpenRouterIntegration\App\DTOs\OpenRouterRequest;
use Addons\OpenRouterIntegration\App\Models\OpenRouterConfiguration;
use Illuminate\Support\Facades\Log;

class OpenRouterSignalParser implements AiProviderInterface
{
    protected OpenRouterService $service;

    public function __construct(OpenRouterService $service)
    {
        $this->service = $service;
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'OpenRouter';
    }

    /**
     * Get provider identifier.
     */
    public function getProvider(): string
    {
        return 'openrouter';
    }

    /**
     * Parse message using OpenRouter AI.
     */
    public function parse(string $message, AiConfiguration $config): ?array
    {
        try {
            // Get OpenRouter configuration
            $openRouterConfig = $this->getOpenRouterConfig($config);
            if (!$openRouterConfig) {
                Log::warning('No active OpenRouter configuration found for parsing');
                return null;
            }

            // Build prompt
            $prompt = $this->buildSignalParsingPrompt($message);

            // Create request
            $request = OpenRouterRequest::fromConfig($openRouterConfig, [
                [
                    'role' => 'system',
                    'content' => 'You are a trading signal parser. Extract trading signal information from messages and return ONLY valid JSON with no additional text or formatting.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ]);

            // Send request
            $response = $this->service->sendRequest($request);

            if (!$response->success) {
                Log::warning('OpenRouter parsing failed', [
                    'error' => $response->error,
                ]);
                return null;
            }

            // Parse JSON response
            $parsedData = $response->parseJson();
            if (!$parsedData) {
                Log::warning('Failed to parse JSON from OpenRouter response', [
                    'content' => $response->content,
                ]);
                return null;
            }

            return $this->validateAndNormalize($parsedData);

        } catch (\Exception $e) {
            Log::error('OpenRouter signal parser exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Test API connection.
     */
    public function testConnection(AiConfiguration $config): bool
    {
        try {
            $openRouterConfig = $this->getOpenRouterConfig($config);
            if (!$openRouterConfig) {
                return false;
            }

            return $this->service->testConnection($openRouterConfig);

        } catch (\Exception $e) {
            Log::error('OpenRouter connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get OpenRouter configuration.
     */
    protected function getOpenRouterConfig(AiConfiguration $config): ?OpenRouterConfiguration
    {
        // Try to find matching OpenRouter config by model or first active for parsing
        if (isset($config->model)) {
            $openRouterConfig = OpenRouterConfiguration::where('model_id', $config->model)
                ->where('enabled', true)
                ->where('use_for_parsing', true)
                ->first();

            if ($openRouterConfig) {
                return $openRouterConfig;
            }
        }

        return OpenRouterConfiguration::getFirstActiveForParsing();
    }

    /**
     * Build signal parsing prompt.
     */
    protected function buildSignalParsingPrompt(string $message): string
    {
        return <<<PROMPT
Extract trading signal information from the following message and return ONLY a JSON object with these fields:

{
  "currency_pair": "string (e.g., EUR/USD, BTC/USDT, XAUUSD)",
  "direction": "buy|sell|long|short",
  "open_price": number (entry price, use 0 for market entry),
  "stop_loss": number (SL price),
  "take_profit": number or array of numbers (TP price(s)),
  "timeframe": "string (e.g., 1H, 4H, 1D)",
  "confidence": number between 0-100 (your confidence in parsing accuracy)
}

Message to parse:
{$message}

Return ONLY the JSON object, no additional text or explanation.
PROMPT;
    }

    /**
     * Validate and normalize parsed data.
     */
    protected function validateAndNormalize(array $data): ?array
    {
        // Required fields
        if (empty($data['currency_pair']) || empty($data['direction'])) {
            return null;
        }

        // Normalize direction
        $data['direction'] = strtolower($data['direction']);
        if (!in_array($data['direction'], ['buy', 'sell', 'long', 'short'])) {
            return null;
        }

        // Ensure numeric fields
        $data['open_price'] = floatval($data['open_price'] ?? 0);
        $data['stop_loss'] = floatval($data['stop_loss'] ?? 0);
        
        // Handle take_profit as array or single value
        if (isset($data['take_profit'])) {
            if (is_array($data['take_profit'])) {
                $data['take_profit'] = array_map('floatval', $data['take_profit']);
            } else {
                $data['take_profit'] = floatval($data['take_profit']);
            }
        }

        // Ensure confidence is between 0-100
        $data['confidence'] = max(0, min(100, intval($data['confidence'] ?? 70)));

        return $data;
    }
}

