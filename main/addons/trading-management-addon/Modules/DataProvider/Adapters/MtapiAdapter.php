<?php

namespace Addons\TradingManagement\Modules\DataProvider\Adapters;

use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * mtapi.io Adapter
 * 
 * Implements DataProviderInterface for mtapi.io MT4/MT5 connections
 * 
 * API Documentation: https://docs.mtapi.io/
 * 
 * Credentials Required:
 * - api_key: mtapi.io API key
 * - account_id: MT account ID
 * - base_url: mtapi.io base URL (optional, defaults to config)
 */
class MtapiAdapter implements DataProviderInterface
{
    protected Client $client;
    protected array $credentials;
    protected string $baseUrl;
    protected bool $connected = false;
    protected int $timeout;

    public function __construct(array $credentials = [])
    {
        $this->credentials = $credentials;
        $this->baseUrl = $credentials['base_url'] ?? config('trading-management.mtapi.base_url', 'https://api.mtapi.io');
        $this->timeout = $credentials['timeout'] ?? config('trading-management.mtapi.timeout', 30);
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Connect to mtapi.io
     */
    public function connect(array $credentials): bool
    {
        $this->credentials = array_merge($this->credentials, $credentials);

        if (empty($this->credentials['api_key'])) {
            throw new \Exception('mtapi.io API key is required');
        }

        if (empty($this->credentials['account_id'])) {
            throw new \Exception('MT account ID is required');
        }

        // Test connection by fetching account info
        try {
            $accountInfo = $this->getAccountInfo();
            $this->connected = !empty($accountInfo);
            return $this->connected;
        } catch (\Exception $e) {
            $this->connected = false;
            throw $e;
        }
    }

    /**
     * Disconnect
     */
    public function disconnect(): void
    {
        $this->connected = false;
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Fetch OHLCV data
     * 
     * @param string $symbol Trading pair (e.g., 'EURUSD', 'GBPUSD')
     * @param string $timeframe Timeframe (M1, M5, M15, M30, H1, H4, D1, W1, MN)
     * @param int $limit Number of candles to fetch
     * @param int|null $since Timestamp to fetch from (optional)
     * @return array Array of OHLCV data
     */
    public function fetchOHLCV(string $symbol, string $timeframe, int $limit = 100, ?int $since = null): array
    {
        $endpoint = sprintf('/v1/accounts/%s/history', $this->credentials['account_id']);
        
        $params = [
            'symbol' => $symbol,
            'timeframe' => $this->convertTimeframe($timeframe),
            'limit' => min($limit, 1000), // mtapi.io limit
        ];

        if ($since !== null) {
            $params['from'] = $since;
        }

        try {
            $response = $this->client->get($endpoint, [
                'query' => $params,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception('mtapi.io error: ' . ($data['message'] ?? 'Unknown error'));
            }

            return $this->normalizeOHLCVData($data['data'] ?? []);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $message = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            
            throw new \Exception(sprintf(
                'mtapi.io request failed (HTTP %d): %s',
                $statusCode,
                $message
            ));
        }
    }

    /**
     * Fetch tick data (not supported by mtapi.io - returns empty)
     */
    public function fetchTicks(string $symbol, int $limit = 100): array
    {
        // mtapi.io doesn't provide tick-by-tick data
        // Return empty array or throw exception
        return [];
    }

    /**
     * Get account information
     */
    public function getAccountInfo(): array
    {
        $endpoint = sprintf('/v1/accounts/%s', $this->credentials['account_id']);

        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception('mtapi.io error: ' . ($data['message'] ?? 'Unknown error'));
            }

            return [
                'balance' => $data['balance'] ?? 0,
                'equity' => $data['equity'] ?? 0,
                'margin' => $data['margin'] ?? 0,
                'free_margin' => $data['free_margin'] ?? 0,
                'margin_level' => $data['margin_level'] ?? 0,
                'currency' => $data['currency'] ?? 'USD',
                'leverage' => $data['leverage'] ?? 100,
                'server' => $data['server'] ?? 'Unknown',
            ];
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch account info: ' . $e->getMessage());
        }
    }

    /**
     * Get available symbols
     */
    public function getAvailableSymbols(): array
    {
        $endpoint = sprintf('/v1/accounts/%s/symbols', $this->credentials['account_id']);

        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['symbols'] ?? [];
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch symbols from mtapi.io', [
                'error' => $e->getMessage(),
            ]);
            // Return common FX pairs as fallback
            return ['EURUSD', 'GBPUSD', 'USDJPY', 'USDCHF', 'AUDUSD', 'USDCAD', 'NZDUSD'];
        }
    }

    /**
     * Test connection
     */
    public function testConnection(): array
    {
        $start = microtime(true);
        
        try {
            $accountInfo = $this->getAccountInfo();
            $latency = round((microtime(true) - $start) * 1000, 2); // ms

            return [
                'success' => true,
                'message' => sprintf(
                    'Connected successfully. Balance: %.2f %s, Equity: %.2f',
                    $accountInfo['balance'],
                    $accountInfo['currency'],
                    $accountInfo['equity']
                ),
                'latency' => $latency,
                'account_info' => $accountInfo,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'latency' => round((microtime(true) - $start) * 1000, 2),
            ];
        }
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'mtapi';
    }

    /**
     * Convert standard timeframe to mtapi.io format
     * 
     * @param string $timeframe Standard format (M1, M5, H1, H4, D1, etc.)
     * @return int mtapi.io timeframe code
     */
    protected function convertTimeframe(string $timeframe): int
    {
        $mapping = [
            'M1' => 1,
            'M5' => 5,
            'M15' => 15,
            'M30' => 30,
            'H1' => 60,
            'H4' => 240,
            'D1' => 1440,
            'W1' => 10080,
            'MN' => 43200,
        ];

        return $mapping[$timeframe] ?? 60; // Default to H1
    }

    /**
     * Normalize OHLCV data to standard format
     * 
     * @param array $data Raw data from mtapi.io
     * @return array Normalized data [[timestamp, open, high, low, close, volume], ...]
     */
    protected function normalizeOHLCVData(array $data): array
    {
        $normalized = [];

        foreach ($data as $candle) {
            $normalized[] = [
                'timestamp' => $candle['time'] ?? $candle['timestamp'] ?? 0,
                'open' => (float) ($candle['open'] ?? 0),
                'high' => (float) ($candle['high'] ?? 0),
                'low' => (float) ($candle['low'] ?? 0),
                'close' => (float) ($candle['close'] ?? 0),
                'volume' => (float) ($candle['tick_volume'] ?? $candle['volume'] ?? 0),
            ];
        }

        return $normalized;
    }
}

