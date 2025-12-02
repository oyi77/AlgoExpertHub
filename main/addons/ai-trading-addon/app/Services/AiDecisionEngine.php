<?php

namespace Addons\AiTradingAddon\App\Services;

use Addons\TradingPresetAddon\App\Models\TradingPreset;
use Illuminate\Support\Facades\Log;

class AiDecisionEngine
{
    /**
     * Make execution decision based on AI analysis and preset rules.
     * 
     * @param array $aiResult AI analysis result from MarketAnalysisAiService
     * @param TradingPreset $preset Trading preset with AI settings
     * @return array ['execute' => bool, 'adjusted_risk_factor' => float, 'reason' => string]
     */
    public function makeDecision(array $aiResult, TradingPreset $preset): array
    {
        try {
            $mode = $preset->ai_confirmation_mode ?? 'NONE';
            $minSafetyScore = $preset->ai_min_safety_score ?? null;

            // Mode NONE: Always execute (AI not used)
            if ($mode === 'NONE') {
                return [
                    'execute' => true,
                    'adjusted_risk_factor' => 1.0,
                    'reason' => 'AI confirmation disabled (NONE mode)',
                ];
            }

            // If AI result is null or invalid, fail-safe: reject
            if (empty($aiResult) || !isset($aiResult['decision'])) {
                Log::warning("AiDecisionEngine: Invalid AI result", [
                    'preset_id' => $preset->id,
                    'ai_result' => $aiResult,
                ]);
                return [
                    'execute' => false,
                    'adjusted_risk_factor' => 0.0,
                    'reason' => 'AI analysis failed or returned invalid result',
                ];
            }

            $decision = strtoupper($aiResult['decision'] ?? 'REJECT');
            $safetyScore = (float) ($aiResult['safety_score'] ?? 0.0);
            $confidence = (float) ($aiResult['confidence'] ?? 0.0);

            // Check safety score threshold
            if ($minSafetyScore !== null && $safetyScore < $minSafetyScore) {
                return [
                    'execute' => false,
                    'adjusted_risk_factor' => 0.0,
                    'reason' => "Safety score {$safetyScore} below minimum threshold {$minSafetyScore}",
                ];
            }

            // Decision logic
            switch ($decision) {
                case 'ACCEPT':
                    // Mode REQUIRED: Must accept to execute
                    if ($mode === 'REQUIRED') {
                        return [
                            'execute' => true,
                            'adjusted_risk_factor' => 1.0,
                            'reason' => 'AI accepted signal (REQUIRED mode)',
                        ];
                    }
                    // Mode ADVISORY: Can execute but consider other factors
                    return [
                        'execute' => true,
                        'adjusted_risk_factor' => 1.0,
                        'reason' => 'AI accepted signal (ADVISORY mode)',
                    ];

                case 'SIZE_DOWN':
                    // Reduce position size
                    $riskFactor = $this->calculateRiskFactor($safetyScore, $confidence);
                    return [
                        'execute' => true,
                        'adjusted_risk_factor' => $riskFactor,
                        'reason' => "AI recommended size down (risk factor: {$riskFactor})",
                    ];

                case 'REJECT':
                default:
                    // Mode REQUIRED: Must reject
                    if ($mode === 'REQUIRED') {
                        return [
                            'execute' => false,
                            'adjusted_risk_factor' => 0.0,
                            'reason' => 'AI rejected signal (REQUIRED mode)',
                        ];
                    }
                    // Mode ADVISORY: Can still execute but log warning
                    Log::warning("AiDecisionEngine: AI rejected but ADVISORY mode allows execution", [
                        'preset_id' => $preset->id,
                        'ai_reasoning' => $aiResult['reasoning'] ?? 'No reasoning provided',
                    ]);
                    return [
                        'execute' => true,
                        'adjusted_risk_factor' => 0.5, // Reduce risk in advisory mode
                        'reason' => 'AI rejected but ADVISORY mode allows execution (reduced risk)',
                    ];
            }

        } catch (\Exception $e) {
            Log::error("AiDecisionEngine: Decision making failed", [
                'preset_id' => $preset->id,
                'error' => $e->getMessage(),
            ]);
            
            // Fail-safe: reject on error
            return [
                'execute' => false,
                'adjusted_risk_factor' => 0.0,
                'reason' => 'Decision engine error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate risk factor based on safety score and confidence.
     */
    protected function calculateRiskFactor(float $safetyScore, float $confidence): float
    {
        // Normalize to 0-1 range
        $safetyFactor = $safetyScore / 100.0;
        $confidenceFactor = $confidence / 100.0;
        
        // Average with slight bias toward safety
        $riskFactor = ($safetyFactor * 0.6) + ($confidenceFactor * 0.4);
        
        // Clamp between 0.1 and 1.0 (never completely eliminate position, but reduce significantly)
        return max(0.1, min(1.0, $riskFactor));
    }
}

