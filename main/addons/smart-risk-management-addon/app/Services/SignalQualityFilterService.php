<?php

namespace Addons\SmartRiskManagement\App\Services;

use App\Models\Signal;
use Illuminate\Support\Facades\Log;

class SignalQualityFilterService
{
    protected PerformanceScoreService $performanceScoreService;
    protected SlippagePredictionService $slippagePredictionService;
    protected MarketContextService $marketContextService;

    public function __construct(
        PerformanceScoreService $performanceScoreService,
        SlippagePredictionService $slippagePredictionService,
        MarketContextService $marketContextService
    ) {
        $this->performanceScoreService = $performanceScoreService;
        $this->slippagePredictionService = $slippagePredictionService;
        $this->marketContextService = $marketContextService;
    }

    /**
     * Check if signal should be rejected
     * 
     * @param Signal $signal Signal to check
     * @param string $signalProviderId Signal provider ID
     * @param float $currentPrice Current market price
     * @return array ['should_reject' => bool, 'reason' => string|null]
     */
    public function shouldRejectSignal(Signal $signal, string $signalProviderId, float $currentPrice): array
    {
        try {
            // Check performance score
            $performanceScore = $this->performanceScoreService->getPerformanceScore(
                $signalProviderId,
                $this->getSignalProviderType($signal)
            );
            
            $performanceThreshold = config('srm.performance_score_threshold', 40);
            if ($performanceScore < $performanceThreshold) {
                return [
                    'should_reject' => true,
                    'reason' => "Signal provider performance score ({$performanceScore}) is below threshold ({$performanceThreshold})",
                    'rejection_type' => 'low_performance_score',
                ];
            }
            
            // Check predicted slippage
            $context = $this->marketContextService->getMarketContext(
                $this->getSymbolFromSignal($signal)
            );
            
            $prediction = $this->slippagePredictionService->getPredictionWithConfidence(
                $this->getSymbolFromSignal($signal),
                array_merge($context, ['signal_provider_id' => $signalProviderId])
            );
            
            $maxSlippageAllowed = config('srm.max_slippage_allowed', 10.0);
            if ($prediction['predicted_value'] > $maxSlippageAllowed) {
                return [
                    'should_reject' => true,
                    'reason' => "Predicted slippage ({$prediction['predicted_value']} pips) exceeds maximum allowed ({$maxSlippageAllowed} pips)",
                    'rejection_type' => 'high_slippage',
                ];
            }
            
            // Check if signal is expired
            if ($this->isSignalExpired($signal, $currentPrice)) {
                return [
                    'should_reject' => true,
                    'reason' => "Signal entry price has expired (current price differs by more than 5 pips)",
                    'rejection_type' => 'expired',
                ];
            }
            
            return [
                'should_reject' => false,
                'reason' => null,
                'rejection_type' => null,
            ];
        } catch (\Exception $e) {
            Log::error("SignalQualityFilterService: Failed to check signal quality", [
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
            
            // On error, don't reject (fail open)
            return [
                'should_reject' => false,
                'reason' => null,
                'rejection_type' => null,
            ];
        }
    }

    /**
     * Check if signal is expired (entry price too far from current price)
     * 
     * @param Signal $signal Signal to check
     * @param float $currentPrice Current market price
     * @param float $maxSlippage Maximum allowed slippage in pips (default 5.0)
     * @return bool True if signal is expired
     */
    public function isSignalExpired(Signal $signal, float $currentPrice, float $maxSlippage = 5.0): bool
    {
        if (!$signal->open_price || $signal->open_price <= 0) {
            return false;
        }
        
        $priceDiff = abs($currentPrice - $signal->open_price);
        
        // Convert to pips (simplified)
        $pipValue = 0.0001;
        $slippagePips = $priceDiff / $pipValue;
        
        return $slippagePips > $maxSlippage;
    }

    /**
     * Get symbol from signal
     */
    protected function getSymbolFromSignal(Signal $signal): string
    {
        if ($signal->pair && $signal->pair->name) {
            return $signal->pair->name;
        }
        
        return 'EURUSD'; // Default fallback
    }

    /**
     * Get signal provider type from signal
     */
    protected function getSignalProviderType(Signal $signal): string
    {
        // Check if signal has channel_source_id (from Multi-Channel Addon)
        if ($signal->channel_source_id) {
            return 'channel_source';
        }
        
        // Default to user (if signal was created by a user)
        return 'user';
    }
}

