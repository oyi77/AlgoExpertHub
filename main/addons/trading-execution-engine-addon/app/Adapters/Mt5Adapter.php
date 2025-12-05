<?php

namespace Addons\TradingExecutionEngine\App\Adapters;

use Addons\TradingExecutionEngine\App\Contracts\ExchangeAdapterInterface;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;

/**
 * MT5 Adapter - Similar to MT4 but uses MT5-specific API endpoints
 * For now, we'll use the same mtapi.io service but with MT5 account IDs
 */
class Mt5Adapter extends Mt4Adapter implements ExchangeAdapterInterface
{
    // MT5 uses the same API structure as MT4 via mtapi.io
    // The only difference is the account type (MT4 vs MT5)
    // This adapter extends Mt4Adapter and can override methods if needed
    
    public function getExchangeName(): string
    {
        return 'MT5';
    }
}
