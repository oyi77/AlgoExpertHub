<?php

namespace Addons\MultiChannelSignalAddon\App\Services\AiProviders;

use Addons\MultiChannelSignalAddon\App\Contracts\AiProviderInterface;
use Addons\MultiChannelSignalAddon\App\Models\AiConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiProvider implements AiProviderInterface
{
    public function getName(): string
    {
        return 'OpenAI';
    }

    public function getProvider(): string
    {
        return 'openai';
    }

    public function parse(string $message, AiConfiguration $config): ?array
    {
        $apiKey = $config->getDecryptedApiKey();
        if (empty($apiKey)) {
            return null;
        }

        $apiUrl = $config->api_url ?? 'https://api.openai.com/v1/chat/completions';
        $model = $config->model ?? 'gpt-3.5-turbo';
        $prompt = $this->buildPrompt($message);

        try {
            $response = Http::timeout($config->timeout ?? 30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($apiUrl, [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a trading signal parser. Extract trading signal information from messages and return ONLY valid JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => $config->temperature ?? 0.3,
                    'max_tokens' => $config->max_tokens ?? 500,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                
                if ($content) {
                    return $this->extractJson($content);
                }
            }

            Log::warning("OpenAI API request failed", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("OpenAI API call exception: " . $e->getMessage());
            return null;
        }
    }

    public function testConnection(AiConfiguration $config): bool
    {
        $apiKey = $config->getDecryptedApiKey();
        if (empty($apiKey)) {
            return false;
        }

        $apiUrl = $config->api_url ?? 'https://api.openai.com/v1/models';
        
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                ->get($apiUrl);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
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

        Log::warning("Failed to extract JSON from AI response", ['content' => substr($content, 0, 200)]);
        return null;
    }
}

