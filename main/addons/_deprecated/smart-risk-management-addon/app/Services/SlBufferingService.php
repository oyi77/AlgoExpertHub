<?php

namespace Addons\SmartRiskManagement\App\Services;

use Illuminate\Support\Facades\Log;

class SlBufferingService
{
    /**
     * Calculate SL buffer based on predicted slippage
     * 
     * @param float $predictedSlippage Predicted slippage in pips
     * @param string $symbol Trading symbol
     * @param string $tradingSession Trading session
     * @return float Buffer in pips (1-3 pips)
     */
    public function calculateSlBuffer(
        float $predictedSlippage,
        string $symbol,
        string $tradingSession
    ): float {
        try {
            // Buffer = min(predicted_slippage * 1.5, 3.0) pips
            // Minimum 1 pip, maximum 3 pips
            $buffer = min($predictedSlippage * 1.5, 3.0);
            $buffer = max(1.0, $buffer); // Minimum 1 pip
            
            // Adjust based on symbol volatility
            $volatilityMultiplier = $this->getVolatilityMultiplier($symbol);
            $buffer = $buffer * $volatilityMultiplier;
            
            // Adjust based on trading session (overlap sessions have higher slippage)
            if ($tradingSession === 'OVERLAP') {
                $buffer = $buffer * 1.2; // 20% more buffer for overlap
            }
            
            return round(min($buffer, 3.0), 4); // Cap at 3 pips
        } catch (\Exception $e) {
            Log::error("SlBufferingService: Failed to calculate SL buffer", [
                'error' => $e->getMessage(),
            ]);
            return 1.0; // Default 1 pip buffer
        }
    }

    /**
     * Get volatility multiplier for a symbol
     */
    protected function getVolatilityMultiplier(string $symbol): float
    {
        // High volatility symbols need more buffer
        $highVolatility = ['XAUUSD', 'BTC', 'ETH', 'GBPJPY'];
        $lowVolatility = ['EURUSD', 'USDJPY', 'AUDUSD'];
        
        foreach ($highVolatility as $pair) {
            if (stripos($symbol, $pair) !== false) {
                return 1.3; // 30% more buffer
            }
        }
        
        foreach ($lowVolatility as $pair) {
            if (stripos($symbol, $pair) !== false) {
                return 0.9; // 10% less buffer
            }
        }
        
        return 1.0; // Default
    }

    /**
     * Apply SL buffer to stop loss price
     * 
     * @param float $slPrice Original stop loss price
     * @param float $buffer Buffer in pips
     * @param string $direction Trade direction ('buy' or 'sell')
     * @return float Adjusted stop loss price
     */
    public function applySlBuffer(float $slPrice, float $buffer, string $direction): float
    {
        if ($slPrice <= 0) {
            return $slPrice;
        }
        
        // Convert buffer from pips to price units
        // Simplified: assume 1 pip = 0.0001 for most pairs
        // In production, use proper pip value calculation
        $pipValue = 0.0001;
        $bufferPrice = $buffer * $pipValue;
        
        if ($direction === 'buy' || $direction === 'long') {
            // For BUY: SL should be lower, so subtract buffer
            return $slPrice - $bufferPrice;
        } else {
            // For SELL: SL should be higher, so add buffer
            return $slPrice + $bufferPrice;
        }
    }

    /**
     * Get buffer reason for transparency
     */
    public function getBufferReason(float $predictedSlippage, string $symbol, string $tradingSession): array
    {
        $buffer = $this->calculateSlBuffer($predictedSlippage, $symbol, $tradingSession);
        
        return [
            'buffer_pips' => $buffer,
            'predicted_slippage' => $predictedSlippage,
            'symbol' => $symbol,
            'trading_session' => $tradingSession,
            'reason' => "Added {$buffer} pip buffer to protect against predicted slippage of {$predictedSlippage} pips",
        ];
    }
}

