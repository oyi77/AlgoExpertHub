<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Events\PositionUpdated;
use Addons\TradingManagement\Modules\TradingBot\Events\MarketDataUpdated;
use Addons\TradingManagement\Modules\TradingBot\Events\OrderExecuted;
use Addons\TradingManagement\Modules\TradingBot\Events\BotStatusChanged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * TradingBotBroadcastService
 * 
 * Handles real-time WebSocket broadcasting for trading bot data
 * 
 * Prerequisites to enable real-time updates:
 * 1. Set BROADCAST_DRIVER=pusher (or redis) in .env
 * 2. Configure Pusher/Soketi credentials:
 *    PUSHER_APP_ID=your-app-id
 *    PUSHER_APP_KEY=your-app-key
 *    PUSHER_APP_SECRET=your-app-secret
 *    PUSHER_APP_CLUSTER=mt1
 * 3. For self-hosted: Use Soketi or laravel-echo-server with Redis
 * 4. Install Laravel Echo on frontend:
 *    npm install laravel-echo pusher-js
 * 
 * Frontend Usage:
 * ```javascript
 * import Echo from 'laravel-echo';
 * import Pusher from 'pusher-js';
 * 
 * window.Pusher = Pusher;
 * window.Echo = new Echo({
 *     broadcaster: 'pusher',
 *     key: process.env.MIX_PUSHER_APP_KEY,
 *     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
 *     encrypted: true
 * });
 * 
 * // Listen for position updates
 * Echo.private(`admin.trading-bot.${botId}`)
 *     .listen('.position.updated', (e) => {
 *         console.log('Positions:', e.positions);
 *         console.log('Stats:', e.stats);
 *     })
 *     .listen('.order.executed', (e) => {
 *         console.log('Order:', e.order, 'Action:', e.action);
 *     })
 *     .listen('.bot.status', (e) => {
 *         console.log('Status:', e.status);
 *     });
 * 
 * // Listen for market data (public channel)
 * Echo.channel(`market.EURUSD`)
 *     .listen('.market.updated', (e) => {
 *         console.log('Bid:', e.bid, 'Ask:', e.ask);
 *     });
 * ```
 */
class TradingBotBroadcastService
{
    /**
     * Check if broadcasting is enabled
     */
    public function isEnabled(): bool
    {
        return config('broadcasting.default') !== 'null';
    }

    /**
     * Broadcast position update
     */
    public function broadcastPositionUpdate(TradingBot $bot, array $positions, array $stats): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            event(new PositionUpdated($bot->id, $bot->user_id, $positions, $stats));
            
            Log::debug('Broadcast: Position updated', [
                'bot_id' => $bot->id,
                'positions_count' => count($positions)
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to broadcast position update', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Broadcast market data update
     */
    public function broadcastMarketData(string $symbol, float $bid, float $ask, float $last, ?int $botId = null, ?int $userId = null): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        // Throttle market data broadcasts (max 1 per symbol per second)
        $cacheKey = "broadcast_market_{$symbol}";
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, 1);

        try {
            event(new MarketDataUpdated($symbol, $bid, $ask, $last, $botId, $userId));
        } catch (\Throwable $e) {
            Log::warning('Failed to broadcast market data', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Broadcast order execution
     */
    public function broadcastOrderExecuted(TradingBot $bot, array $order, string $action): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            event(new OrderExecuted($bot->id, $bot->user_id, $order, $action));
            
            Log::info('Broadcast: Order executed', [
                'bot_id' => $bot->id,
                'action' => $action,
                'order_id' => $order['id'] ?? null
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to broadcast order execution', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Broadcast bot status change
     */
    public function broadcastStatusChange(TradingBot $bot, string $status, ?string $message = null, array $metrics = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            event(new BotStatusChanged($bot->id, $bot->user_id, $status, $message, $metrics));
            
            Log::info('Broadcast: Bot status changed', [
                'bot_id' => $bot->id,
                'status' => $status
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to broadcast status change', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Broadcast all updates for a bot (positions, stats, status)
     */
    public function broadcastAllUpdates(TradingBot $bot): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $monitoringService = app(TradingBotMonitoringService::class);
            
            // Get fresh data
            $positions = $monitoringService->getOpenPositions($bot, true);
            $stats = $monitoringService->calculatePositionStats($bot, true);
            $workerStatus = $monitoringService->getWorkerStatus($bot);
            
            // Broadcast position update
            $this->broadcastPositionUpdate($bot, $positions, $stats);
            
            // Broadcast status if changed
            $status = $workerStatus['is_running'] ? 'running' : 'stopped';
            $this->broadcastStatusChange($bot, $status, null, $workerStatus);
            
        } catch (\Throwable $e) {
            Log::warning('Failed to broadcast all updates', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

