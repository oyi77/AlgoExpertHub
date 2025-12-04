<?php

namespace Addons\TradingManagement\Shared\Contracts;

/**
 * Interface for exchange adapters (CCXT, mtapi.io for execution)
 * 
 * All exchange adapters must implement this interface to ensure
 * consistent trade execution across different exchanges/brokers.
 */
interface ExchangeAdapterInterface
{
    /**
     * Connect to the exchange
     * 
     * @param array $credentials Exchange-specific credentials
     * @return bool True if connection successful
     * @throws \Exception If connection fails
     */
    public function connect(array $credentials): bool;

    /**
     * Create a market order
     * 
     * @param string $symbol Trading pair (e.g., 'BTC/USDT', 'EURUSD')
     * @param string $side 'buy' or 'sell'
     * @param float $amount Order size (lots or quantity)
     * @param array $params Additional parameters (stop_loss, take_profit, etc.)
     * @return array Order response [order_id, status, filled, price, etc.]
     * @throws \Exception If order fails
     */
    public function createMarketOrder(string $symbol, string $side, float $amount, array $params = []): array;

    /**
     * Create a limit order
     * 
     * @param string $symbol Trading pair
     * @param string $side 'buy' or 'sell'
     * @param float $amount Order size
     * @param float $price Limit price
     * @param array $params Additional parameters
     * @return array Order response
     * @throws \Exception If order fails
     */
    public function createLimitOrder(string $symbol, string $side, float $amount, float $price, array $params = []): array;

    /**
     * Cancel an order
     * 
     * @param string $orderId Order ID to cancel
     * @param string $symbol Trading pair
     * @return array Cancellation response
     * @throws \Exception If cancellation fails
     */
    public function cancelOrder(string $orderId, string $symbol): array;

    /**
     * Get order status
     * 
     * @param string $orderId Order ID
     * @param string $symbol Trading pair
     * @return array Order details [status, filled, remaining, price, etc.]
     * @throws \Exception If fetch fails
     */
    public function getOrder(string $orderId, string $symbol): array;

    /**
     * Get open positions
     * 
     * @param string|null $symbol Filter by symbol (optional)
     * @return array Array of open positions
     * @throws \Exception If fetch fails
     */
    public function getOpenPositions(?string $symbol = null): array;

    /**
     * Close a position
     * 
     * @param string $positionId Position ID or order ID
     * @param string $symbol Trading pair
     * @param float|null $amount Amount to close (null = close all)
     * @return array Close response
     * @throws \Exception If close fails
     */
    public function closePosition(string $positionId, string $symbol, ?float $amount = null): array;

    /**
     * Get account balance
     * 
     * @return array Balance info [total, free, used, currencies, etc.]
     * @throws \Exception If fetch fails
     */
    public function getBalance(): array;

    /**
     * Modify position stop loss and take profit
     * 
     * @param string $positionId Position ID
     * @param string $symbol Trading pair
     * @param float|null $stopLoss New stop loss price (null = no change)
     * @param float|null $takeProfit New take profit price (null = no change)
     * @return array Modification response
     * @throws \Exception If modification fails
     */
    public function modifyPosition(string $positionId, string $symbol, ?float $stopLoss = null, ?float $takeProfit = null): array;

    /**
     * Get current price for a symbol
     * 
     * @param string $symbol Trading pair
     * @return array Price data [bid, ask, last, timestamp]
     * @throws \Exception If fetch fails
     */
    public function getCurrentPrice(string $symbol): array;

    /**
     * Test connection
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array;

    /**
     * Get exchange name
     * 
     * @return string Exchange identifier (e.g., 'binance', 'mt4_account_123')
     */
    public function getExchangeName(): string;
}

