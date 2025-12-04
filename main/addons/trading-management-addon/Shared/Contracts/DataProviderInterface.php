<?php

namespace Addons\TradingManagement\Shared\Contracts;

/**
 * Interface for data providers (mtapi.io, CCXT, etc.)
 * 
 * All data providers must implement this interface to ensure
 * consistent data fetching across different sources.
 */
interface DataProviderInterface
{
    /**
     * Connect to the data provider
     * 
     * @param array $credentials Provider-specific credentials
     * @return bool True if connection successful
     * @throws \Exception If connection fails
     */
    public function connect(array $credentials): bool;

    /**
     * Disconnect from the data provider
     * 
     * @return void
     */
    public function disconnect(): void;

    /**
     * Check if currently connected
     * 
     * @return bool True if connected
     */
    public function isConnected(): bool;

    /**
     * Fetch OHLCV (candlestick) data
     * 
     * @param string $symbol Trading pair (e.g., 'EURUSD', 'BTC/USDT')
     * @param string $timeframe Timeframe (M1, M5, M15, M30, H1, H4, D1, W1, MN)
     * @param int $limit Number of candles to fetch
     * @param int|null $since Timestamp to fetch from (optional)
     * @return array Array of OHLCV data [timestamp, open, high, low, close, volume]
     * @throws \Exception If fetch fails
     */
    public function fetchOHLCV(string $symbol, string $timeframe, int $limit = 100, ?int $since = null): array;

    /**
     * Fetch tick data (optional - for high-frequency strategies)
     * 
     * @param string $symbol Trading pair
     * @param int $limit Number of ticks to fetch
     * @return array Array of tick data [timestamp, bid, ask, volume]
     * @throws \Exception If fetch fails or not supported
     */
    public function fetchTicks(string $symbol, int $limit = 100): array;

    /**
     * Get account information
     * 
     * @return array Account data [balance, equity, margin, free_margin, etc.]
     * @throws \Exception If fetch fails
     */
    public function getAccountInfo(): array;

    /**
     * Get available symbols for this provider
     * 
     * @return array List of available trading pairs
     * @throws \Exception If fetch fails
     */
    public function getAvailableSymbols(): array;

    /**
     * Test connection and credentials
     * 
     * @return array ['success' => bool, 'message' => string, 'latency' => float]
     */
    public function testConnection(): array;

    /**
     * Get provider name (e.g., 'mtapi', 'ccxt_binance')
     * 
     * @return string Provider identifier
     */
    public function getProviderName(): string;
}

