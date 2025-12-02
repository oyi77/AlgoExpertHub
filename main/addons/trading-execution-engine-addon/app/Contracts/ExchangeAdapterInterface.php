<?php

namespace Addons\TradingExecutionEngine\App\Contracts;

interface ExchangeAdapterInterface
{
    /**
     * Test connection to exchange/broker.
     *
     * @param array $credentials
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function testConnection(array $credentials): array;

    /**
     * Place a market order.
     *
     * @param string $symbol
     * @param string $direction (buy/sell)
     * @param float $quantity
     * @param array $options Optional: sl_price, tp_price, etc.
     * @return array ['success' => bool, 'order_id' => string|null, 'price' => float|null, 'message' => string, 'data' => array]
     */
    public function placeMarketOrder(string $symbol, string $direction, float $quantity, array $options = []): array;

    /**
     * Place a limit order.
     *
     * @param string $symbol
     * @param string $direction
     * @param float $quantity
     * @param float $price
     * @param array $options
     * @return array ['success' => bool, 'order_id' => string|null, 'message' => string, 'data' => array]
     */
    public function placeLimitOrder(string $symbol, string $direction, float $quantity, float $price, array $options = []): array;

    /**
     * Get current price for a symbol.
     *
     * @param string $symbol
     * @return float|null
     */
    public function getCurrentPrice(string $symbol): ?float;

    /**
     * Get account balance.
     *
     * @return array ['balance' => float, 'equity' => float, 'free_margin' => float|null]
     */
    public function getBalance(): array;

    /**
     * Get open positions.
     *
     * @return array Array of positions with: order_id, symbol, direction, quantity, entry_price, current_price, pnl
     */
    public function getOpenPositions(): array;

    /**
     * Get a specific position by order_id.
     *
     * @param string $orderId
     * @return array|null
     */
    public function getPosition(string $orderId): ?array;

    /**
     * Close a position.
     *
     * @param string $orderId
     * @return array ['success' => bool, 'close_price' => float|null, 'message' => string]
     */
    public function closePosition(string $orderId): array;

    /**
     * Update stop loss for a position.
     *
     * @param string $orderId
     * @param float $slPrice
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateStopLoss(string $orderId, float $slPrice): array;

    /**
     * Update take profit for a position.
     *
     * @param string $orderId
     * @param float $tpPrice
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateTakeProfit(string $orderId, float $tpPrice): array;

    /**
     * Cancel an order.
     *
     * @param string $orderId
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancelOrder(string $orderId): array;
}

