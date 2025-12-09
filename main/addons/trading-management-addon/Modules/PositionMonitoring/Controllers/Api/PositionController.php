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
 * PositionController
 * 
 * API endpoints for real-time position and balance control
 */
class PositionController extends Controller
{
    protected PositionControlService $controlService;

    public function __construct(PositionControlService $controlService)
    {
        $this->controlService = $controlService;
    }

    /**
     * Get all positions for trading bot
     * 
     * GET /api/trading-bots/{id}/positions
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
     * Update position TP/SL
     * 
     * PATCH /api/trading-bots/{id}/positions/{position_id}
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
     * Close position manually
     * 
     * POST /api/trading-bots/{id}/positions/{position_id}/close
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
     * Get current balance
     * 
     * GET /api/trading-bots/{id}/balance
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
