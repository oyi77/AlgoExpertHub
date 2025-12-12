<?php

namespace Addons\TradingManagement\Modules\DataProvider\Adapters;

use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;
use Addons\TradingManagement\Shared\Contracts\ExchangeAdapterInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * mtapi.io Adapter
 * 
 * Implements DataProviderInterface and ExchangeAdapterInterface for mtapi.io MT4/MT5 connections
 * 
 * API Documentation: https://docs.mtapi.io/
 * 
 * Credentials Required:
 * - api_key: mtapi.io API key
 * - account_id: MT account ID
 * - base_url: mtapi.io base URL (optional, defaults to config)
 */
class MtapiAdapter implements DataProviderInterface, ExchangeAdapterInterface
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
     * Get account ID from credentials with validation
     * 
     * @return string
     * @throws \Exception if account_id is missing
     */
    protected function getAccountId(): string
    {
        $accountId = $this->credentials['account_id'] ?? null;
        if (empty($accountId)) {
            throw new \Exception('MT account ID is required');
        }
        return $accountId;
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

        // Validate account_id exists (will throw exception if missing)
        $this->getAccountId();

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
        $endpoint = sprintf('/v1/accounts/%s/history', $this->getAccountId());
        
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
        $endpoint = sprintf('/v1/accounts/%s', $this->getAccountId());

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
        $endpoint = sprintf('/v1/accounts/%s/symbols', $this->getAccountId());

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

    // ExchangeAdapterInterface Implementation

    public function createMarketOrder(string $symbol, string $side, float $amount, array $params = []): array
    {
        $endpoint = sprintf('/v1/accounts/%s/orders', $this->getAccountId());
        
        // Map side to MT4/MT5 operation type
        // This is simplified, actual constant values depend on broker/API
        $type = strtolower($side) === 'buy' ? 0 : 1; // 0=Buy, 1=Sell usually

        $payload = [
            'symbol' => $symbol,
            'operation' => $type,
            'volume' => $amount,
            // 'price' => 0, // Market order
            'slippage' => $params['slippage'] ?? 10,
            'stoploss' => $params['stopLoss'] ?? 0,
            'takeprofit' => $params['takeProfit'] ?? 0,
            'comment' => $params['comment'] ?? 'AutoTrade',
        ];

        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create market order: ' . $e->getMessage());
        }
    }

    public function createLimitOrder(string $symbol, string $side, float $amount, float $price, array $params = []): array
    {
        $endpoint = sprintf('/v1/accounts/%s/orders', $this->getAccountId());
        
        $type = strtolower($side) === 'buy' ? 2 : 3; // 2=Buy Limit, 3=Sell Limit (Example)

        $payload = [
            'symbol' => $symbol,
            'operation' => $type,
            'volume' => $amount,
            'price' => $price,
            'slippage' => $params['slippage'] ?? 10,
            'stoploss' => $params['stopLoss'] ?? 0,
            'takeprofit' => $params['takeProfit'] ?? 0,
        ];

        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create limit order: ' . $e->getMessage());
        }
    }

    public function cancelOrder(string $orderId, string $symbol): array
    {
        // Cancel pending order
        $endpoint = sprintf('/v1/accounts/%s/orders/%s', $this->getAccountId(), $orderId);
        
        try {
            $response = $this->client->delete($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new \Exception('Failed to cancel order: ' . $e->getMessage());
        }
    }

    public function getOrder(string $orderId, string $symbol): array
    {
        $endpoint = sprintf('/v1/accounts/%s/orders/%s', $this->getAccountId(), $orderId);
        
        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new \Exception('Failed to get order: ' . $e->getMessage());
        }
    }

    public function getOpenPositions(?string $symbol = null): array
    {
        $endpoint = sprintf('/v1/accounts/%s/positions', $this->getAccountId());
        
        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);
            $positions = json_decode($response->getBody()->getContents(), true);
            
            // Filter by symbol if needed
            if ($symbol) {
                return array_filter($positions, fn($p) => ($p['symbol'] ?? '') == $symbol);
            }
            return $positions;
        } catch (\Exception $e) {
            throw new \Exception('Failed to get positions: ' . $e->getMessage());
        }
    }

    public function closePosition(string $positionId, string $symbol, ?float $amount = null): array
    {
        // Close position
        $endpoint = sprintf('/v1/accounts/%s/positions/%s/close', $this->getAccountId(), $positionId);
        
        $payload = [];
        if ($amount) {
            $payload['volume'] = $amount;
        }

        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new \Exception('Failed to close position: ' . $e->getMessage());
        }
    }

    public function getBalance(): array
    {
        return $this->getAccountInfo();
    }

    public function modifyPosition(string $positionId, string $symbol, ?float $stopLoss = null, ?float $takeProfit = null): array
    {
        $endpoint = sprintf('/v1/accounts/%s/positions/%s', $this->getAccountId(), $positionId);
        
        $payload = [];
        if ($stopLoss !== null) $payload['stoploss'] = $stopLoss;
        if ($takeProfit !== null) $payload['takeprofit'] = $takeProfit;

        try {
            $response = $this->client->patch($endpoint, [ // PATCH for update
                'json' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
             throw new \Exception('Failed to modify position: ' . $e->getMessage());
        }
    }

    public function getCurrentPrice(string $symbol): array
    {
        // fetchOHLCV for 1 min candle isn't same as tick, but mtapi might have /prices endpoint
        $endpoint = sprintf('/v1/accounts/%s/symbols/%s/price', $this->getAccountId(), $symbol);
        
        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentials['api_key'],
                ],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            // Fallback: fetchOHLCV
             try {
                $candles = $this->fetchOHLCV($symbol, 'M1', 1);
                if (!empty($candles)) {
                    $last = end($candles);
                    return [
                        'bid' => $last['close'],
                        'ask' => $last['close'], // Approx
                        'last' => $last['close'],
                        'timestamp' => $last['timestamp'],
                    ];
                }
             } catch (\Exception $ex) {}
             
             throw new \Exception('Failed to get current price: ' . $e->getMessage());
        }
    }

    public function getExchangeName(): string
    {
        return 'mtapi';
    }
}

