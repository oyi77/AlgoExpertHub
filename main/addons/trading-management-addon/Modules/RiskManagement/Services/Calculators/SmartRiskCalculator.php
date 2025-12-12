<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services\Calculators;

use Addons\TradingManagement\Shared\Contracts\RiskCalculatorInterface;
use App\Models\Signal;

/**
 * Smart Risk Calculator
 * 
 * AI-powered adaptive risk management
 * Adjusts position sizing based on:
 * - Signal provider performance
 * - Market conditions
 * - Predicted slippage
 * 
 * Migrated from smart-risk-management-addon
 */
class SmartRiskCalculator implements RiskCalculatorInterface
{
    /**
     * Calculate position size with AI adaptive risk
     * 
     * Adjusts lot size based on signal provider performance score
     */
    public function calculatePositionSize(Signal $signal, array $accountInfo, array $config): array
    {
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 10000);
        $baseRiskPercent = (float) ($config['risk_per_trade_pct'] ?? 1.0);

        // Get signal provider performance score (0-100)
        $providerScore = $this->getProviderScore($signal);

        // Adjust risk based on performance
        $adjustedRiskPercent = $this->adjustRiskByScore($baseRiskPercent, $providerScore, $config);

        // Calculate risk amount
        $riskAmount = $equity * ($adjustedRiskPercent / 100);

        // Calculate lot size (simplified - same as preset calculator)
        $slDistance = $this->calculateSLDistance($signal);
        $pipValue = 10.0; // Simplified
        
        $lotSize = $slDistance > 0 
            ? $riskAmount / ($slDistance * $pipValue)
            : 0.01;

        $lotSize = max(0.01, min($lotSize, 10.0));

        return [
            'lot_size' => round($lotSize, 2),
            'risk_amount' => $riskAmount,
            'risk_percent' => $adjustedRiskPercent,
            'provider_score' => $providerScore,
            'base_risk_percent' => $baseRiskPercent,
            'adjustment_factor' => $adjustedRiskPercent / $baseRiskPercent,
        ];
    }

    /**
     * Calculate stop loss with slippage buffer (if enabled)
     */
    public function calculateStopLoss(Signal $signal, float $lotSize, array $config): float
    {
        $baseSL = (float) $signal->sl;

        // If slippage buffer enabled, adjust SL
        if ($config['smart_risk_slippage_buffer'] ?? false) {
            $predictedSlippage = $this->predictSlippage($signal);
            $pipSize = $this->getPipSize($signal);
            $direction = $signal->direction;

            // Add buffer in the direction that protects us
            if ($direction === 'buy' || $direction === 'long') {
                $baseSL -= ($predictedSlippage * $pipSize); // Move SL further down
            } else {
                $baseSL += ($predictedSlippage * $pipSize); // Move SL further up
            }
        }

        return $baseSL;
    }

    /**
     * Calculate take profits (same as preset calculator for now)
     */
    public function calculateTakeProfits(Signal $signal, float $lotSize, array $config): array
    {
        // Use signal's TP for now
        // Can be enhanced with smart adjustments
        return [(float) $signal->tp];
    }

    /**
     * Validate trade with smart risk criteria
     */
    public function validateTrade(Signal $signal, array $accountInfo, array $config): array
    {
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 0);

        if ($equity <= 0) {
            return ['valid' => false, 'reason' => 'Insufficient balance'];
        }

        // Check provider score threshold
        $minScore = (float) ($config['smart_risk_min_score'] ?? 0);
        if ($minScore > 0) {
            $providerScore = $this->getProviderScore($signal);
            
            if ($providerScore < $minScore) {
                return [
                    'valid' => false,
                    'reason' => "Provider score too low: {$providerScore} < {$minScore}",
                ];
            }
        }

        return ['valid' => true, 'reason' => null];
    }

    public function getCalculatorName(): string
    {
        return 'smart_risk';
    }

    /**
     * Get signal provider performance score
     * 
     * @param Signal $signal
     * @return float Score 0-100
     */
    protected function getProviderScore(Signal $signal): float
    {
        $score = 50.0;
        $components = 0;

        // 1. Check AI Confidence (Priority)
        if ($signal->aiDecision) {
            $aiScore = (float) $signal->aiDecision->confidence;
            if ($aiScore > 0) {
                $score += $aiScore;
                $components++;
            }
        }

        // 2. Check Signal Provider History
        if ($signal->auto_created && $signal->channel_source_id) {
            $metrics = \DB::table('srm_signal_provider_metrics')
                ->where('provider_id', $signal->channel_source_id)
                ->where('symbol', $signal->pair->name ?? '')
                ->first();

            if ($metrics) {
                $score += (float) $metrics->performance_score;
                $components++;
            }
        }

        // 3. Fallback for manual signals
        if ($components === 0 && !$signal->auto_created) {
            return 80.0; // Trust manual signals by default
        }

        // Calculate average if multiple components
        if ($components > 0) {
            // If we started with base 50, subtract it before averaging if we added components
            // Actually simpler:
            $totalScore = 0;
            $count = 0;
            
            if ($signal->aiDecision) {
                $totalScore += $signal->aiDecision->confidence;
                $count++;
            }
            
            if ($signal->auto_created && $signal->channel_source_id && isset($metrics)) {
                $totalScore += $metrics->performance_score;
                $count++;
            }
            
            return $count > 0 ? $totalScore / $count : 50.0;
        }

        return 50.0;
    }

    /**
     * Adjust risk based on provider score
     * 
     * Score 0-50: Reduce risk by up to 50%
     * Score 50: No adjustment
     * Score 50-100: Increase risk by up to 50%
     */
    protected function adjustRiskByScore(float $baseRisk, float $score, array $config): float
    {
        // Normalize score to -1.0 to +1.0 range
        $normalized = ($score - 50) / 50;

        // Adjustment factor: 0.5 to 1.5
        $factor = 1.0 + ($normalized * 0.5);

        $adjustedRisk = $baseRisk * $factor;

        // Ensure within min/max bounds
        $minRisk = (float) ($config['risk_min_pct'] ?? 0.5);
        $maxRisk = (float) ($config['risk_max_pct'] ?? 3.0);

        return max($minRisk, min($adjustedRisk, $maxRisk));
    }

    /**
     * Predict slippage for signal
     */
    protected function predictSlippage(Signal $signal): float
    {
        // Query SRM predictions for this signal/symbol
        // For now, return average slippage estimate
        return 2.0; // pips
    }

    /**
     * Calculate SL distance in pips
     */
    protected function calculateSLDistance(Signal $signal): float
    {
        $entryPrice = (float) $signal->open_price;
        $slPrice = (float) $signal->sl;
        $pipSize = $this->getPipSize($signal);

        return abs($entryPrice - $slPrice) / $pipSize;
    }

    /**
     * Get pip size
     */
    protected function getPipSize(Signal $signal): float
    {
        $symbol = $signal->pair->name ?? '';
        
        if (str_contains($symbol, 'JPY')) return 0.01;
        if (str_contains($symbol, 'XAU') || str_contains($symbol, 'GOLD')) return 0.10;
        
        return 0.0001;
    }
}

