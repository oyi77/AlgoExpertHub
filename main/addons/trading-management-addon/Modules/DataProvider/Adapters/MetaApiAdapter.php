<?php

namespace Addons\TradingManagement\Modules\DataProvider\Adapters;

use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * MetaApi.cloud Adapter
 * 
 * Implements DataProviderInterface for MetaApi.cloud MT4/MT5 connections
 * 
 * API Documentation:
 * - Client API: https://mt-client-api-v1.london.agiliumtrade.ai/api-docs.json
 * - Market Data API: https://mt-market-data-client-api-v1.london.agiliumtrade.ai/api-docs.json
 * - Provisioning API: https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai/api-docs.json
 * - Billing API: https://billing-api-v1.agiliumtrade.agiliumtrade.ai/api-docs.json
 * - MetaStats API: https://metastats-api-v1.london.agiliumtrade.ai/api-docs.json
 * 
 * Credentials Required:
 * - api_token: MetaApi auth token (from web app)
 * - account_id: MetaApi account ID (from web app)
 * - base_url: Main API base URL (optional, defaults to config)
 * - market_data_base_url: Market Data API base URL (optional, defaults to config)
 * 
 * Endpoints Used:
 * - GET /users/current/accounts/{accountId}/account-information - Account info
 * - GET /users/current/accounts/{accountId}/symbols - Available symbols
 * - GET /users/current/accounts/{accountId}/historical-market-data/symbols/{symbol}/timeframes/{timeframe}/candles - Historical candles
 * - GET /users/current/accounts/{accountId}/historical-market-data/symbols/{symbol}/ticks - Historical ticks (MT5 only)
 */
class MetaApiAdapter implements DataProviderInterface
{
    protected Client $client;
    protected Client $marketDataClient;
    protected array $credentials;
    protected string $baseUrl;
    protected string $marketDataBaseUrl;
    protected bool $connected = false;
    protected int $timeout;

