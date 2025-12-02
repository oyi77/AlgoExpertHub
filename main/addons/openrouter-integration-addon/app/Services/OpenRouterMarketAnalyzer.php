<?php

namespace Addons\OpenRouterIntegration\App\Services;

use Addons\OpenRouterIntegration\App\DTOs\MarketAnalysisResult;
use Addons\OpenRouterIntegration\App\DTOs\OpenRouterRequest;
use Addons\OpenRouterIntegration\App\Models\OpenRouterConfiguration;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

class OpenRouterMarketAnalyzer
{
    protected OpenRouterService $service;

    public function __construct(OpenRouterService $service)
    {
        $this->service = $service;
    }

    /**
     * Analyze signal against market context.
     */
    public function analyzeSignal(
        Signal $signal,
        array $marketData,
        ?OpenRouterConfiguration $config = null
    ): MarketAnalysisResult {
        try {
            // Get configuration
            if (!$config) {
                $config = OpenRouterConfiguration::getFirstActiveForAnalysis();
            }

            if (!$config) {
                Log::warning('No active OpenRouter configuration found for market analysis');
                return $this->defaultResult('No AI configuration available');
            }

            // Build analysis prompt
            $prompt = $this->buildMarketAnalysisPrompt($signal, $marketData);

            // Create request
            $request = OpenRouterRequest::fromConfig($config, [
                [
                    'role' => 'system',
                    'content' => 'You are an expert trading analyst. Analyze trading signals against market conditions and provide risk assessment. Return ONLY valid JSON with no additional text.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ]);

            // Send request
            $response = $this->service->sendRequest($request);

            if (!$response->success) {
                Log::warning('OpenRouter market analysis failed', [
                    'error' => $response->error,
                    'signal_id' => $signal->id,
                ]);
                return $this->defaultResult('AI analysis failed: ' . $response->error);
            }

            // Parse JSON response
            $analysisData = $response->parseJson();
            if (!$analysisData) {
                Log::warning('Failed to parse JSON from OpenRouter analysis', [
                    'content' => $response->content,
                    'signal_id' => $signal->id,
                ]);
                return $this->defaultResult('Failed to parse AI response');
            }

            return MarketAnalysisResult::fromArray($analysisData);

        } catch (\Exception $e) {
            Log::error('OpenRouter market analyzer exception: ' . $e->getMessage(), [
                'exception' => $e,
                'signal_id' => $signal->id ?? null,
            ]);

            return $this->defaultResult('Exception: ' . $e->getMessage());
        }
    }

    /**
     * Build market analysis prompt.
     */
    protected function buildMarketAnalysisPrompt(Signal $signal, array $marketData): string
    {
        $pair = $signal->pair->name ?? 'Unknown';
        $direction = strtoupper($signal->direction);
        $entry = $signal->open_price;
        $sl = $signal->sl;
        $tp = $signal->tp;
        $timeframe = $signal->time->name ?? 'Unknown';

        // Format market data
        $candlesData = $this->formatCandlesData($marketData['candles'] ?? []);
        $indicators = $this->formatIndicators($marketData['indicators'] ?? []);

        return <<<PROMPT
Analyze this trading signal against current market conditions and provide a risk assessment.

SIGNAL DETAILS:
- Pair: {$pair}
- Direction: {$direction}
- Entry Price: {$entry}
- Stop Loss: {$sl}
- Take Profit: {$tp}
- Timeframe: {$timeframe}

MARKET DATA:
{$candlesData}

TECHNICAL INDICATORS:
{$indicators}

Analyze the signal and return ONLY a JSON object with these fields:
{
  "alignment": "aligned|weakly_aligned|against_trend|unknown",
  "risk_score": number between 0-100 (0=very safe, 100=very risky),
  "safety_score": number between 0-100 (0=very unsafe, 100=very safe),
  "recommendation": "accept|reject|size_down|manual_review",
  "reasoning": "brief explanation of your analysis (2-3 sentences)"
}

Consider:
- Is the signal direction aligned with the current trend?
- Are stop loss and take profit levels reasonable given recent volatility?
- Do technical indicators support the signal?
- What is the risk/reward ratio?

Return ONLY the JSON object, no additional text.
PROMPT;
    }

    /**
     * Format candles data for prompt.
     */
    protected function formatCandlesData(array $candles): string
    {
        if (empty($candles)) {
            return 'No candle data available';
        }

        $recent = array_slice($candles, -10); // Last 10 candles
        $formatted = [];

        foreach ($recent as $candle) {
            $formatted[] = sprintf(
                'O: %s, H: %s, L: %s, C: %s',
                $candle['open'] ?? 'N/A',
                $candle['high'] ?? 'N/A',
                $candle['low'] ?? 'N/A',
                $candle['close'] ?? 'N/A'
            );
        }

        return "Last 10 Candles:\n" . implode("\n", $formatted);
    }

    /**
     * Format indicators for prompt.
     */
    protected function formatIndicators(array $indicators): string
    {
        if (empty($indicators)) {
            return 'No indicator data available';
        }

        $formatted = [];
        foreach ($indicators as $key => $value) {
            if (is_numeric($value)) {
                $formatted[] = sprintf('%s: %.2f', ucfirst($key), $value);
            } elseif (is_string($value)) {
                $formatted[] = sprintf('%s: %s', ucfirst($key), $value);
            }
        }

        return implode("\n", $formatted);
    }

    /**
     * Return default result for error cases.
     */
    protected function defaultResult(string $reason): MarketAnalysisResult
    {
        return new MarketAnalysisResult(
            'unknown',
            50,
            50,
            'manual_review',
            $reason
        );
    }

    /**
     * Quick analysis without full market data (fallback).
     */
    public function quickAnalyze(Signal $signal, ?OpenRouterConfiguration $config = null): MarketAnalysisResult
    {
        return $this->analyzeSignal($signal, [], $config);
    }
}

