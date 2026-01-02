<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SymbolSpecService
 * 
 * Calculates accurate pip values, contract sizes, and symbol-specific parameters
 * for proper position sizing and risk management.
 */
class SymbolSpecService
{
    /**
     * Standard contract sizes by market type
     */
    protected const CONTRACT_SIZES = [
        'fx' => 100000,      // Standard FX lot = 100,000 units
        'crypto' => 1,       // Crypto contracts are typically 1 unit
        'commodity' => 100,   // Gold/XAU typically 100 oz per lot
        'stock' => 1,         // Stocks are typically 1 share per lot
    ];

    /**
     * Get pip size for a symbol
     * 
     * @param string $symbol Trading symbol (e.g., 'EUR/USD', 'GBP/JPY', 'XAU/USD')
     * @param string|null $accountCurrency Account currency (e.g., 'USD', 'EUR')
     * @return float Pip size (0.0001 for most FX, 0.01 for JPY, 0.10 for XAU)
     */
    public function getPipSize(string $symbol, ?string $accountCurrency = null): float
    {
        $symbol = strtoupper($symbol);
        
        // JPY pairs have different pip size (0.01)
        if (str_contains($symbol, 'JPY')) {
            return 0.01;
        }

        // XAU (gold) has different pip size (0.10)
        if (str_contains($symbol, 'XAU') || str_contains($symbol, 'GOLD')) {
            return 0.10;
        }

        // XAG (silver) has different pip size (0.01)
        if (str_contains($symbol, 'XAG') || str_contains($symbol, 'SILVER')) {
            return 0.01;
        }

        // Crypto pairs - typically 0.01 or 0.0001 depending on price
        if (str_contains($symbol, 'BTC') || str_contains($symbol, 'ETH') || 
            str_contains($symbol, 'USDT') || str_contains($symbol, 'USDC')) {
            // For crypto, pip size depends on price level
            // High-value coins (BTC, ETH) use 0.01, others use 0.0001
            if (str_contains($symbol, 'BTC') || str_contains($symbol, 'ETH')) {
                return 0.01;
            }
            return 0.0001;
        }

        // Most FX pairs use 0.0001 (4 decimal places)
        return 0.0001;
    }

    /**
     * Get pip value (how much 1 pip is worth in account currency)
     * 
     * @param string $symbol Trading symbol
     * @param float $lotSize Lot size (e.g., 1.0 for 1 standard lot)
     * @param string $accountCurrency Account currency (e.g., 'USD', 'EUR')
     * @param float $entryPrice Entry price for the trade
     * @return float Pip value in account currency
     */
    public function getPipValue(string $symbol, float $lotSize, string $accountCurrency, float $entryPrice): float
    {
        $cacheKey = "symbol_pip_value:{$symbol}:{$lotSize}:{$accountCurrency}:{$entryPrice}";
        
        return Cache::remember($cacheKey, 3600, function () use ($symbol, $lotSize, $accountCurrency, $entryPrice) {
            $symbol = strtoupper($symbol);
            $accountCurrency = strtoupper($accountCurrency);
            $pipSize = $this->getPipSize($symbol, $accountCurrency);
            $contractSize = $this->getContractSize($symbol);
            
            // For FX pairs
            if ($this->isForexPair($symbol)) {
                return $this->calculateForexPipValue($symbol, $lotSize, $accountCurrency, $entryPrice, $pipSize, $contractSize);
            }
            
            // For crypto pairs
            if ($this->isCryptoPair($symbol)) {
                return $this->calculateCryptoPipValue($symbol, $lotSize, $accountCurrency, $entryPrice, $pipSize);
            }
            
            // For commodities (XAU, XAG, etc.)
            if ($this->isCommodity($symbol)) {
                return $this->calculateCommodityPipValue($symbol, $lotSize, $accountCurrency, $entryPrice, $pipSize, $contractSize);
            }
            
            // Default calculation for other instruments
            return $lotSize * $contractSize * $pipSize;
        });
    }

