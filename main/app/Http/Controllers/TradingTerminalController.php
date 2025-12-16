<?php

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Models\InternalTrade;
use App\Services\InternalBrokerService;
use App\Services\MarketDataService;
use App\Events\PositionUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TradingTerminalController extends Controller
{
    protected InternalBrokerService $brokerService;
    protected MarketDataService $marketDataService;

    public function __construct(
        InternalBrokerService $brokerService,
        MarketDataService $marketDataService
    ) {
        $this->brokerService = $brokerService;
        $this->marketDataService = $marketDataService;
    }

    /**
     * Display trading terminal
     */
    public function index(Request $request)
    {
        $data['title'] = 'Trading Terminal';
        $data['symbol'] = $request->get('symbol', 'BTCUSDT');
        $data['isDemo'] = false; // Default to real trading logic or fetch from user preference
        
        // Get user's open positions
        $data['openPositions'] = $this->brokerService->getUserOpenPositions(Auth::user());
        
        // Get market data
        $data['currentPrice'] = $this->marketDataService->getCurrentPrice($data['symbol']);
        $data['stats24h'] = $this->marketDataService->get24hStats($data['symbol']);
        
        // Get trades for demo mode (legacy trade page)
        $data['trades'] = \App\Models\Trade::when($request->trx, function ($item) use ($request) {
            $item->where('ref', $request->trx);
        })->when($request->date, function ($item) use ($request) {
            $item->whereDate('trade_opens_at', $request->date);
        })->where('user_id', Auth::id())->orderBy('id', 'desc')->paginate(Helper::pagination());
        
        // Check if user has exchange connections
        $data['hasExchangeConnections'] = false;
        if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
            $data['hasExchangeConnections'] = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('user_id', Auth::id())
                ->where('is_active', true)
                ->exists();
        }

        return view(Helper::themeView('user.trading_terminal'), $data);
    }

    /**
     * Place order (internal broker)
     */
    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'direction' => 'required|in:buy,sell',
            'quantity' => 'required|numeric|min:0.00000001',
            'sl_price' => 'nullable|numeric',
            'tp_price' => 'nullable|numeric',
        ]);

        try {
            $user = Auth::user();
            
            // Get current market price
            $currentPrice = $this->marketDataService->getCurrentPrice($validated['symbol']);
            
            if (!$currentPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch current market price',
                ], 400);
            }

            // Place order
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
                'message' => 'Order placed successfully',
                'data' => [
                    'trade_id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'direction' => $trade->direction,
                    'quantity' => $trade->quantity,
                    'entry_price' => $trade->entry_price,
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
}
