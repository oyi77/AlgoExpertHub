<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesCrudOperations;
use Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesTestingOperations;
use Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\ProvidesHelperMethods;
use Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesMetaApiOperations;
use Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesStreamingOperations;
use Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesCopyTrading;

/**
 * Unified Exchange Connection Controller
 * 
 * Manages connections that can be used for BOTH data fetching AND trade execution
 * 
 * This controller has been refactored into traits for better organization:
 * - HandlesCrudOperations: CRUD operations (index, create, store, show, edit, update, destroy, transferOwnership)
 * - HandlesTestingOperations: Testing operations (testConnection, testDataFetch, testExecution, activate/deactivate)
 * - ProvidesHelperMethods: Helper methods (getAdapter, getDefaultSymbol, MetaApi helpers)
 * - HandlesMetaApiOperations: MetaAPI-specific operations (addAccount, getStatus, monitor, generateToken)
 * - HandlesStreamingOperations: Streaming operations (streamMarketData, streamPositions, streamOrders, streamBalance, testStream*)
 * - HandlesCopyTrading: Copy trading operations (toggleCopyTrading, getCopyTradingStats)
 */
class ExchangeConnectionController extends Controller
{
    use HandlesCrudOperations,
        HandlesTestingOperations,
        ProvidesHelperMethods,
        HandlesMetaApiOperations,
        HandlesStreamingOperations,
        HandlesCopyTrading;

    protected ExchangeConnectionService $connectionService;

    public function __construct(ExchangeConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }
}
