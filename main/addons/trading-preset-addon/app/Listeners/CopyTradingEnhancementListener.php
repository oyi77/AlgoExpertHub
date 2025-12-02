<?php

namespace Addons\TradingPresetAddon\App\Listeners;

use Addons\CopyTrading\App\Models\CopyTradingSubscription;
use Addons\TradingPresetAddon\App\Services\CopyTradingEnhancer;
use Illuminate\Support\Facades\Log;

/**
 * Listener to enhance copy trading with preset functionality
 * This listener hooks into copy trading process to apply preset configurations
 */
class CopyTradingEnhancementListener
{
    protected CopyTradingEnhancer $enhancer;

    public function __construct(CopyTradingEnhancer $enhancer)
    {
        $this->enhancer = $enhancer;
    }

    /**
     * Enhance copied quantity calculation
     * This method should be called from TradeCopyService::calculateCopiedQuantity
     * 
     * @param CopyTradingSubscription $subscription
     * @param array $baseCalculation
     * @return array Enhanced calculation
     */
    public function enhanceQuantityCalculation(CopyTradingSubscription $subscription, array $baseCalculation): array
    {
        try {
            // Get trader position from base calculation context
            // This assumes the calculation includes trader_position in context
            $traderPosition = $baseCalculation['trader_position'] ?? null;
            
            if (!$traderPosition) {
                return $baseCalculation; // Cannot enhance without trader position
            }

            return $this->enhancer->enhanceCopiedQuantity(
                $traderPosition,
                $subscription,
                $baseCalculation
            );
        } catch (\Exception $e) {
            Log::warning("CopyTradingEnhancementListener: Failed to enhance quantity calculation", [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return $baseCalculation; // Fallback to base calculation
        }
    }
}

