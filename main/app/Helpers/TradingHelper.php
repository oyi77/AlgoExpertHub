<?php

if (!function_exists('formatTradingPrice')) {
    /**
     * Format trading prices smartly based on asset type and value
     * 
     * @param mixed $price Price value to format
     * @param string|null $symbol Trading symbol (e.g., BTC/USDT, EUR/USD)
     * @return string Formatted price
     */
    function formatTradingPrice($price, $symbol = null)
    {
        if ($price === null || $price === '') {
            return 'N/A';
        }

        $price = (float) $price;
        
        // Handle zero or very small numbers
        if (abs($price) < 0.00000001) {
            return '0.00';
        }

        // If symbol provided, detect asset type
        if ($symbol) {
            $symbol = strtoupper($symbol);
            
            // Forex pairs (typically 4-5 decimals)
            if (preg_match('/^(EUR|GBP|USD|JPY|AUD|NZD|CAD|CHF)(USD|EUR|GBP|JPY)$/', $symbol)) {
                // JPY pairs use 2-3 decimals
                if (str_contains($symbol, 'JPY')) {
                    return number_format($price, 3);
                }
                // Other forex pairs use 4-5 decimals
                return number_format($price, 5);
            }
            
            // Gold/Silver (2 decimals)
            if (preg_match('/(XAU|XAG|GOLD|SILVER)/', $symbol)) {
                return number_format($price, 2);
            }
            
            // Indices (2 decimals)
            if (preg_match('/(SPX|NAS|DJI|DAX|FTSE|US30|US500)/', $symbol)) {
                return number_format($price, 2);
            }
        }
        
        // Crypto/default: Dynamic decimal based on price magnitude
        if ($price >= 1000) {
            // Large prices: 2 decimals (e.g., BTC: 45,123.45)
            return number_format($price, 2);
        } elseif ($price >= 1) {
            // Medium prices: 4 decimals (e.g., ETH: 2,345.6789)
            return number_format($price, 4);
        } elseif ($price >= 0.01) {
            // Small prices: 6 decimals
            return number_format($price, 6);
        } else {
            // Very small prices: 8 decimals
            return number_format($price, 8);
        }
    }
}
