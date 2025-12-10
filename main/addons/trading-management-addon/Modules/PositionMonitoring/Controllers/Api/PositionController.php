<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Controllers\Api;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Addons\TradingManagement\Modules\PositionMonitoring\Services\PositionControlService;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Trading Management
 * Trading bot position and balance management endpoints
 */
class PositionController extends Controller
{
    protected PositionControlService $controlService;

    public function __construct(PositionControlService $controlService)
    {
        $this->controlService = $controlService;
    }

    /**
     * List Positions
     * 
     * Get all positions for a trading bot
     * 
     * @param TradingBot $bot
     * @return JsonResponse
     * @authenticated
     * @urlParam bot integer required Trading bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "symbol": "EUR/USD",
     *       "direction": "buy",
     *       "entry_price": "1.1000",
     *       "current_price": "1.1050",
     *       "stop_loss": "1.0950",
     *       "take_profit": "1.1100",
     *       "quantity": 0.1,
     *       "status": "open",
     *       "profit_loss": "50.00",
     *       "profit_loss_percentage": 5.0,
     *       "opened_at": "2023-01-01T00:00:00.000000Z",
     *       "closed_at": null
     *     }
     *   ]
     * }
     */
    public function index(TradingBot $bot): JsonResponse
    {
        $positions = TradingBotPosition::where('bot_id', $bot->id)
            ->orderBy('opened_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $positions->map(function ($position) {
                return [
                    'id' => $position->id,
                    'symbol' => $position->symbol,
                    'direction' => $position->direction,
                    'entry_price' => $position->entry_price,
                    'current_price' => $position->current_price,
                    'stop_loss' => $position->stop_loss,
                    'take_profit' => $position->take_profit,
                    'quantity' => $position->quantity,
                    'status' => $position->status,
                    'profit_loss' => $position->profit_loss,
                    'profit_loss_percentage' => $position->getProfitLossPercentage(),
                    'opened_at' => $position->opened_at?->toIso8601String(),
                    'closed_at' => $position->closed_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Update Position
     * 
     * Update stop loss or take profit for a position
     * 
     * @param TradingBot $bot
     * @param TradingBotPosition $position
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @urlParam bot integer required Trading bot ID. Example: 1
     * @urlParam position integer required Position ID. Example: 1
     * @bodyParam stop_loss decimal optional New stop loss price. Example: 1.0950
     * @bodyParam take_profit decimal optional New take profit price. Example: 1.1100
     * @response 200 {
     *   "success": true,
     *   "message": "Position updated successfully",
     *   "data": {
     *     "position": {...}
     *   }
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Update failed"
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "Position does not belong to this bot"
     * }
     */
    public function update(TradingBot $bot, TradingBotPosition $position, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stop_loss' => 'nullable|numeric|min:0',
            'take_profit' => 'nullable|numeric|min:0',
        ]);

        // Verify position belongs to bot
        if ($position->bot_id !== $bot->id) {
            return response()->json([
                'success' => false,
                'message' => 'Position does not belong to this bot',
            ], 403);
        }

        try {
            $result = $this->controlService->updatePosition($position, $validated);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'position' => $position->fresh(),
                ],
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Close Position
     * 
     * Manually close an open position
     * 
     * @param TradingBot $bot
     * @param TradingBotPosition $position
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @urlParam bot integer required Trading bot ID. Example: 1
     * @urlParam position integer required Position ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Position closed successfully",
     *   "data": {
     *     "position": {...},
     *     "profit_loss": "50.00"
     *   }
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Position is already closed"
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "Position does not belong to this bot"
     * }
     */
    public function close(TradingBot $bot, TradingBotPosition $position, Request $request): JsonResponse
    {
        // Verify position belongs to bot
        if ($position->bot_id !== $bot->id) {
            return response()->json([
                'success' => false,
                'message' => 'Position does not belong to this bot',
            ], 403);
        }

        if (!$position->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'Position is already closed',
            ], 400);
        }

        try {
            $result = $this->controlService->closePosition($position, 'manual');

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'position' => $position->fresh(),
                    'profit_loss' => $position->profit_loss,
                ],
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Balance
     * 
     * Get current account balance for a trading bot's exchange connection
     * 
     * @param TradingBot $bot
     * @return JsonResponse
     * @authenticated
     * @urlParam bot integer required Trading bot ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "balance": "10000.00",
     *     "currency": "USD",
     *     "available": "9500.00",
     *     "in_use": "500.00"
     *   }
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Bot has no exchange connection"
     * }
     */
    public function balance(TradingBot $bot): JsonResponse
    {
        try {
            $connection = $bot->exchangeConnection;
            if (!$connection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bot has no exchange connection',
                ], 400);
            }

            $balance = $this->controlService->getBalance($connection);

            return response()->json([
                'success' => true,
                'data' => $balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
