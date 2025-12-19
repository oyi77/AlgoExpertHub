<?php

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Models\InternalTrade;
use App\Services\InternalBrokerService;
use App\Services\MarketDataService;
use App\Services\Trading\MarketDataService as TradingMarketDataService;
use App\Events\PositionUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TradingTerminalController extends Controller
{
    protected InternalBrokerService $brokerService;
    protected MarketDataService $marketDataService;
    protected TradingMarketDataService $tradingMarketDataService;

    public function __construct(
        InternalBrokerService $brokerService,
        MarketDataService $marketDataService,
        TradingMarketDataService $tradingMarketDataService
    ) {
        $this->brokerService = $brokerService;
        $this->marketDataService = $marketDataService;
        $this->tradingMarketDataService = $tradingMarketDataService;
    }

    /**
     * Display trading terminal
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $data['title'] = 'Trading Terminal';
        $data['symbol'] = $request->get('symbol', 'BTCUSDT');
        $data['isDemo'] = $request->get('mode', 'real') === 'demo'; // Can be toggled via mode parameter
        
        // Get user's balance
        $data['realBalance'] = $user->balance ?? 0;
        // Demo balance: use user's demo_balance if exists, otherwise default to 10000 USDT
        $data['demoBalance'] = $user->demo_balance ?? 10000;
        
        // Get user's open positions
        $data['openPositions'] = $this->brokerService->getUserOpenPositions($user);
        
        // Get market data
        $data['currentPrice'] = $this->marketDataService->getCurrentPrice($data['symbol']);
        $data['stats24h'] = $this->marketDataService->get24hStats($data['symbol']);
        
        // Get trades for demo mode (legacy trade page)
        $data['trades'] = \App\Models\Trade::when($request->trx, function ($item) use ($request) {
            $item->where('ref', $request->trx);
        })->when($request->date, function ($item) use ($request) {
            $item->whereDate('trade_opens_at', $request->date);
        })->where('user_id', Auth::id())->orderBy('id', 'desc')->paginate(Helper::pagination());
        
        // Get user's active exchange connections for real trading
        $data['exchangeConnections'] = collect();
        $data['hasExchangeConnections'] = false;
        
        if (class_exists(\Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class)) {
            $data['exchangeConnections'] = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('user_id', Auth::id())
                ->where('is_admin_owned', false)
                ->where('is_active', true)
                ->where(function($query) {
                    $query->where('trade_execution_enabled', true)
                          ->orWhere('connection_type', 'EXECUTION_ONLY')
                          ->orWhere('connection_type', 'BOTH');
                })
                ->get();
            $data['hasExchangeConnections'] = $data['exchangeConnections']->isNotEmpty();
        } elseif (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
            $data['exchangeConnections'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('user_id', Auth::id())
                ->where('is_admin_owned', false)
                ->where('is_active', true)
                ->get();
            $data['hasExchangeConnections'] = $data['exchangeConnections']->isNotEmpty();
        }

        return view(Helper::themeView('user.trading_terminal'), $data);
    }

    /**
     * Place order (supports both internal broker and exchange connections)
     */
    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'direction' => 'required|in:buy,sell',
            'quantity' => 'required|numeric|min:0.00000001',
            'sl_price' => 'nullable|numeric',
            'tp_price' => 'nullable|numeric',
            'connection_id' => 'nullable|integer', // Exchange connection ID for real trading
            'mode' => 'nullable|in:demo,real', // Trading mode
        ]);

        try {
            $user = Auth::user();
            $mode = $validated['mode'] ?? 'demo';
            $connectionId = $validated['connection_id'] ?? null;
            
            // Real trading mode: use exchange connection
            if ($mode === 'real' && $connectionId) {
                return $this->placeOrderOnExchange($validated, $connectionId);
            }
            
            // Demo mode: use internal broker
            // Get current market price
            $currentPrice = $this->marketDataService->getCurrentPrice($validated['symbol']);
            
            if (!$currentPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch current market price',
                ], 400);
            }

            // Place order with internal broker
            $trade = $this->brokerService->placeOrder(
                $user,
                $validated['symbol'],
                $validated['direction'],
                $validated['quantity'],
                $currentPrice,
                $validated['sl_price'] ?? null,
                $validated['tp_price'] ?? null
            );

            // Broadcast position update
            broadcast(new PositionUpdated($user->id, [
                'id' => $trade->id,
                'symbol' => $trade->symbol,
                'direction' => $trade->direction,
                'quantity' => $trade->quantity,
                'entry_price' => $trade->entry_price,
                'current_price' => $trade->current_price,
                'pnl' => $trade->pnl,
                'status' => $trade->status,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully (Demo Mode)',
                'data' => [
                    'trade_id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'direction' => $trade->direction,
                    'quantity' => $trade->quantity,
                    'entry_price' => $trade->entry_price,
                    'mode' => 'demo',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to place order', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Place order on connected exchange
     */
    protected function placeOrderOnExchange(array $validated, int $connectionId)
    {
        try {
            // Get connection
            $connection = null;
            if (class_exists(\Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class)) {
                $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $connectionId)
                    ->where('user_id', Auth::id())
                    ->where('is_admin_owned', false)
                    ->where('is_active', true)
                    ->firstOrFail();
            } elseif (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
                $connection = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('id', $connectionId)
                    ->where('user_id', Auth::id())
                    ->where('is_admin_owned', false)
                    ->where('is_active', true)
                    ->firstOrFail();
            }

            if (!$connection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange connection not found or inactive',
                ], 404);
            }

            // Get adapter (same logic as ExecutionLogController)
            $adapter = $this->getAdapter($connection);
            if (!$adapter || !method_exists($adapter, 'placeOrder')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trade execution not supported for this connection type',
                ], 400);
            }

            // Map direction
            $direction = strtolower($validated['direction']);
            if (in_array($direction, ['long', 'short'])) {
                $direction = $direction === 'long' ? 'buy' : 'sell';
            }

            // Execute trade via adapter
            $result = $adapter->placeOrder(
                $validated['symbol'],
                $direction,
                $validated['quantity'],
                'market', // Terminal always uses market orders
                null, // No entry price for market orders
                $validated['sl_price'] ?? null,
                $validated['tp_price'] ?? null,
                'Terminal: ' . $validated['symbol']
            );

            if (isset($result['success']) && $result['success'] === false) {
                throw new \Exception($result['message'] ?? 'Trade execution failed');
            }

            // Create execution log
            $ExecutionLog = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class;
            $log = $ExecutionLog::create([
                'connection_id' => $connection->id,
                'signal_id' => null,
                'symbol' => $validated['symbol'],
                'direction' => $direction,
                'quantity' => $validated['quantity'],
                'entry_price' => $result['data']['openPrice'] ?? $result['data']['price'] ?? null,
                'sl_price' => $validated['sl_price'],
                'tp_price' => $validated['tp_price'],
                'execution_type' => 'market',
                'status' => 'executed',
                'executed_at' => now(),
                'order_id' => $result['orderId'] ?? $result['numericTicket'] ?? null,
            ]);

            // Create position if needed
            $orderId = $result['orderId'] ?? $result['numericTicket'] ?? null;
            $positionId = $result['positionId'] ?? null;
            
            if ($orderId || $positionId) {
                $ExecutionPosition = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class;
                $entryPrice = $result['data']['openPrice'] ?? $result['data']['price'] ?? 0;
                
                $ExecutionPosition::create([
                    'connection_id' => $connection->id,
                    'execution_log_id' => $log->id,
                    'order_id' => $orderId ? (string)$orderId : null,
                    'symbol' => $validated['symbol'],
                    'direction' => $direction,
                    'quantity' => $validated['quantity'],
                    'entry_price' => $entryPrice > 0 ? $entryPrice : 0,
                    'current_price' => $entryPrice > 0 ? $entryPrice : 0,
                    'sl_price' => $validated['sl_price'],
                    'tp_price' => $validated['tp_price'],
                    'status' => 'open',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully on exchange',
                'data' => [
                    'order_id' => $orderId,
                    'position_id' => $positionId,
                    'symbol' => $validated['symbol'],
                    'direction' => strtoupper($direction),
                    'quantity' => $validated['quantity'],
                    'entry_price' => $result['data']['openPrice'] ?? $result['data']['price'] ?? 'Market',
                    'mode' => 'real',
                    'connection' => $connection->name,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to place order on exchange', [
                'user_id' => Auth::id(),
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get adapter for connection (same logic as ExecutionLogController)
     */
    protected function getAdapter($connection)
    {
        $connectionType = $connection->connection_type ?? null;
        $provider = $connection->provider ?? null;
        $type = $connection->type ?? null;
        $exchangeName = $connection->exchange_name ?? null;
        
        if ($connectionType === 'CRYPTO_EXCHANGE' || ($type === 'crypto' && !$connectionType)) {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter(
                $connection->credentials,
                $provider ?? $exchangeName ?? 'binance'
            );
        }
        
        if ($provider === 'mtapi_grpc' || (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'mtapi_grpc')) {
            $credentials = $connection->credentials;
            $globalSettings = \App\Services\GlobalConfigurationService::get('mtapi_global_settings', []);
            if (!empty($globalSettings['base_url'])) {
                $credentials['base_url'] = $globalSettings['base_url'];
            }
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter($credentials);
        } elseif ($provider === 'metaapi' || (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'metaapi')) {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter($connection->credentials);
        } else {
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter($connection->credentials);
        }
    }

    /**
     * Close position
     */
    public function closePosition(Request $request, $id)
    {
        try {
            $trade = InternalTrade::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            if ($trade->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Position is already closed',
                ], 400);
            }

            // Get current market price
            $currentPrice = $this->marketDataService->getCurrentPrice($trade->symbol);
            
            if (!$currentPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch current market price',
                ], 400);
            }

            // Close position
            $this->brokerService->closePosition($trade, $currentPrice);

            // Broadcast position update
            broadcast(new PositionUpdated(Auth::id(), [
                'id' => $trade->id,
                'status' => 'closed',
                'close_price' => $currentPrice,
                'pnl' => $trade->pnl,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Position closed successfully',
                'data' => [
                    'trade_id' => $trade->id,
                    'close_price' => $currentPrice,
                    'pnl' => $trade->pnl,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to close position', [
                'trade_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get open positions
     */
    public function getPositions()
    {
        $positions = $this->brokerService->getUserOpenPositions(Auth::user());

        return response()->json([
            'success' => true,
            'data' => $positions->map(function ($position) {
                return [
                    'id' => $position->id,
                    'symbol' => $position->symbol,
                    'direction' => $position->direction,
                    'quantity' => $position->quantity,
                    'entry_price' => $position->entry_price,
                    'current_price' => $position->current_price,
                    'sl_price' => $position->sl_price,
                    'tp_price' => $position->tp_price,
                    'pnl' => $position->pnl,
                    'opened_at' => $position->opened_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Get market data
     */
    public function getMarketData(Request $request)
    {
        $symbol = $request->get('symbol', 'BTCUSDT');
        $type = $request->get('type', 'price'); // price, orderbook, trades, candlestick

        try {
            \Log::info('Market data request', [
                'symbol' => $symbol,
                'type' => $type,
                'interval' => $request->get('interval'),
                'limit' => $request->get('limit'),
            ]);

            $data = match ($type) {
                'price' => [
                    'price' => $this->marketDataService->getCurrentPrice($symbol),
                    'stats' => $this->marketDataService->get24hStats($symbol),
                ],
                'orderbook' => $this->marketDataService->getOrderbook($symbol, 20),
                'trades' => $this->marketDataService->getRecentTrades($symbol, 50),
                'candlestick' => $this->marketDataService->getCandlestickData(
                    $symbol,
                    $request->get('interval', '5m'),
                    (int)$request->get('limit', 100)
                ),
                default => null,
            };

            if ($data === null) {
                \Log::warning('Invalid data type requested', ['type' => $type]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data type: ' . $type,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch market data', [
                'symbol' => $symbol,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch market data: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get all trading pairs with market data for symbol selector
     */
    public function getTradingPairs(Request $request)
    {
        try {
            $category = $request->get('category', 'all'); // all, crypto, forex, indices, commodities, stocks
            
            // Get crypto pairs
            $cryptoData = $this->tradingMarketDataService->getCryptoData(50);
            $cryptoPairs = array_map(function ($item) {
                return [
                    'symbol' => $item['symbol'] . 'USDT',
                    'displaySymbol' => $item['symbol'] . '/USDT',
                    'name' => $item['name'],
                    'category' => 'crypto',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '100x',
                    'icon' => $item['image'] ?? null,
                ];
            }, $cryptoData);

            // Get forex pairs
            $forexData = $this->tradingMarketDataService->getForexData(20);
            $forexPairs = array_map(function ($item) {
                $symbol = $item['symbol'];
                // Format display symbol for forex (EURUSD -> EUR/USD)
                $displaySymbol = $symbol;
                if (strlen($symbol) === 6) {
                    $displaySymbol = substr($symbol, 0, 3) . '/' . substr($symbol, 3, 3);
                }
                
                return [
                    'symbol' => $symbol,
                    'displaySymbol' => $displaySymbol,
                    'name' => $item['name'],
                    'category' => 'forex',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '200x',
                    'icon' => null,
                ];
            }, $forexData);

            // Get indices
            $indicesData = $this->tradingMarketDataService->getIndicesData(15);
            $indicesPairs = array_map(function ($item) {
                return [
                    'symbol' => $item['symbol'],
                    'displaySymbol' => $item['symbol'],
                    'name' => $item['name'],
                    'category' => 'indices',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '50x',
                    'icon' => null,
                ];
            }, $indicesData);

            // Get commodities
            $commoditiesData = $this->tradingMarketDataService->getCommoditiesData(15);
            $commoditiesPairs = array_map(function ($item) {
                $symbol = $item['symbol'];
                // Format display symbol (XAUUSD -> XAU/USD)
                $displaySymbol = $symbol;
                if (strlen($symbol) === 6 && strpos($symbol, 'USD') !== false) {
                    $base = substr($symbol, 0, 3);
                    $displaySymbol = $base . '/USD';
                }
                
                return [
                    'symbol' => $symbol,
                    'displaySymbol' => $displaySymbol,
                    'name' => $item['name'],
                    'category' => 'commodities',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '100x',
                    'icon' => null,
                ];
            }, $commoditiesData);

            // Get stocks
            $stocksData = $this->tradingMarketDataService->getStocksData(15);
            $stocksPairs = array_map(function ($item) {
                return [
                    'symbol' => $item['symbol'],
                    'displaySymbol' => $item['symbol'],
                    'name' => $item['name'],
                    'category' => 'stocks',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '5x',
                    'icon' => null,
                ];
            }, $stocksData);

            // Combine all pairs
            $allPairs = array_merge($cryptoPairs, $forexPairs, $indicesPairs, $commoditiesPairs, $stocksPairs);

            // Filter by category
            if ($category !== 'all') {
                $allPairs = array_filter($allPairs, function ($pair) use ($category) {
                    return $pair['category'] === $category;
                });
            }

            // Format volume for display and fix symbol format
            $allPairs = array_map(function ($pair) {
                $volume = $pair['volume'];
                if ($volume >= 1000000000) {
                    $pair['volumeDisplay'] = number_format($volume / 1000000000, 2) . 'B';
                } elseif ($volume >= 1000000) {
                    $pair['volumeDisplay'] = number_format($volume / 1000000, 2) . 'M';
                } else {
                    $pair['volumeDisplay'] = number_format($volume, 0);
                }
                
                // Ensure displaySymbol is properly formatted
                if (!isset($pair['displaySymbol']) || empty($pair['displaySymbol'])) {
                    if ($pair['category'] === 'crypto') {
                        $pair['displaySymbol'] = str_replace('USDT', '/USDT', $pair['symbol']);
                    } else {
                        // For forex, format like EUR/USD
                        $symbol = $pair['symbol'];
                        if (strlen($symbol) === 6) {
                            $pair['displaySymbol'] = substr($symbol, 0, 3) . '/' . substr($symbol, 3, 3);
                        } else {
                            $pair['displaySymbol'] = $symbol;
                        }
                    }
                }
                
                return $pair;
            }, $allPairs);

            return response()->json([
                'success' => true,
                'data' => array_values($allPairs),
                'categories' => [
                    'all' => count($cryptoPairs) + count($forexPairs) + count($indicesPairs) + count($commoditiesPairs) + count($stocksPairs),
                    'crypto' => count($cryptoPairs),
                    'forex' => count($forexPairs),
                    'indices' => count($indicesPairs),
                    'commodities' => count($commoditiesPairs),
                    'stocks' => count($stocksPairs),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch trading pairs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trading pairs',
                'data' => [],
            ], 500);
        }
    }
}
