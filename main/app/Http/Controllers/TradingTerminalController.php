<?php

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Services\TradingTerminalService;
use App\Services\TradingPairProviderService;
use App\Services\PositionManagementService;
use App\Services\MarketDataService;
use App\Repositories\Contracts\ExchangeConnectionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Inertia\Inertia;

class TradingTerminalController extends Controller
{
    protected $tradingTerminalService;
    protected $tradingPairProviderService;
    protected $positionManagementService;
    protected $marketDataService;
    protected $exchangeConnectionRepository;

    public function __construct(
        TradingTerminalService $tradingTerminalService,
        TradingPairProviderService $tradingPairProviderService,
        PositionManagementService $positionManagementService,
        MarketDataService $marketDataService,
        ExchangeConnectionRepositoryInterface $exchangeConnectionRepository
    ) {
        $this->tradingTerminalService = $tradingTerminalService;
        $this->tradingPairProviderService = $tradingPairProviderService;
        $this->positionManagementService = $positionManagementService;
        $this->marketDataService = $marketDataService;
        $this->exchangeConnectionRepository = $exchangeConnectionRepository;
    }

    /**
     * Display legacy trading terminal (GoldenLayout + Interact.js)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $data['title'] = 'Trading Terminal';
        $data['symbol'] = $request->get('symbol', 'BTCUSDT');
        $data['isDemo'] = $request->get('mode', 'real') === 'demo';
        
        // Get user's balance
        $data['realBalance'] = $user->balance ?? 0;
        $data['demoBalance'] = $user->demo_balance ?? 10000;
        
        // Get user's open positions
        $data['openPositions'] = $this->positionManagementService->getUserOpenPositions($user);
        
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
        $data['exchangeConnections'] = $this->exchangeConnectionRepository->getUserConnections($user->id, true);
        $data['hasExchangeConnections'] = $data['exchangeConnections']->isNotEmpty();

        // Return legacy Blade view with GoldenLayout
        return view(Helper::themeView('user.trading_terminal'))->with($data);
    }

    /**
     * Display new trading terminal (React/Inertia - Beta)
     */
    public function betaIndex(Request $request)
    {
        $user = Auth::user();
        $data['title'] = 'Trading Terminal';
        $data['symbol'] = $request->get('symbol', 'BTCUSDT');
        $data['isDemo'] = $request->get('mode', 'real') === 'demo';
        
        // Get user's balance
        $data['realBalance'] = $user->balance ?? 0;
        $data['demoBalance'] = $user->demo_balance ?? 10000;
        
        // Get user's open positions
        $data['openPositions'] = $this->positionManagementService->getUserOpenPositions($user);
        
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
        $data['exchangeConnections'] = $this->exchangeConnectionRepository->getUserConnections($user->id, true);
        $data['hasExchangeConnections'] = $data['exchangeConnections']->isNotEmpty();

        // Return React/Inertia view for Beta
        return Inertia::render('User/TradingTerminal', $data);
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
            $result = $this->tradingTerminalService->placeOrder(Auth::user(), $validated);
            return response()->json($result);

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
            $result = $this->positionManagementService->closePosition($id, Auth::user());
            
            return response()->json([
                'success' => true,
                'message' => 'Position closed successfully',
                'data' => $result,
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
        $positions = $this->positionManagementService->getUserOpenPositions(Auth::user());

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
            // Can be extracted to Service if logic grows, keeping proxy for now
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
            Log::error('Failed to fetch market data', [
                'symbol' => $symbol,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch market data',
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
            $category = $request->get('category', 'all');
            $data = $this->tradingPairProviderService->getTradingPairs($category);
            
            return response()->json([
                'success' => true,
                'data' => $data['data'],
                'categories' => $data['counts'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trading pairs',
                'data' => [],
            ], 500);
        }
    }
}
