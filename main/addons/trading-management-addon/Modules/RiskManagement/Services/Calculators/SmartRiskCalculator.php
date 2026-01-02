<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services\Calculators;

use Addons\TradingManagement\Shared\Contracts\RiskCalculatorInterface;
use Addons\TradingManagement\Modules\RiskManagement\Services\SymbolSpecService;
use Addons\TradingManagement\Modules\RiskManagement\Services\MarginManagementService;
use Addons\TradingManagement\Modules\RiskManagement\Services\CorrelationRiskService;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

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

        // Calculate lot size using proper pip value calculation
        $slDistance = $this->calculateSLDistance($signal);
        $pipValue = $this->getPipValue($signal, $accountInfo);
        
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

        // Check margin requirements
        $marginService = app(MarginManagementService::class);
        $positionData = $this->calculatePositionSize($signal, $accountInfo, $config);
        $symbol = $signal->pair->name ?? '';
        $entryPrice = (float) $signal->open_price;
        $leverage = (int) ($config['leverage'] ?? $accountInfo['leverage'] ?? 100);
        
        if ($entryPrice > 0 && !empty($symbol)) {
            $requiredMargin = $marginService->calculateRequiredMargin(
                $positionData['lot_size'],
                $entryPrice,
                $leverage,
                $symbol
            );
            
            $marginCheck = $marginService->shouldPreventTrade($accountInfo, $requiredMargin, $config);
            
            if ($marginCheck['should_prevent']) {
                return [
                    'valid' => false,
                    'reason' => $marginCheck['reason'] ?? 'Insufficient margin for trade',
                ];
            }
        }

        // Check correlation risk
        $correlationService = app(CorrelationRiskService::class);
        $maxCorrelationExposurePct = (float) ($config['max_correlation_exposure_pct'] ?? 50.0);
        
        // Get existing open positions for this connection (if available)
        $existingPositions = $this->getExistingPositions($accountInfo, $symbol);
        $newPositionValue = $positionData['lot_size'] * $entryPrice;
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 0);
        
        if (!empty($symbol) && $equity > 0 && $newPositionValue > 0) {
            $correlationCheck = $correlationService->shouldPreventTrade(
                $symbol,
                $existingPositions,
                $newPositionValue,
                $equity,
                $maxCorrelationExposurePct
            );
            
            if ($correlationCheck['should_prevent']) {
                return [
                    'valid' => false,
                    'reason' => $correlationCheck['reason'] ?? 'Correlation risk exceeds limit',
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
        $symbolSpecService = app(SymbolSpecService::class);
        $symbol = $signal->pair->name ?? '';
        $accountCurrency = 'USD'; // Default, should come from accountInfo in future
        
        return $symbolSpecService->getPipSize($symbol, $accountCurrency);
    }

    /**
     * Get pip value (how much 1 pip is worth in account currency)
     */
    protected function getPipValue(Signal $signal, array $accountInfo): float
    {
        $symbolSpecService = app(SymbolSpecService::class);
        $symbol = $signal->pair->name ?? '';
        $accountCurrency = $accountInfo['currency'] ?? 'USD';
        $entryPrice = (float) $signal->open_price;
        
        if (empty($symbol) || $entryPrice <= 0) {
            Log::warning('SmartRiskCalculator: Invalid symbol or entry price for pip value calculation', [
                'symbol' => $symbol,
                'entry_price' => $entryPrice,
            ]);
            // Fallback to standard $10 per pip for 1.0 lot
            return 10.0;
        }
        
        return $symbolSpecService->getPipValue($symbol, 1.0, $accountCurrency, $entryPrice);
    }

    /**
     * Get existing open positions for correlation check
     * 
     * @param array $accountInfo Account information
     * @param string $symbol Current symbol (to exclude from check)
     * @return array Array of existing positions
     */
    protected function getExistingPositions(array $accountInfo, string $symbol): array
    {
        $connectionId = $accountInfo['connection_id'] ?? null;
        
        if (!$connectionId) {
            Log::warning('SmartRiskCalculator: No connection_id in accountInfo, cannot fetch existing positions', [
                'accountInfo_keys' => array_keys($accountInfo),
            ]);
            return [];
        }

        try {
            $positions = ExecutionPosition::where('connection_id', $connectionId)
                ->where('status', 'open')
                ->get();

            return $positions->map(function ($position) {
                return [
                    'symbol' => $position->symbol ?? '',
                    'quantity' => (float) ($position->quantity ?? 0),
                    'entry_price' => (float) ($position->entry_price ?? 0),
                    'direction' => $position->direction ?? 'buy',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('SmartRiskCalculator: Failed to fetch existing positions', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