    /**
     * Get contract size for a symbol
     * 
     * @param string $symbol Trading symbol
     * @param string|null $exchange Exchange name (optional, for exchange-specific contracts)
     * @return float Contract size (100,000 for standard FX lot, 1 for crypto, etc.)
     */
    public function getContractSize(string $symbol, ?string $exchange = null): float
    {
        $symbol = strtoupper($symbol);
        
        // FX pairs
        if ($this->isForexPair($symbol)) {
            return self::CONTRACT_SIZES['fx'];
        }
        
        // Crypto pairs
        if ($this->isCryptoPair($symbol)) {
            return self::CONTRACT_SIZES['crypto'];
        }
        
        // Commodities
        if ($this->isCommodity($symbol)) {
            return self::CONTRACT_SIZES['commodity'];
        }
        
        // Stocks/Indices
        if ($this->isStockOrIndex($symbol)) {
            return self::CONTRACT_SIZES['stock'];
        }
        
        // Default to FX contract size
        return self::CONTRACT_SIZES['fx'];
    }

    /**
     * Get complete symbol specification
     * 
     * @param string $symbol Trading symbol
     * @param string|null $exchange Exchange name
     * @param string $accountCurrency Account currency
     * @return array Symbol specification
     */
    public function getSymbolSpec(string $symbol, ?string $exchange = null, string $accountCurrency = 'USD'): array
    {
        $symbol = strtoupper($symbol);
        $accountCurrency = strtoupper($accountCurrency);
        
        return [
            'symbol' => $symbol,
            'exchange' => $exchange,
            'account_currency' => $accountCurrency,
            'pip_size' => $this->getPipSize($symbol, $accountCurrency),
            'contract_size' => $this->getContractSize($symbol, $exchange),
            'market_type' => $this->getMarketType($symbol),
            'quote_currency' => $this->getQuoteCurrency($symbol),
            'base_currency' => $this->getBaseCurrency($symbol),
        ];
    }

    /**
     * Check if symbol is a forex pair
     */
    protected function isForexPair(string $symbol): bool
    {
        $forexCurrencies = ['USD', 'EUR', 'GBP', 'JPY', 'CHF', 'AUD', 'CAD', 'NZD', 'SEK', 'NOK', 'DKK', 'PLN', 'HUF', 'CZK', 'RUB', 'TRY', 'ZAR', 'MXN', 'BRL', 'CNY', 'HKD', 'SGD'];
        
        $parts = preg_split('/[\/\-_]/', $symbol);
        if (count($parts) !== 2) {
            return false;
        }
        
        return in_array($parts[0], $forexCurrencies) && in_array($parts[1], $forexCurrencies);
    }

    /**
     * Check if symbol is a crypto pair
     */
    protected function isCryptoPair(string $symbol): bool
    {
        $cryptoCurrencies = ['BTC', 'ETH', 'BNB', 'ADA', 'SOL', 'XRP', 'DOT', 'DOGE', 'MATIC', 'AVAX', 'LINK', 'UNI', 'LTC', 'BCH', 'ALGO', 'ATOM', 'VET', 'FIL', 'TRX', 'ETC', 'XLM', 'EOS', 'AAVE', 'MKR', 'COMP', 'SUSHI', 'YFI', 'SNX', 'CRV', 'USDT', 'USDC', 'BUSD', 'DAI', 'TUSD', 'PAX', 'USDP'];
        
        $parts = preg_split('/[\/\-_]/', $symbol);
        if (count($parts) !== 2) {
            return false;
        }
        
        return in_array($parts[0], $cryptoCurrencies) || in_array($parts[1], $cryptoCurrencies);
    }

    /**
     * Check if symbol is a commodity
     */
    protected function isCommodity(string $symbol): bool
    {
        return str_contains($symbol, 'XAU') || 
               str_contains($symbol, 'XAG') || 
               str_contains($symbol, 'GOLD') || 
               str_contains($symbol, 'SILVER') ||
               str_contains($symbol, 'OIL') ||
               str_contains($symbol, 'CRUDE');
    }

    /**
     * Check if symbol is a stock or index
     */
    protected function isStockOrIndex(string $symbol): bool
    {
        // Simple heuristic - can be enhanced
        return !$this->isForexPair($symbol) && 
               !$this->isCryptoPair($symbol) && 
               !$this->isCommodity($symbol);
    }

