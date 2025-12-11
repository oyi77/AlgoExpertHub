<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\CryptoTradeController as WebCryptoTradeController;

class CryptoTradeController extends Controller
{
    protected $webController;

    public function __construct()
    {
        $this->webController = new WebCryptoTradeController();
    }

    /**
     * Get current price for symbol
     */
    public function currentPrice(Request $request): JsonResponse
    {
        $symbol = $request->get('symbol');
        
        if (!$symbol) {
            return response()->json([
                'success' => false,
                'message' => 'Symbol parameter is required'
            ], 400);
        }

        try {
            // Call web controller method if it exists
            $price = $this->webController->currentPrice($request);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'symbol' => $symbol,
                    'price' => $price ?? null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get price: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get latest ticker
     */
    public function latestTicker(Request $request): JsonResponse
    {
        try {
            $ticker = $this->webController->latestTicker($request);
            
            return response()->json([
                'success' => true,
                'data' => $ticker
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get ticker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Open a trade
     */
    public function openTrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'type' => 'required|in:buy,sell',
            'amount' => 'required|numeric|min:0.0001',
            'price' => 'nullable|numeric',
            'stop_loss' => 'nullable|numeric',
            'take_profit' => 'nullable|numeric',
        ]);

        try {
            $result = $this->webController->openTrade($request);
            
            return response()->json([
                'success' => true,
                'message' => 'Trade opened successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to open trade: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List user trades
     */
    public function trades(Request $request): JsonResponse
    {
        try {
            $trades = \DB::table('trades')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $trades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close a trade
     */
    public function closeTrade(Request $request, $id): JsonResponse
    {
        try {
            $trade = \DB::table('trades')
                ->where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$trade) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trade not found'
                ], 404);
            }

            // Call web controller close method
            $result = $this->webController->tradeClose($request);

            return response()->json([
                'success' => true,
                'message' => 'Trade closed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close trade: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream prices (SSE endpoint)
     */
    public function streamPrices(Request $request): JsonResponse
    {
        // For API, return current prices instead of SSE stream
        $symbols = $request->get('symbols', []);
        
        try {
            $prices = [];
            foreach ($symbols as $symbol) {
                $prices[$symbol] = $this->webController->currentPrice($request->merge(['symbol' => $symbol]));
            }

            return response()->json([
                'success' => true,
                'data' => $prices,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get prices: ' . $e->getMessage()
            ], 500);
        }
    }
}

