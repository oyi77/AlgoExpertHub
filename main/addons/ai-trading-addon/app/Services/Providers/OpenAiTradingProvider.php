<?php

namespace Addons\AiTradingAddon\App\Services\Providers;

use Addons\AiTradingAddon\App\Contracts\AiTradingProviderInterface;
use Addons\AiTradingAddon\App\Models\AiModelProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OpenAiTradingProvider implements AiTradingProviderInterface
{
    public function getName(): string
    {
        return 'OpenAI';
    }

    public function getProvider(): string
    {
        return 'openai';
    }

    public function analyzeForConfirmation(array $marketData, array $signalData, AiModelProfile $profile): ?array
    {
        try {
            $apiKey = $profile->getApiKey();
            if (!$apiKey) {
                Log::error("OpenAI API key not found for profile {$profile->id}");
                return null;
            }

            $prompt = $this->buildConfirmationPrompt($marketData, $signalData, $profile);
            $response = $this->callOpenAI($apiKey, $profile->model_name, $prompt, $profile->settings);

            if (!$response) {
                return null;
            }

            return $this->parseConfirmationResponse($response);
        } catch (\Exception $e) {
            Log::error("OpenAI confirmation analysis failed", [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function analyzeForScan(array $marketData, AiModelProfile $profile): ?array
    {
        try {
            $apiKey = $profile->getApiKey();
            if (!$apiKey) {
                Log::error("OpenAI API key not found for profile {$profile->id}");
                return null;
            }

            $prompt = $this->buildScanPrompt($marketData, $profile);
            $response = $this->callOpenAI($apiKey, $profile->model_name, $prompt, $profile->settings);

            if (!$response) {
                return null;
            }

            return $this->parseScanResponse($response);
        } catch (\Exception $e) {
            Log::error("OpenAI scan analysis failed", [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function analyzeForPositionMgmt(array $positionData, array $marketData, AiModelProfile $profile): ?array
    {
        try {
            $apiKey = $profile->getApiKey();
            if (!$apiKey) {
                Log::error("OpenAI API key not found for profile {$profile->id}");
                return null;
            }

            $prompt = $this->buildPositionMgmtPrompt($positionData, $marketData, $profile);
            $response = $this->callOpenAI($apiKey, $profile->model_name, $prompt, $profile->settings);

            if (!$response) {
                return null;
            }

            return $this->parsePositionMgmtResponse($response);
        } catch (\Exception $e) {
            Log::error("OpenAI position management analysis failed", [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function testConnection(AiModelProfile $profile): bool
    {
        try {
            $apiKey = $profile->getApiKey();
            if (!$apiKey) {
                return false;
            }

            // Simple test call
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $profile->model_name,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test'],
                ],
                'max_tokens' => 5,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("OpenAI connection test failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function callOpenAI(string $apiKey, string $model, string $prompt, ?array $settings = null): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $settings['temperature'] ?? 0.7,
                'max_tokens' => $settings['max_tokens'] ?? 1000,
            ]);

            if (!$response->successful()) {
                Log::error("OpenAI API error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error("OpenAI API call failed", ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function buildConfirmationPrompt(array $marketData, array $signalData, AiModelProfile $profile): string
    {
        $template = $profile->prompt_template ?? $this->getDefaultConfirmationPrompt();
        
        // Replace placeholders
        $prompt = str_replace('{{symbol}}', $signalData['pair'] ?? 'N/A', $template);
        $prompt = str_replace('{{direction}}', $signalData['direction'] ?? 'N/A', $prompt);
        $prompt = str_replace('{{entry}}', $signalData['entry'] ?? 'N/A', $prompt);
        $prompt = str_replace('{{sl}}', $signalData['sl'] ?? 'N/A', $prompt);
        $prompt = str_replace('{{tp}}', $signalData['tp'] ?? 'N/A', $prompt);
        $prompt = str_replace('{{market_data}}', json_encode($marketData, JSON_PRETTY_PRINT), $prompt);
        
        return $prompt;
    }

    protected function buildScanPrompt(array $marketData, AiModelProfile $profile): string
    {
        $template = $profile->prompt_template ?? $this->getDefaultScanPrompt();
        $prompt = str_replace('{{market_data}}', json_encode($marketData, JSON_PRETTY_PRINT), $template);
        return $prompt;
    }

    protected function buildPositionMgmtPrompt(array $positionData, array $marketData, AiModelProfile $profile): string
    {
        $template = $profile->prompt_template ?? $this->getDefaultPositionMgmtPrompt();
        $prompt = str_replace('{{position_data}}', json_encode($positionData, JSON_PRETTY_PRINT), $template);
        $prompt = str_replace('{{market_data}}', json_encode($marketData, JSON_PRETTY_PRINT), $prompt);
        return $prompt;
    }

    protected function parseConfirmationResponse($response): array
    {
        // Try to parse JSON response
        if (is_string($response)) {
            $json = json_decode($response, true);
            if ($json) {
                return [
                    'alignment' => $json['alignment'] ?? 50.0,
                    'safety_score' => $json['safety_score'] ?? 50.0,
                    'decision' => $json['decision'] ?? 'REJECT',
                    'reasoning' => $json['reasoning'] ?? 'Unable to parse AI response',
                    'confidence' => $json['confidence'] ?? 0.0,
                ];
            }
        }

        // Fallback: return safe defaults
        return [
            'alignment' => 0.0,
            'safety_score' => 0.0,
            'decision' => 'REJECT',
            'reasoning' => 'Failed to parse AI response',
            'confidence' => 0.0,
        ];
    }

    protected function parseScanResponse($response): array
    {
        if (is_string($response)) {
            $json = json_decode($response, true);
            if ($json) {
                return [
                    'should_open_trade' => $json['should_open_trade'] ?? false,
                    'direction' => $json['direction'] ?? 'BUY',
                    'entry' => $json['entry'] ?? 0.0,
                    'sl' => $json['sl'] ?? 0.0,
                    'tp' => $json['tp'] ?? 0.0,
                    'confidence' => $json['confidence'] ?? 0.0,
                    'reasoning' => $json['reasoning'] ?? 'Unable to parse AI response',
                ];
            }
        }

        return [
            'should_open_trade' => false,
            'direction' => 'BUY',
            'entry' => 0.0,
            'sl' => 0.0,
            'tp' => 0.0,
            'confidence' => 0.0,
            'reasoning' => 'Failed to parse AI response',
        ];
    }

    protected function parsePositionMgmtResponse($response): array
    {
        if (is_string($response)) {
            $json = json_decode($response, true);
            if ($json) {
                return [
                    'action' => $json['action'] ?? 'HOLD',
                    'new_sl' => $json['new_sl'] ?? null,
                    'new_tp' => $json['new_tp'] ?? null,
                    'close_percentage' => $json['close_percentage'] ?? null,
                    'reasoning' => $json['reasoning'] ?? 'Unable to parse AI response',
                ];
            }
        }

        return [
            'action' => 'HOLD',
            'new_sl' => null,
            'new_tp' => null,
            'close_percentage' => null,
            'reasoning' => 'Failed to parse AI response',
        ];
    }

    protected function getDefaultConfirmationPrompt(): string
    {
        return <<<'PROMPT'
Analyze the following trading signal and market data to confirm if it's safe to execute.

Signal:
- Symbol: {{symbol}}
- Direction: {{direction}}
- Entry: {{entry}}
- Stop Loss: {{sl}}
- Take Profit: {{tp}}

Market Data:
{{market_data}}

Provide your analysis in JSON format:
{
  "alignment": <0-100, how well signal aligns with market>,
  "safety_score": <0-100, overall safety>,
  "decision": "ACCEPT|REJECT|SIZE_DOWN",
  "reasoning": "<explanation>",
  "confidence": <0-100>
}
PROMPT;
    }

    protected function getDefaultScanPrompt(): string
    {
        return <<<'PROMPT'
Analyze the following market data and determine if a trade should be opened.

Market Data:
{{market_data}}

Provide your analysis in JSON format:
{
  "should_open_trade": <true|false>,
  "direction": "BUY|SELL",
  "entry": <entry_price>,
  "sl": <stop_loss_price>,
  "tp": <take_profit_price>,
  "confidence": <0-100>,
  "reasoning": "<explanation>"
}
PROMPT;
    }

    protected function getDefaultPositionMgmtPrompt(): string
    {
        return <<<'PROMPT'
Analyze the following open position and current market data to determine position management actions.

Position Data:
{{position_data}}

Current Market Data:
{{market_data}}

Provide your analysis in JSON format:
{
  "action": "SET_BE|ADJUST_SL|TIGHTEN_TP|CLOSE_PARTIAL|CLOSE_FULL|HOLD",
  "new_sl": <new_stop_loss_or_null>,
  "new_tp": <new_take_profit_or_null>,
  "close_percentage": <0-100_or_null>,
  "reasoning": "<explanation>"
}
PROMPT;
    }
}