    public function __construct(array $credentials = [])
    {
        $this->credentials = $credentials;
        
        // Get base URLs: credentials -> config -> global settings -> default
        $this->baseUrl = $credentials['base_url'] 
            ?? config('trading-management.metaapi.base_url')
            ?? $this->getBaseUrlFromGlobalSettings();
        
        $this->marketDataBaseUrl = $credentials['market_data_base_url'] 
            ?? config('trading-management.metaapi.market_data_base_url')
            ?? $this->getMarketDataBaseUrlFromGlobalSettings();
        
        $this->timeout = $credentials['timeout'] 
            ?? config('trading-management.metaapi.timeout', 30);
        
        // Auto-fill API token from global settings if not provided in credentials
        if (empty($this->credentials['api_token'])) {
            $this->credentials['api_token'] = config('trading-management.metaapi.api_token')
                ?? $this->getTokenFromGlobalSettings();
        }
        
        // Main API client
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        // Market Data API client
        $this->marketDataClient = new Client([
            'base_uri' => $this->marketDataBaseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Connect to MetaApi.cloud
     */
    public function connect(array $credentials): bool
    {
        $this->credentials = array_merge($this->credentials, $credentials);

        if (empty($this->credentials['api_token'])) {
            throw new \Exception('MetaApi API token is required');
        }

        if (empty($this->credentials['account_id'])) {
            throw new \Exception('MetaApi account ID is required');
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
     * Uses GET /users/current/accounts/{accountId}/historical-market-data/symbols/{symbol}/timeframes/{timeframe}/candles
     * from Market Data API
     * 
     * @param string $symbol Trading pair (e.g., 'EURUSD', 'GBPUSD')
     * @param string $timeframe Timeframe (M1, M5, M15, M30, H1, H4, D1, W1, MN)
     * @param int $limit Number of candles to fetch (max 1000)
     * @param int|null $since Timestamp to fetch from (optional, candles loaded backwards from startTime)
     * @return array Array of OHLCV data
     */
    public function fetchOHLCV(string $symbol, string $timeframe, int $limit = 100, ?int $since = null): array
    {
        $accountId = $this->credentials['account_id'];
        $metaApiTimeframe = $this->convertTimeframe($timeframe);
        
        $endpoint = sprintf(
            '/users/current/accounts/%s/historical-market-data/symbols/%s/timeframes/%s/candles',
            $accountId,
            $symbol,
            $metaApiTimeframe
        );
        
        $params = [
            'limit' => min($limit, 1000), // MetaApi limit
        ];

        if ($since !== null) {
            // Convert timestamp to ISO 8601 format
            // Note: candles are loaded in backwards direction, so startTime should be the latest time
            $params['startTime'] = date('c', $since);
        }

        try {
            $response = $this->marketDataClient->get($endpoint, [
                'query' => $params,
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                ],
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 401) {
                    throw new \Exception('MetaApi authentication failed. Please check your API token.');
                } elseif ($statusCode === 403) {
                    throw new \Exception('MetaApi access forbidden. Your token may not have permission to access historical market data.');
                } elseif ($statusCode === 404) {
                    throw new \Exception('MetaApi account not found or symbol not defined for this broker.');
                }
                
                throw new \Exception(sprintf(
                    'MetaApi request failed (HTTP %d): %s',
                    $statusCode,
                    $errorMessage
                ));
            }

            $data = json_decode($response->getBody()->getContents(), true);

            if (!is_array($data)) {
                throw new \Exception('Invalid response format from MetaApi');
            }

            return $this->normalizeOHLCVData($data);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $message = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            
            if ($statusCode === 401) {
                throw new \Exception('MetaApi authentication failed. Please check your API token.');
            } elseif ($statusCode === 404) {
                throw new \Exception('MetaApi account not found. Please check your account ID.');
            }
            
            throw new \Exception(sprintf(
                'MetaApi request failed (HTTP %d): %s',
                $statusCode,
                $message
            ));
        }
    }

    /**
     * Fetch tick data (MT5 only)
     * 
     * Uses GET /users/current/accounts/{accountId}/historical-market-data/symbols/{symbol}/ticks
     * from Market Data API
     * Note: This API is not supported by MT4 accounts
     */
    public function fetchTicks(string $symbol, int $limit = 100, ?int $since = null, int $offset = 0): array
    {
        $accountId = $this->credentials['account_id'];
        
        $endpoint = sprintf(
            '/users/current/accounts/%s/historical-market-data/symbols/%s/ticks',
            $accountId,
            $symbol
        );
        
        $params = [
            'limit' => min($limit, 1000), // MetaApi limit
            'offset' => $offset,
        ];

        if ($since !== null) {
            // Convert timestamp to ISO 8601 format
            // Note: ticks are loaded in forward direction, so startTime should be the earliest time
            $params['startTime'] = date('c', $since);
        }

        try {
            $response = $this->marketDataClient->get($endpoint, [
                'query' => $params,
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                ],
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 401) {
                    throw new \Exception('MetaApi authentication failed. Please check your API token.');
                } elseif ($statusCode === 403) {
                    throw new \Exception('MetaApi access forbidden. Your token may not have permission to access historical ticks.');
                } elseif ($statusCode === 404) {
                    throw new \Exception('MetaApi account not found or symbol not defined. Note: Historical ticks API is not supported by MT4 accounts.');
                }
                
                throw new \Exception(sprintf(
                    'MetaApi request failed (HTTP %d): %s',
                    $statusCode,
                    $errorMessage
                ));
            }

            $data = json_decode($response->getBody()->getContents(), true);

            if (!is_array($data)) {
                throw new \Exception('Invalid response format from MetaApi');
            }

            return $this->normalizeTickData($data);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $message = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            
            if ($statusCode === 401) {
                throw new \Exception('MetaApi authentication failed. Please check your API token.');
            } elseif ($statusCode === 404) {
                throw new \Exception('MetaApi account not found or historical ticks not supported (MT4 accounts do not support this API).');
            }
            
            throw new \Exception(sprintf(
                'MetaApi request failed (HTTP %d): %s',
                $statusCode,
                $message
            ));
        }
    }

    /**
     * Get account information
     * 
     * Uses GET /users/current/accounts/{accountId}/account-information endpoint
     * Returns MetatraderAccountInformation with balance, equity, margin, etc.
     */
    public function getAccountInfo(): array
    {
        $accountId = $this->credentials['account_id'];
        $endpoint = sprintf('/users/current/accounts/%s/account-information', $accountId);

        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                ],
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 401) {
                    throw new \Exception('MetaApi authentication failed. Please check your API token.');
                } elseif ($statusCode === 404) {
                    throw new \Exception('MetaApi account not found. Please check your account ID.');
                }
                
                throw new \Exception('MetaApi error: ' . $errorMessage);
            }

            $data = json_decode($response->getBody()->getContents(), true);

            // Map MetatraderAccountInformation fields to our format
            return [
                'balance' => (float) ($data['balance'] ?? 0),
                'equity' => (float) ($data['equity'] ?? 0),
                'margin' => (float) ($data['margin'] ?? 0),
                'free_margin' => (float) ($data['freeMargin'] ?? 0),
                'margin_level' => isset($data['marginLevel']) ? (float) $data['marginLevel'] : null,
                'currency' => $data['currency'] ?? 'USD',
                'leverage' => (int) ($data['leverage'] ?? 100),
                'server' => $data['server'] ?? $data['broker'] ?? 'Unknown',
                'broker' => $data['broker'] ?? null,
                'platform' => $data['platform'] ?? null, // mt4 or mt5
                'trade_allowed' => $data['tradeAllowed'] ?? false,
                'investor_mode' => $data['investorMode'] ?? false,
                'margin_mode' => $data['marginMode'] ?? null,
                'name' => $data['name'] ?? null,
                'login' => $data['login'] ?? null,
                'credit' => (float) ($data['credit'] ?? 0),
                'type' => $data['type'] ?? null, // ACCOUNT_TRADE_MODE_DEMO, ACCOUNT_TRADE_MODE_CONTEST, ACCOUNT_TRADE_MODE_REAL
            ];
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            
            if ($statusCode === 401) {
                throw new \Exception('MetaApi authentication failed. Please check your API token.');
            } elseif ($statusCode === 404) {
                throw new \Exception('MetaApi account not found. Please check your account ID.');
            }
            
            throw new \Exception('Failed to fetch account info: ' . $e->getMessage());
        }
    }

    /**
     * Get available symbols
     * 
     * Uses GET /users/current/accounts/{accountId}/symbols endpoint
     * Returns array of symbol strings available on the trading account
     */
    public function getAvailableSymbols(): array
    {
        $accountId = $this->credentials['account_id'];
        $endpoint = sprintf('/users/current/accounts/%s/symbols', $accountId);

        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                ],
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 401) {
                    throw new \Exception('MetaApi authentication failed. Please check your API token.');
                } elseif ($statusCode === 404) {
                    throw new \Exception('MetaApi account not found. Please check your account ID.');
                }
                
                throw new \Exception('MetaApi error: ' . $errorMessage);
            }

            $data = json_decode($response->getBody()->getContents(), true);

            // MetaApi returns array of symbol strings directly
            if (is_array($data)) {
                return $data;
            }

            return [];
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch symbols from MetaApi', [
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
        return 'metaapi';
    }

    /**
     * Convert standard timeframe to MetaApi format
     * 
     * @param string $timeframe Standard format (M1, M5, H1, H4, D1, etc.)
     * @return string MetaApi timeframe format (1m, 5m, 1h, 4h, 1d, etc.)
     */
    protected function convertTimeframe(string $timeframe): string
    {
        // Map to MT5 format (more flexible, supports more timeframes)
        $mapping = [
            'M1' => '1m',
            'M2' => '2m',
            'M3' => '3m',
            'M4' => '4m',
            'M5' => '5m',
            'M6' => '6m',
            'M10' => '10m',
            'M12' => '12m',
            'M15' => '15m',
            'M20' => '20m',
            'M30' => '30m',
            'H1' => '1h',
            'H2' => '2h',
            'H3' => '3h',
            'H4' => '4h',
            'H6' => '6h',
            'H8' => '8h',
            'H12' => '12h',
            'D1' => '1d',
            'W1' => '1w',
            'MN' => '1mn',
        ];

        return $mapping[$timeframe] ?? '1h'; // Default to 1h
    }

    /**
     * Normalize OHLCV data to standard format
     * 
     * Maps MetatraderCandle fields to our standard format
     * 
     * @param array $data Raw data from MetaApi (array of MetatraderCandle)
     * @return array Normalized data [[timestamp, open, high, low, close, volume], ...]
     */
    protected function normalizeOHLCVData(array $data): array
    {
        $normalized = [];

        foreach ($data as $candle) {
            // MetaApi returns ISO 8601 datetime strings, convert to timestamp
            $timestamp = 0;
            if (isset($candle['time'])) {
                $timestamp = is_string($candle['time']) 
                    ? strtotime($candle['time']) 
                    : $candle['time'];
            }

            $normalized[] = [
                'timestamp' => $timestamp,
                'open' => (float) ($candle['open'] ?? 0),
                'high' => (float) ($candle['high'] ?? 0),
                'low' => (float) ($candle['low'] ?? 0),
                'close' => (float) ($candle['close'] ?? 0),
                'volume' => (int) ($candle['volume'] ?? $candle['tickVolume'] ?? 0),
                'tick_volume' => (int) ($candle['tickVolume'] ?? 0),
                'spread' => (int) ($candle['spread'] ?? 0),
                'symbol' => $candle['symbol'] ?? null,
                'timeframe' => $candle['timeframe'] ?? null,
                'broker_time' => $candle['brokerTime'] ?? null,
            ];
        }

        return $normalized;
    }

    /**
     * Normalize tick data to standard format
     * 
     * Maps MetatraderTick fields to our standard format
     * 
     * @param array $data Raw data from MetaApi (array of MetatraderTick)
     * @return array Normalized tick data
     */
    protected function normalizeTickData(array $data): array
    {
        $normalized = [];

        foreach ($data as $tick) {
            // MetaApi returns ISO 8601 datetime strings, convert to timestamp
            $timestamp = 0;
            if (isset($tick['time'])) {
                $timestamp = is_string($tick['time']) 
                    ? strtotime($tick['time']) 
                    : $tick['time'];
            }

            $normalized[] = [
                'timestamp' => $timestamp,
                'symbol' => $tick['symbol'] ?? null,
                'bid' => isset($tick['bid']) ? (float) $tick['bid'] : null,
                'ask' => isset($tick['ask']) ? (float) $tick['ask'] : null,
                'last' => isset($tick['last']) ? (float) $tick['last'] : null,
                'volume' => isset($tick['volume']) ? (float) $tick['volume'] : null,
                'side' => $tick['side'] ?? null, // 'buy' or 'sell'
                'broker_time' => $tick['brokerTime'] ?? null,
            ];
        }

        return $normalized;
    }

    protected function getTokenFromGlobalSettings(): ?string
    {
        try {
            $globalConfig = \App\Services\GlobalConfigurationService::get('metaapi_global_settings', []);
            if (!empty($globalConfig['api_token'])) {
                try {
                    return \Illuminate\Support\Facades\Crypt::decryptString($globalConfig['api_token']);
                } catch (\Exception $e) {
                    return $globalConfig['api_token'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    protected function getBaseUrlFromGlobalSettings(): string
    {
        try {
            $globalConfig = \App\Services\GlobalConfigurationService::get('metaapi_global_settings', []);
            return $globalConfig['base_url'] ?? 'https://mt-client-api-v1.london.agiliumtrade.ai';
        } catch (\Exception $e) {
            return 'https://mt-client-api-v1.london.agiliumtrade.ai';
        }
    }

    protected function getMarketDataBaseUrlFromGlobalSettings(): string
    {
        try {
            $globalConfig = \App\Services\GlobalConfigurationService::get('metaapi_global_settings', []);
            return $globalConfig['market_data_base_url'] ?? 'https://mt-market-data-client-api-v1.london.agiliumtrade.ai';
        } catch (\Exception $e) {
            return 'https://mt-market-data-client-api-v1.london.agiliumtrade.ai';
        }
    }