    /**
     * Get market type
     */
    protected function getMarketType(string $symbol): string
    {
        if ($this->isForexPair($symbol)) {
            return 'forex';
        }
        if ($this->isCryptoPair($symbol)) {
            return 'crypto';
        }
        if ($this->isCommodity($symbol)) {
            return 'commodity';
        }
        return 'other';
    }

    /**
     * Get quote currency from symbol
     */
    protected function getQuoteCurrency(string $symbol): ?string
    {
        $parts = preg_split('/[\/\-_]/', $symbol);
        return count($parts) === 2 ? $parts[1] : null;
    }

    /**
     * Get base currency from symbol
     */
    protected function getBaseCurrency(string $symbol): ?string
    {
        $parts = preg_split('/[\/\-_]/', $symbol);
        return count($parts) === 2 ? $parts[0] : null;
    }

    /**
     * Calculate pip value for forex pairs
     */
    protected function calculateForexPipValue(string $symbol, float $lotSize, string $accountCurrency, float $entryPrice, float $pipSize, float $contractSize): float
    {
        $parts = preg_split('/[\/\-_]/', $symbol);
        if (count($parts) !== 2) {
            return 10.0 * $lotSize; // Fallback
        }
        
        $baseCurrency = $parts[0];
        $quoteCurrency = $parts[1];
        
        // If account currency is the quote currency, pip value is straightforward
        if ($accountCurrency === $quoteCurrency) {
            // Pip value = lot size * contract size * pip size
            return $lotSize * $contractSize * $pipSize;
        }
        
        // If account currency is the base currency
        if ($accountCurrency === $baseCurrency) {
            // Need to convert: pip value in quote currency / entry price
            $pipValueInQuote = $lotSize * $contractSize * $pipSize;
            return $entryPrice > 0 ? $pipValueInQuote / $entryPrice : 0;
        }
        
        // Account currency is different from both base and quote
        // For simplicity, assume USD conversion
        // In production, you'd fetch exchange rate
        if ($accountCurrency === 'USD') {
            // If quote is USD, use direct calculation
            if ($quoteCurrency === 'USD') {
                return $lotSize * $contractSize * $pipSize;
            }
            // If base is USD, need conversion
            if ($baseCurrency === 'USD') {
                $pipValueInQuote = $lotSize * $contractSize * $pipSize;
                return $entryPrice > 0 ? $pipValueInQuote / $entryPrice : 0;
            }
        }
        
        // Fallback: assume $10 per pip for 1.0 lot (standard for major pairs with USD account)
        return 10.0 * $lotSize;
    }

    /**
     * Calculate pip value for crypto pairs
     */
    protected function calculateCryptoPipValue(string $symbol, float $lotSize, string $accountCurrency, float $entryPrice, float $pipSize): float
    {
        // For crypto, pip value = lot size * entry price * pip size
        // This gives the value of 1 pip in the quote currency
        $pipValueInQuote = $lotSize * $entryPrice * $pipSize;
        
        // If account currency matches quote currency, return directly
        $quoteCurrency = $this->getQuoteCurrency($symbol);
        if ($quoteCurrency && strtoupper($accountCurrency) === strtoupper($quoteCurrency)) {
            return $pipValueInQuote;
        }
        
        // Otherwise, assume 1:1 for major stablecoins (USDT, USDC, etc.)
        if (in_array(strtoupper($quoteCurrency ?? ''), ['USDT', 'USDC', 'BUSD', 'DAI', 'TUSD'])) {
            return $pipValueInQuote;
        }
        
        // Fallback: return pip value in quote currency
        return $pipValueInQuote;
    }

    /**
     * Calculate pip value for commodities
     */
    protected function calculateCommodityPipValue(string $symbol, float $lotSize, string $accountCurrency, float $entryPrice, float $pipSize, float $contractSize): float
    {
        // For commodities, pip value = lot size * contract size * pip size
        // This gives the value in the quote currency (usually USD)
        $pipValueInQuote = $lotSize * $contractSize * $pipSize;
        
        // If account currency is USD (common for commodities), return directly
        if (strtoupper($accountCurrency) === 'USD') {
            return $pipValueInQuote;
        }
        
        // For other account currencies, would need exchange rate
        // For now, return pip value in quote currency
        return $pipValueInQuote;
    }
}

