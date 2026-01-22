<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\MarketRouter;

use Addons\TradingManagement\Modules\MarketRouter\Services\SymbolNormalizer;
use Addons\TradingManagement\Modules\MarketRouter\Services\TradingHoursService;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter;

class MarketRouter
{
    public function __construct(
        protected SymbolNormalizer $symbolNormalizer,
        protected TradingHoursService $tradingHours
    ) {}
    
    /**
     * Normalize symbol for market type.
     */
    public function normalizeSymbol(string $symbol, string $marketType): string
    {
        return $this->symbolNormalizer->normalize($symbol, $marketType);
    }
    
    /**
     * Check if market is open for trading.
     */
    public function isMarketOpen(string $marketType, ?string $symbol = null): bool
    {
        return $this->tradingHours->isOpen($marketType, $symbol);
    }
    
    /**
     * Get lot size for market type.
     */
    public function getLotSize(
        float $amount,
        string $symbol,
        ExchangeConnection $connection
    ): float {
        return match ($connection->type) {
            'crypto' => $this->getCryptoLotSize($amount, $symbol),
            'fx' => $this->getForexLotSize($amount, $symbol, $connection),
            default => $amount,
        };
    }
    
    /**
     * Get adapter for exchange connection.
     */
    public function getAdapter(ExchangeConnection $connection): object
    {
        return match ($connection->type) {
            'crypto' => app(CcxtAdapter::class)->setConnection($connection),
            'fx' => app(MetaApiAdapter::class)->setConnection($connection),
        };
    }
    
    /**
     * Crypto lot size calculation.
     */
    protected function getCryptoLotSize(float $amount, string $symbol): float
    {
        // Crypto: amount is already in base currency
        return $amount;
    }
    
    /**
     * Forex lot size calculation.
     */
    protected function getForexLotSize(
        float $amount,
        string $symbol,
        ExchangeConnection $connection
    ): float {
        // Forex: 1 standard lot = 100,000 units
        // Mini lot = 10,000 units
        // Micro lot = 1,000 units
        return $amount / 100000;
    }
}
