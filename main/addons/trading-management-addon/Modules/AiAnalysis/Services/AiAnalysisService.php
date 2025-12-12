<?php

namespace Addons\TradingManagement\Modules\AiAnalysis\Services;

use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiDecision;
use Addons\TradingManagement\Modules\MarketData\Models\MarketData;
use Illuminate\Support\Facades\Log;

class AiAnalysisService
{
    protected AiConnectionService $aiConnectionService;

    public function __construct(AiConnectionService $aiConnectionService)
    {
        $this->aiConnectionService = $aiConnectionService;
    }

    /**
     * Analyze market data using AI
     * 
     * @param string $symbol
     * @param string $timeframe
     * @param array $candles Array of candles (OHLCV)
     * @param array $indicators Array of calculated indicators
     * @return AiDecision|null
     */
    public function analyze(string $symbol, string $timeframe, array $candles, array $indicators): ?AiDecision
    {
        try {
            // 1. Get available AI connection (prefer OpenAI or Gemini)
            // leveraging AiConnectionService's rotation logic via alias or tag if supported, 
            // otherwise just asking for 'openai' or 'gemini'
            // The addon docs say: getAvailableConnections('openai')
            
            // For now, let's try to get a 'market-analysis' capable connection if possible,
            // or default to 'openai'
            $connections = $this->aiConnectionService->getAvailableConnections('openai');
            
            if ($connections->isEmpty()) {
                $connections = $this->aiConnectionService->getAvailableConnections('gemini');
            }
            
            if ($connections->isEmpty()) {
                Log::warning("AiAnalysisService: No AI connections available");
                return null;
            }

            $connection = $connections->first(); // Use first available (rotation logic might be inside execute or we pick one)

            // 2. Build Prompt
            $prompt = $this->buildPrompt($symbol, $timeframe, $candles, $indicators);

            // 3. Execute AI Call
            $result = $this->aiConnectionService->execute(
                $connection->id,
                $prompt,
                [
                    'temperature' => 0.2, // Low temp for analytical consistency
                    'max_tokens' => 500,
                    'response_format' => ['type' => 'json_object'] // Force JSON if model supports it (like gpt-4-turbo)
                ]
            );

            if (!$result['success']) {
                Log::error("AiAnalysisService: AI call failed", ['error' => $result['error_message'] ?? 'Unknown error']);
                return null;
            }

            // 4. Parse Response
            $analysis = $this->parseResponse($result['response']);
            
            if (!$analysis) {
                return null;
            }

            // 5. Save Decision
            $decision = AiDecision::create([
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'action' => strtoupper($analysis['action'] ?? 'NEUTRAL'),
                'confidence' => (int) ($analysis['confidence'] ?? 0),
                'reasoning' => $analysis['reasoning'] ?? 'No reasoning provided',
                'prompt_used' => $prompt,
                'analysis_data' => $analysis,
                'ai_connection_id' => $connection->id,
                'model_used' => $connection->settings['model'] ?? 'unknown',
            ]);

            return $decision;

        } catch (\Exception $e) {
            Log::error("AiAnalysisService: Analysis failed", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build the analysis prompt
     */
    protected function buildPrompt(string $symbol, string $timeframe, array $candles, array $indicators): string
    {
        $lastCandle = end($candles);
        $currentPrice = $lastCandle['close'] ?? 0;
        
        $indicatorText = "";
        foreach ($indicators as $key => $value) {
            if (is_array($value)) {
                $lastVal = end($value);
                $indicatorText .= "- {$key}: " . round((float)$lastVal, 4) . "\n";
            } else {
                $indicatorText .= "- {$key}: {$value}\n";
            }
        }

        $prompt = <<<EOT
You are an expert crypto and forex trading analyst. Analyze the following market data for {$symbol} on {$timeframe} timeframe.

Current Price: {$currentPrice}

Technical Indicators:
{$indicatorText}

Based on this data, assume a trend-following or mean-reversion strategy as appropriate.
Determine the trading action (BUY, SELL, HOLD, NEUTRAL).
Provide a confidence score (0-100).
Provide a brief reasoning.

Output strictly valid JSON in the following format:
{
    "action": "BUY|SELL|HOLD|NEUTRAL",
    "confidence": 85,
    "reasoning": "RSI is oversold and price bounced off support...",
    "entry_zone": "1.2345-1.2350",
    "stop_loss": "1.2300",
    "take_profit": "1.2400"
}
EOT;
        return $prompt;
    }

    /**
     * Parse AI response (JSON)
     */
    protected function parseResponse(string $response): ?array
    {
        try {
            // Clean markdown code blocks if present
            $jsonStr = str_replace(['```json', '```'], '', $response);
            $jsonStr = trim($jsonStr);
            
            $data = json_decode($jsonStr, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("AiAnalysisService: JSON decode error", ['error' => json_last_error_msg(), 'response' => $response]);
                return null;
            }
            
            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }
}
