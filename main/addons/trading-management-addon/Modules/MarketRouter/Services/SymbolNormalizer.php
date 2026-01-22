<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\MarketRouter\Services;

use Addons\TradingManagement\Modules\MarketRouter\Exceptions\InvalidSymbolException;

class SymbolNormalizer
{
    public function normalize(string $symbol, string $marketType): string
    {
        $normalized = match ($marketType) {
            'crypto' => $this->normalizeCrypto($symbol),
            'forex' => $this->normalizeForex($symbol),
            default => throw new \InvalidArgumentException("Unknown market type: {$marketType}"),
        };
        
        $this->validate($normalized, $marketType);
        
        return $normalized;
    }
    
    protected function normalizeCrypto(string $symbol): string
    {
        $symbol = str_replace(['/', '-', '_'], '', $symbol);
        
        return strtoupper($symbol);
    }
    
    protected function normalizeForex(string $symbol): string
    {
        $symbol = str_replace(['/', '-', '_'], '', $symbol);
        
        return strtoupper($symbol);
    }
    
    protected function validate(string $symbol, string $marketType): void
    {
        if (empty($symbol)) {
            throw new InvalidSymbolException("Symbol cannot be empty");
        }
        
        if ($marketType === 'forex' && strlen($symbol) !== 6) {
            throw new InvalidSymbolException("Forex symbols must be 6 characters (e.g., EURUSD)");
        }
    }
}
