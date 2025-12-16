<?php

namespace Addons\TradingManagement\Modules\DataProvider\Adapters;

use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

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
    protected ?object $sdkAccountApi = null;
    protected bool $useSdk = false;
    protected ?object $sdkService = null;
    
    // Latency monitoring
    protected array $latencies = [
        'trade' => [],
        'request' => [],
        'update' => [],
        'quote' => [],
    ];
    protected int $maxLatencyRecords = 100;

    public function __construct(array $credentials = [])
    {
        try {
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
            
            // Prefer account token if available (more secure, scoped to account)
            // Fallback to main API token: credentials -> config -> global settings
            if (empty($this->credentials['api_token'])) {
                // First check for account-specific token (if generated via Profile API)
                $this->credentials['api_token'] = $this->credentials['account_token']
                    ?? config('trading-management.metaapi.api_token')
                    ?? $this->getTokenFromGlobalSettings();
            }
            
            // Check memory before creating clients
            $memoryBefore = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            $memoryLimitBytes = $this->convertToBytes($memoryLimit);
            
            // If memory usage is already high, use lazy client creation
            if ($memoryBefore > ($memoryLimitBytes * 0.7)) {
                Log::warning('Memory usage high, using lazy client creation', [
                    'memory_usage' => $memoryBefore,
                    'memory_limit' => $memoryLimitBytes,
                ]);
                // Clients will be created on first use
                return;
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
            
            // Try to initialize SDK if available (but don't fail if it doesn't work)
            try {
                $this->initializeSdk();
            } catch (\Throwable $sdkEx) {
                Log::debug('SDK initialization failed (non-critical)', [
                    'error' => $sdkEx->getMessage(),
                ]);
                // Continue without SDK
            }
        } catch (\Throwable $e) {
            Log::error('MetaApiAdapter constructor error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Set defaults to prevent fatal errors
            $this->baseUrl = 'https://mt-client-api-v1.london.agiliumtrade.ai';
            $this->marketDataBaseUrl = 'https://mt-market-data-client-api-v1.london.agiliumtrade.ai';
            $this->timeout = 30;
        }
    }
    
    /**
     * Convert memory limit string to bytes
     */
    protected function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        if (empty($memoryLimit)) {
            return 128 * 1024 * 1024; // Default 128MB
        }
        
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Lazy client creation - create clients on first use if not already created
     */
    protected function ensureClientsCreated(): void
    {
        if (!isset($this->client)) {
            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => $this->timeout,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
        
        if (!isset($this->marketDataClient)) {
            $this->marketDataClient = new Client([
                'base_uri' => $this->marketDataBaseUrl,
                'timeout' => $this->timeout,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
    }
    
    /**
     * Initialize MetaAPI SDK if available
     * 
     * @return void
     */
    protected function initializeSdk(): void
    {
        try {
            // Check if SDK is available via composer
            if (class_exists('\Oyi77\MetaapiCloudPhpSdk\AccountApi')) {
                $apiToken = $this->credentials['api_token'] 
                    ?? config('trading-management.metaapi.api_token')
                    ?? $this->getTokenFromGlobalSettings();
                
                if (!empty($apiToken)) {
                    $this->sdkAccountApi = new \Oyi77\MetaapiCloudPhpSdk\AccountApi($apiToken);
                    $this->useSdk = true;
                    
                    Log::debug('MetaApiAdapter: SDK initialized successfully', [
                        'use_sdk' => true,
                    ]);
                }
            }
            
            // Try to initialize enhanced SDK service if available
            if (class_exists('\Addons\TradingManagement\Modules\DataProvider\Services\MetaApiSdkService')) {
                $apiToken = $this->credentials['api_token'] 
                    ?? config('trading-management.metaapi.api_token')
                    ?? $this->getTokenFromGlobalSettings();
                
                $accountId = $this->credentials['account_id'] ?? null;
                $region = $this->credentials['region'] ?? null;
                
                if (!empty($apiToken) && !empty($accountId)) {
                    $this->sdkService = new \Addons\TradingManagement\Modules\DataProvider\Services\MetaApiSdkService(
                        $apiToken,
                        $accountId,
                        $region
                    );
                    
                    Log::debug('MetaApiAdapter: Enhanced SDK service initialized', [
                        'account_id' => $accountId,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // SDK not available or failed to initialize - use fallback
            Log::debug('MetaApiAdapter: SDK not available, using fallback implementation', [
                'error' => $e->getMessage(),
                'use_sdk' => false,
            ]);
            $this->useSdk = false;
        }
    }
    
    /**
     * Record latency for monitoring
     */
    protected function recordLatency(string $type, float $latencySeconds): void
    {
        $latencyMs = $latencySeconds * 1000;
        
        if (!isset($this->latencies[$type])) {
            $this->latencies[$type] = [];
        }
        
        $this->latencies[$type][] = $latencyMs;
        
        // Keep only last N records
        if (count($this->latencies[$type]) > $this->maxLatencyRecords) {
            array_shift($this->latencies[$type]);
        }
    }
    
    /**
     * Get latency statistics
     */
    public function getLatencyStats(string $type): ?array
    {
        if (empty($this->latencies[$type])) {
            return null;
        }
        
        $latencies = $this->latencies[$type];
        $count = count($latencies);
        sort($latencies);
        $middle = floor($count / 2);
        $median = ($count % 2 == 0) 
            ? ($latencies[$middle - 1] + $latencies[$middle]) / 2 
            : $latencies[$middle];
        
        return [
            'count' => $count,
            'min' => min($latencies),
            'max' => max($latencies),
            'avg' => array_sum($latencies) / $count,
            'median' => $median,
        ];
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
            throw new \Exception('MetaApi account ID is required');
        }
        return $accountId;
    }

    /**
     * Connect to MetaApi.cloud
     */
    public function connect(array $credentials): bool
    {
        $this->credentials = array_merge($this->credentials, $credentials);

        // Ensure API token is set - try multiple sources
        if (empty($this->credentials['api_token'])) {
            // Try config first
            $this->credentials['api_token'] = config('trading-management.metaapi.api_token');
            
            // Then try global settings
            if (empty($this->credentials['api_token'])) {
                $this->credentials['api_token'] = $this->getTokenFromGlobalSettings();
            }
        }

        if (empty($this->credentials['api_token'])) {
            throw new \Exception('MetaApi API token is required. Please configure it in Global Settings or .env file (METAAPI_TOKEN)');
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
        // Try SDK first if available
        if ($this->useSdk && $this->sdkAccountApi) {
            try {
                return $this->fetchOHLCVUsingSdk($symbol, $timeframe, $limit, $since);
            } catch (\Exception $e) {
                Log::warning('MetaApiAdapter: SDK fetch failed, falling back to direct API', [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'error' => $e->getMessage(),
                ]);
                // Fall through to fallback implementation
            }
        }
        
        // Fallback to direct API implementation
        return $this->fetchOHLCVDirect($symbol, $timeframe, $limit, $since);
    }
    
    /**
     * Fetch OHLCV using MetaAPI SDK
     * 
     * @param string $symbol
     * @param string $timeframe
     * @param int $limit
     * @param int|null $since
     * @return array
     */
    protected function fetchOHLCVUsingSdk(string $symbol, string $timeframe, int $limit = 100, ?int $since = null): array
    {
        $accountId = $this->getAccountId();
        $metaApiTimeframe = $this->convertTimeframe($timeframe);
        
        try {
            // Convert timestamp format if provided
            $startTime = null;
            if ($since !== null) {
                // SDK expects ISO 8601 format: "YYYY-MM-DD HH:MM:SS.fff"
                $startTime = date('Y-m-d H:i:s', $since) . '.000';
            }
            
            // Use SDK's getHistoricalCandles method
            // Based on SDK README: getHistoricalCandles(accountId, symbol, timeframe, startTime, limit)
            // SDK returns candles array directly
            if (!method_exists($this->sdkAccountApi, 'getHistoricalCandles')) {
                throw new \Exception('SDK does not have getHistoricalCandles method');
            }
            
            // Call SDK method with parameters
            // Note: SDK may expect startTime before limit, or may have different signature
            // If this fails, exception will be caught and fallback to direct API will occur
            $candles = $this->sdkAccountApi->getHistoricalCandles(
                $accountId,
                $symbol,
                $metaApiTimeframe,
                $startTime,  // null if not provided
                min($limit, 1000)
            );
            
            if (empty($candles) || !is_array($candles)) {
                return [];
            }
            
            // Convert SDK format to our normalized format
            return $this->normalizeOHLCVDataFromSdk($candles);
            
        } catch (\Exception $e) {
            Log::error('MetaApiAdapter: SDK fetchOHLCV error', [
                'account_id' => $accountId,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Fetch OHLCV using direct API (fallback implementation)
     * 
     * @param string $symbol
     * @param string $timeframe
     * @param int $limit
     * @param int|null $since
     * @return array
     */
    protected function fetchOHLCVDirect(string $symbol, string $timeframe, int $limit = 100, ?int $since = null): array
    {
        // Ensure clients are created (lazy initialization)
        $this->ensureClientsCreated();
        
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            throw new \Exception('MetaApi account ID is required');
        }
        
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
     * Normalize OHLCV data from SDK format to our standard format
     * 
     * @param array $sdkCandles SDK candle data
     * @return array Normalized OHLCV array
     */
    protected function normalizeOHLCVDataFromSdk(array $sdkCandles): array
    {
        $normalized = [];
        
        foreach ($sdkCandles as $candle) {
            // SDK format may vary - handle different possible structures
            $timestamp = null;
            if (isset($candle['time'])) {
                $time = $candle['time'];
                if (is_string($time)) {
                    $timestamp = strtotime($time) * 1000;
                } elseif (is_numeric($time)) {
                    $timestamp = $time < 10000000000 ? $time * 1000 : $time;
                }
            } elseif (isset($candle['timestamp'])) {
                $timestamp = $candle['timestamp'] < 10000000000 ? $candle['timestamp'] * 1000 : $candle['timestamp'];
            }
            
            if (!$timestamp) {
                continue; // Skip invalid candles
            }
            
            $normalized[] = [
                'timestamp' => (int) $timestamp,
                'open' => (float) ($candle['open'] ?? 0),
                'high' => (float) ($candle['high'] ?? 0),
                'low' => (float) ($candle['low'] ?? 0),
                'close' => (float) ($candle['close'] ?? 0),
                'volume' => (int) ($candle['volume'] ?? $candle['tickVolume'] ?? 0),
            ];
        }
        
        return $normalized;
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
        // Ensure clients are created (lazy initialization)
        $this->ensureClientsCreated();
        
        $accountId = $this->getAccountId();
        
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
        $startTime = microtime(true);
        
        // Ensure clients are created (lazy initialization)
        $this->ensureClientsCreated();
        
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->getAccountId();
        
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

            $this->recordLatency('request', microtime(true) - $startTime);
            
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
        // Ensure clients are created (lazy initialization)
        $this->ensureClientsCreated();
        
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            throw new \Exception('MetaApi account ID is required');
        }
        
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
     * Fetch account balance
     * 
     * Uses account-information endpoint
     * 
     * @return array ['balance' => float, 'equity' => float, 'margin' => float, 'free_margin' => float, ...]
     */
    public function fetchBalance(): array
    {
        $accountInfo = $this->getAccountInfo();
        
        return [
            'balance' => $accountInfo['balance'] ?? 0,
            'equity' => $accountInfo['equity'] ?? 0,
            'margin' => $accountInfo['margin'] ?? 0,
            'free_margin' => $accountInfo['free_margin'] ?? 0,
            'margin_level' => $accountInfo['margin_level'] ?? null,
            'currency' => $accountInfo['currency'] ?? 'USD',
        ];
    }

    /**
     * Fetch open positions
     * 
     * Uses GET /users/current/accounts/{accountId}/positions endpoint
     * 
     * @return array Array of position data
     */
    /**
     * Fetch open positions (pending positions)
     * 
     * Uses GET /users/current/accounts/{accountId}/positions endpoint
     * This returns OPEN positions only (not closed positions)
     * 
     * @return array Array of position data
     */
    public function fetchPositions(): array
    {
        // Ensure clients are created (lazy initialization)
        $this->ensureClientsCreated();
        
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->getAccountId();
        
        $endpoint = sprintf('/users/current/accounts/%s/positions', $accountId);

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

            // MetaApi returns array of MetatraderPosition objects
            if (!is_array($data)) {
                return [];
            }

            // Normalize position data
            return array_map(function ($position) {
                return [
                    'id' => $position['id'] ?? null,
                    'symbol' => $position['symbol'] ?? null,
                    'type' => $position['type'] ?? null, // POSITION_TYPE_BUY or POSITION_TYPE_SELL
                    'volume' => isset($position['volume']) ? (float) $position['volume'] : 0,
                    'profit' => isset($position['profit']) ? (float) $position['profit'] : 0,
                    'swap' => isset($position['swap']) ? (float) $position['swap'] : 0,
                    'commission' => isset($position['commission']) ? (float) $position['commission'] : 0,
                    'openPrice' => isset($position['openPrice']) ? (float) $position['openPrice'] : 0,
                    'currentPrice' => isset($position['currentPrice']) ? (float) $position['currentPrice'] : 0,
                    'stopLoss' => isset($position['stopLoss']) ? (float) $position['stopLoss'] : null,
                    'takeProfit' => isset($position['takeProfit']) ? (float) $position['takeProfit'] : null,
                    'time' => $position['time'] ?? null,
                    'unrealizedProfit' => isset($position['unrealizedProfit']) ? (float) $position['unrealizedProfit'] : 0,
                    'realizedProfit' => isset($position['realizedProfit']) ? (float) $position['realizedProfit'] : 0,
                ];
            }, $data);

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            
            if ($statusCode === 401) {
                throw new \Exception('MetaApi authentication failed. Please check your API token.');
            } elseif ($statusCode === 404) {
                throw new \Exception('MetaApi account not found. Please check your account ID.');
            }
            
            throw new \Exception('Failed to fetch positions: ' . $e->getMessage());
        }
    }

    /**
     * Fetch pending orders
     * 
     * Uses GET /users/current/accounts/{accountId}/orders endpoint
     * Note: This endpoint returns PENDING orders only, not order history
     * 
     * @return array Array of pending order data
     */
    public function fetchPendingOrders(): array
    {
        return $this->fetchOrders(false);
    }
    
    /**
     * Fetch order history (filled, completed, cancelled orders)
     * 
     * Note: MetaApi REST API /orders endpoint only returns PENDING orders.
     * For order history, we try:
     * 1. SDK methods (if available)
     * 2. Deals endpoint (if available)
     * 3. Return empty with helpful message
     * 
     * @param int $limit Maximum number of orders to return (default: 100)
     * @return array Array of order history data
     */
    public function fetchOrderHistory(int $limit = 100): array
    {
        // Try SDK first if available (SDK might have order history methods)
        if ($this->useSdk && $this->sdkAccountApi) {
            try {
                $history = $this->fetchOrderHistoryUsingSdk($limit);
                if (!empty($history)) {
                    return $history;
                }
            } catch (\Exception $e) {
                Log::debug('MetaApiAdapter: SDK order history fetch failed, trying alternative', [
                    'error' => $e->getMessage(),
                ]);
                // Fall through to alternative method
            }
        }
        
        // Alternative: Try to fetch from deals endpoint (deals contain order execution history)
        try {
            $history = $this->fetchOrderHistoryFromApi($limit);
            if (!empty($history)) {
                return $history;
            }
        } catch (\Exception $e) {
            Log::debug('MetaApiAdapter: Order history API fetch failed', [
                'error' => $e->getMessage(),
            ]);
        }
        
        // If all methods fail, return empty with note
        Log::info('MetaApiAdapter: Order history not available', [
            'note' => 'MetaApi REST API only provides pending orders. Order history requires MetaApi SDK, deals endpoint, or database storage.',
        ]);
        
        return [];
    }
    
    /**
     * Fetch order history using MetaApi SDK
     * 
     * @param int $limit
     * @return array
     */
    protected function fetchOrderHistoryUsingSdk(int $limit): array
    {
        // SDK might have methods like getOrderHistory, getHistoricalOrders, etc.
        // Check available methods and use appropriate one
        if (method_exists($this->sdkAccountApi, 'getOrderHistory')) {
            $accountId = $this->getAccountId();
            $history = $this->sdkAccountApi->getOrderHistory($accountId, $limit);
            return $this->normalizeOrderHistoryData($history);
        }
        
        // If SDK doesn't have order history method, throw exception to fall back
        throw new \Exception('SDK does not have order history method');
    }
    
    /**
     * Fetch order history from REST API (alternative endpoint if available)
     * 
     * @param int $limit
     * @return array
     */
    protected function fetchOrderHistoryFromApi(int $limit): array
    {
        // Ensure clients are created
        $this->ensureClientsCreated();
        
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            throw new \Exception('MetaApi account ID is required');
        }
        
        // Try historical orders endpoint (if available)
        // Note: MetaApi REST API might not have this endpoint
        $endpoint = sprintf('/users/current/accounts/%s/historical-orders', $accountId);
        
        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                ],
                'http_errors' => false,
            ]);
            
            $statusCode = $response->getStatusCode();
            
            // If endpoint doesn't exist (404), try alternative approach
            if ($statusCode === 404) {
                // Try to get order history from positions/deals endpoint
                // Some brokers store order history in deals/positions
                return $this->fetchOrderHistoryFromDeals($limit);
            }
            
            if ($statusCode !== 200) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                throw new \Exception('MetaApi error: ' . $errorMessage);
            }
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!is_array($data)) {
                return [];
            }
            
            return $this->normalizeOrderHistoryData($data);
            
        } catch (RequestException $e) {
            // If endpoint doesn't exist, try alternative
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 404) {
                return $this->fetchOrderHistoryFromDeals($limit);
            }
            throw $e;
        }
    }
    
    /**
     * Try to fetch order history from deals endpoint (alternative approach)
     * 
     * MetaApi might have a deals endpoint that shows order execution history
     * 
     * @param int $limit
     * @return array
     */
    protected function fetchOrderHistoryFromDeals(int $limit): array
    {
        // Ensure clients are created
        $this->ensureClientsCreated();
        
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            return [];
        }
        
        // Try deals endpoint - MetaApi might store order history in deals
        $endpoint = sprintf('/users/current/accounts/%s/deals', $accountId);
        
        try {
            $response = $this->client->get($endpoint, [
                'query' => [
                    'limit' => min($limit, 1000),
                ],
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                ],
                'http_errors' => false,
            ]);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                
                if (is_array($data)) {
                    // Convert deals to order history format
                    return $this->convertDealsToOrderHistory($data);
                }
            }
            
            // If deals endpoint doesn't work, try to get from positions (closed positions might have order info)
            return $this->fetchOrderHistoryFromClosedPositions($limit);
            
        } catch (\Exception $e) {
            Log::debug('MetaApiAdapter: Deals endpoint failed, trying closed positions', [
                'error' => $e->getMessage(),
            ]);
            return $this->fetchOrderHistoryFromClosedPositions($limit);
        }
    }
    
    /**
     * Try to get order history from closed positions
     * 
     * @param int $limit
     * @return array
     */
    protected function fetchOrderHistoryFromClosedPositions(int $limit): array
    {
        // MetaApi REST API doesn't provide order history directly
        // For now, return empty with helpful message
        // In the future, this could:
        // 1. Use MetaApi SDK which might have order history
        // 2. Store orders in database when executed
        // 3. Use MetaApi streaming API to capture order events
        
        Log::info('MetaApiAdapter: Order history not available via REST API', [
            'note' => 'MetaApi REST API /orders endpoint only returns pending orders. Order history requires MetaApi SDK, database storage, or streaming API.',
        ]);
        
        return [];
    }
    
    /**
     * Convert deals data to order history format
     * 
     * @param array $deals
     * @return array
     */
    protected function convertDealsToOrderHistory(array $deals): array
    {
        if (!is_array($deals)) {
            return [];
        }
        
        return array_map(function ($deal) {
            if (!is_array($deal)) {
                return null;
            }
            
            return [
                'id' => $deal['id'] ?? $deal['dealId'] ?? null,
                'orderId' => $deal['orderId'] ?? null,
                'symbol' => $deal['symbol'] ?? null,
                'type' => $deal['type'] ?? $deal['dealType'] ?? null,
                'volume' => isset($deal['volume']) ? (float) $deal['volume'] : 0,
                'openPrice' => isset($deal['openPrice']) ? (float) $deal['openPrice'] : (isset($deal['price']) ? (float) $deal['price'] : 0),
                'closePrice' => isset($deal['closePrice']) ? (float) $deal['closePrice'] : null,
                'time' => $deal['time'] ?? $deal['timeCreated'] ?? null,
                'closeTime' => $deal['closeTime'] ?? $deal['timeClosed'] ?? null,
                'state' => 'FILLED', // Deals are filled orders
                'profit' => isset($deal['profit']) ? (float) $deal['profit'] : null,
                'swap' => isset($deal['swap']) ? (float) $deal['swap'] : null,
                'commission' => isset($deal['commission']) ? (float) $deal['commission'] : null,
                'comment' => $deal['comment'] ?? null,
            ];
        }, $deals);
    }
    
    /**
     * Normalize order history data
     * 
     * @param array $data
     * @return array
     */
    protected function normalizeOrderHistoryData(array $data): array
    {
        if (!is_array($data)) {
            return [];
        }
        
        return array_map(function ($order) {
            if (!is_array($order)) {
                return null;
            }
            return [
                'id' => $order['id'] ?? $order['orderId'] ?? null,
                'symbol' => $order['symbol'] ?? null,
                'type' => $order['type'] ?? $order['orderType'] ?? null,
                'volume' => isset($order['volume']) ? (float) $order['volume'] : 0,
                'openPrice' => isset($order['openPrice']) ? (float) $order['openPrice'] : (isset($order['price']) ? (float) $order['price'] : 0),
                'closePrice' => isset($order['closePrice']) ? (float) $order['closePrice'] : null,
                'stopLoss' => isset($order['stopLoss']) ? (float) $order['stopLoss'] : null,
                'takeProfit' => isset($order['takeProfit']) ? (float) $order['takeProfit'] : null,
                'time' => $order['time'] ?? $order['timeCreated'] ?? null,
                'closeTime' => $order['closeTime'] ?? $order['timeClosed'] ?? null,
                'expirationTime' => $order['expirationTime'] ?? $order['expiration'] ?? null,
                'state' => $order['state'] ?? $order['orderState'] ?? null,
                'comment' => $order['comment'] ?? null,
                'magic' => $order['magic'] ?? null,
                'profit' => isset($order['profit']) ? (float) $order['profit'] : null,
                'swap' => isset($order['swap']) ? (float) $order['swap'] : null,
                'commission' => isset($order['commission']) ? (float) $order['commission'] : null,
            ];
        }, $data);
    }
    
    /**
     * Fetch orders (all or pending only)
     * 
     * Uses GET /users/current/accounts/{accountId}/orders endpoint
     * 
     * @param bool $includeAll If true, fetch all orders (pending + history), if false, only pending
     * @return array Array of order data
     */
    public function fetchOrders(bool $includeAll = false): array
    {
        // Ensure clients are created (lazy initialization)
        $this->ensureClientsCreated();
        
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->getAccountId();
        if (empty($accountId)) {
            throw new \Exception('MetaApi account ID is required');
        }
        
        $orders = [];
        
        // Fetch pending orders
        $endpoint = sprintf('/users/current/accounts/%s/orders', $accountId);

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
                
                Log::warning('MetaApi fetchOrders: API returned non-200 status', [
                    'account_id' => $accountId,
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $responseBody,
                ]);
                
                if ($statusCode === 401) {
                    throw new \Exception('MetaApi authentication failed. Please check your API token.');
                } elseif ($statusCode === 404) {
                    throw new \Exception('MetaApi account not found. Please check your account ID.');
                }
                
                throw new \Exception('MetaApi error: ' . $errorMessage);
            }

            $data = json_decode($response->getBody()->getContents(), true);

            // MetaApi returns array of MetatraderOrder objects
            if (!is_array($data)) {
                Log::debug('MetaApi fetchOrders: Response is not an array', [
                    'account_id' => $accountId,
                    'data_type' => gettype($data),
                    'data' => $data,
                ]);
                return [];
            }

            // Normalize order data
            $orders = array_map(function ($order) {
                if (!is_array($order)) {
                    return null;
                }
                return [
                    'id' => $order['id'] ?? $order['orderId'] ?? null,
                    'symbol' => $order['symbol'] ?? null,
                    'type' => $order['type'] ?? $order['orderType'] ?? null, // ORDER_TYPE_BUY_LIMIT, ORDER_TYPE_SELL_LIMIT, etc.
                    'volume' => isset($order['volume']) ? (float) $order['volume'] : 0,
                    'openPrice' => isset($order['openPrice']) ? (float) $order['openPrice'] : (isset($order['price']) ? (float) $order['price'] : 0),
                    'stopLoss' => isset($order['stopLoss']) ? (float) $order['stopLoss'] : null,
                    'takeProfit' => isset($order['takeProfit']) ? (float) $order['takeProfit'] : null,
                    'time' => $order['time'] ?? $order['timeCreated'] ?? null,
                    'expirationTime' => $order['expirationTime'] ?? $order['expiration'] ?? null,
                    'state' => $order['state'] ?? $order['orderState'] ?? null, // ORDER_STATE_STARTED, ORDER_STATE_FILLED, etc.
                    'comment' => $order['comment'] ?? null,
                    'magic' => $order['magic'] ?? null,
                    'positionId' => $order['positionId'] ?? null,
                ];
            }, $data);
            
            // Filter out null values
            $orders = array_filter($orders, function($order) {
                return $order !== null;
            });
            
            // Re-index array
            $orders = array_values($orders);
            
            Log::debug('MetaApi fetchOrders: Fetched pending orders', [
                'account_id' => $accountId,
                'count' => count($orders),
            ]);

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            
            Log::error('MetaApi fetchOrders: RequestException', [
                'account_id' => $accountId,
                'status_code' => $statusCode,
                'error' => $e->getMessage(),
                'response' => $responseBody,
            ]);
            
            if ($statusCode === 401) {
                throw new \Exception('MetaApi authentication failed. Please check your API token.');
            } elseif ($statusCode === 404) {
                throw new \Exception('MetaApi account not found. Please check your account ID.');
            }
            
            throw new \Exception('Failed to fetch orders: ' . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('MetaApi fetchOrders: Unexpected error', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
        
        // If includeHistory is true, also fetch order history
        // Note: MetaApi might have a separate endpoint for order history
        // For now, we only return pending orders
        // TODO: Implement order history endpoint if available
        
        return $orders;
    }

    /**
     * Place market order
     * 
     * Uses POST /users/current/accounts/{accountId}/trade endpoint
     * 
     * @param string $symbol Trading symbol (e.g., 'EURUSD', 'BTCUSDT')
     * @param string $direction 'buy' or 'sell'
     * @param float $volume Order volume (lot size)
     * @param float|null $sl Stop loss price (optional)
     * @param float|null $tp Take profit price (optional)
     * @param string|null $comment Order comment (optional)
     * @return array Order result with orderId/positionId
     */
    public function placeMarketOrder(string $symbol, string $direction, float $volume, ?float $sl = null, ?float $tp = null, ?string $comment = null): array
    {
        $startTime = microtime(true);
        $accountId = $this->getAccountId();
        $endpoint = sprintf('/users/current/accounts/%s/trade', $accountId);

        // Map direction to MetaAPI order type
        $orderType = strtolower($direction) === 'buy' ? 'ORDER_TYPE_BUY' : 'ORDER_TYPE_SELL';

        // For market orders, actionType should be the order type itself (not ORDER_TYPE_MARKET)
        $body = [
            'actionType' => $orderType,
            'symbol' => $symbol,
            'volume' => $volume,
        ];

        // Add SL/TP if provided
        if ($sl !== null) {
            $body['stopLoss'] = $sl;
        }
        if ($tp !== null) {
            $body['takeProfit'] = $tp;
        }
        if ($comment !== null) {
            $body['comment'] = $comment;
        }

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200 && $statusCode !== 201) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 400) {
                    throw new \Exception('Invalid trade request: ' . $errorMessage);
                } elseif ($statusCode === 401) {
                    throw new \Exception('MetaApi authentication failed. Please check your API token.');
                } elseif ($statusCode === 403) {
                    throw new \Exception('Trade execution forbidden. Check account permissions.');
                }
                
                throw new \Exception('Trade execution failed: ' . $errorMessage);
            }

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('MetaApiAdapter: Market order response from MetaAPI', [
                'response_data' => $data,
                'has_numericTicket' => isset($data['numericTicket']),
                'has_orderId' => isset($data['orderId']),
                'has_positionId' => isset($data['positionId']),
                'all_keys' => array_keys($data ?? []),
            ]);

            // Check if response contains an error code
            if (isset($data['stringCode']) && strpos($data['stringCode'], 'RETCODE_') !== false) {
                $errorMessage = $data['message'] ?? $data['stringCode'];
                
                Log::warning('MetaApiAdapter: Market order failed with retcode', [
                    'symbol' => $symbol,
                    'direction' => $direction,
                    'code' => $data['stringCode'],
                    'message' => $errorMessage,
                ]);
                
                throw new \Exception($errorMessage . ' (Code: ' . $data['stringCode'] . ')');
            }

            // MetaAPI returns TradeResponse with numericTicket (order ID) or position ID
            $this->recordLatency('trade', microtime(true) - $startTime);
            
            return [
                'success' => true,
                'orderId' => $data['numericTicket'] ?? $data['orderId'] ?? null,
                'positionId' => $data['positionId'] ?? $data['position'] ?? null,
                'data' => $data,
            ];

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorData = json_decode($responseBody, true);
            
            $errorMessage = $errorData['message'] ?? $e->getMessage();
            
            if ($statusCode === 400) {
                throw new \Exception('Invalid trade request: ' . $errorMessage);
            } elseif ($statusCode === 401) {
                throw new \Exception('MetaApi authentication failed. Please check your API token.');
            }
            
            throw new \Exception('Trade execution failed: ' . $errorMessage);
        }
    }

    /**
     * Place limit order
     * 
     * Uses POST /users/current/accounts/{accountId}/trade endpoint
     * 
     * @param string $symbol Trading symbol
     * @param string $direction 'buy' or 'sell'
     * @param float $volume Order volume
     * @param float $price Limit price
     * @param float|null $sl Stop loss price (optional)
     * @param float|null $tp Take profit price (optional)
     * @param string|null $comment Order comment (optional)
     * @return array Order result
     */
    public function placeLimitOrder(string $symbol, string $direction, float $volume, float $price, ?float $sl = null, ?float $tp = null, ?string $comment = null): array
    {
        $accountId = $this->getAccountId();
        $endpoint = sprintf('/users/current/accounts/%s/trade', $accountId);

        // Map direction to MetaAPI limit order type
        $orderType = strtolower($direction) === 'buy' ? 'ORDER_TYPE_BUY_LIMIT' : 'ORDER_TYPE_SELL_LIMIT';

        $body = [
            'actionType' => $orderType,
            'symbol' => $symbol,
            'volume' => $volume,
            'openPrice' => $price,
        ];

        // Add SL/TP if provided
        if ($sl !== null) {
            $body['stopLoss'] = $sl;
        }
        if ($tp !== null) {
            $body['takeProfit'] = $tp;
        }
        if ($comment !== null) {
            $body['comment'] = $comment;
        }

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200 && $statusCode !== 201) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                throw new \Exception('Limit order failed: ' . $errorMessage);
            }

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('MetaApiAdapter: Limit order response from MetaAPI', [
                'response_data' => $data,
                'has_numericTicket' => isset($data['numericTicket']),
                'has_orderId' => isset($data['orderId']),
                'has_positionId' => isset($data['positionId']),
                'all_keys' => array_keys($data ?? []),
            ]);

            // Check if response contains an error code
            if (isset($data['stringCode']) && strpos($data['stringCode'], 'RETCODE_') !== false) {
                $errorMessage = $data['message'] ?? $data['stringCode'];
                
                Log::warning('MetaApiAdapter: Limit order failed with retcode', [
                    'symbol' => $symbol,
                    'direction' => $direction,
                    'price' => $price,
                    'code' => $data['stringCode'],
                    'message' => $errorMessage,
                ]);
                
                throw new \Exception($errorMessage . ' (Code: ' . $data['stringCode'] . ')');
            }

            return [
                'success' => true,
                'orderId' => $data['numericTicket'] ?? $data['orderId'] ?? null,
                'positionId' => $data['positionId'] ?? $data['position'] ?? null,
                'data' => $data,
            ];

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorData = json_decode($responseBody, true);
            
            throw new \Exception('Limit order failed: ' . ($errorData['message'] ?? $e->getMessage()));
        }
    }

    /**
     * Close position
     * 
     * Uses DELETE /users/current/accounts/{accountId}/positions/{positionId} endpoint
     * 
     * @param string $positionId Position ID
     * @param float|null $volume Volume to close (null = close all)
     * @return array Close result
     */
    public function closePosition(string $positionId, ?float $volume = null): array
    {
        $accountId = $this->getAccountId();
        $endpoint = sprintf('/users/current/accounts/%s/positions/%s', $accountId, $positionId);

        $params = [];
        if ($volume !== null) {
            $params['volume'] = $volume;
        }

        try {
            $response = $this->client->delete($endpoint, [
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                ],
                'query' => $params,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200 && $statusCode !== 204) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 404) {
                    throw new \Exception('Position not found: ' . $errorMessage);
                }
                
                throw new \Exception('Close position failed: ' . $errorMessage);
            }

            return [
                'success' => true,
                'message' => 'Position closed successfully',
            ];

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorData = json_decode($responseBody, true);
            
            throw new \Exception('Close position failed: ' . ($errorData['message'] ?? $e->getMessage()));
        }
    }

    /**
     * Modify position (update SL/TP)
     * 
     * Uses PATCH /users/current/accounts/{accountId}/positions/{positionId} endpoint
     * 
     * @param string $positionId Position ID
     * @param float|null $sl New stop loss (null = don't change)
     * @param float|null $tp New take profit (null = don't change)
     * @return array Modify result
     */
    public function modifyPosition(string $positionId, ?float $sl = null, ?float $tp = null): array
    {
        $accountId = $this->getAccountId();
        $endpoint = sprintf('/users/current/accounts/%s/positions/%s', $accountId, $positionId);

        $body = [];
        if ($sl !== null) {
            $body['stopLoss'] = $sl;
        }
        if ($tp !== null) {
            $body['takeProfit'] = $tp;
        }

        if (empty($body)) {
            throw new \Exception('At least one of stopLoss or takeProfit must be provided');
        }

        try {
            $response = $this->client->patch($endpoint, [
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 404) {
                    throw new \Exception('Position not found: ' . $errorMessage);
                }
                
                throw new \Exception('Modify position failed: ' . $errorMessage);
            }

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'message' => 'Position modified successfully',
                'data' => $data,
            ];

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorData = json_decode($responseBody, true);
            
            throw new \Exception('Modify position failed: ' . ($errorData['message'] ?? $e->getMessage()));
        }
    }

    /**
     * Modify order (update SL/TP or price for pending orders)
     * 
     * Uses PATCH /users/current/accounts/{accountId}/orders/{orderId} endpoint
     * 
     * @param string $orderId Order ID
     * @param float|null $sl New stop loss (null = don't change)
     * @param float|null $tp New take profit (null = don't change)
     * @param float|null $price New price for limit orders (null = don't change)
     * @return array Modify result
     */
    public function modifyOrder(string $orderId, ?float $sl = null, ?float $tp = null, ?float $price = null): array
    {
        $accountId = $this->getAccountId();
        $endpoint = sprintf('/users/current/accounts/%s/orders/%s', $accountId, $orderId);

        $body = [];
        if ($sl !== null) {
            $body['stopLoss'] = $sl;
        }
        if ($tp !== null) {
            $body['takeProfit'] = $tp;
        }
        if ($price !== null) {
            $body['openPrice'] = $price;
        }

        if (empty($body)) {
            throw new \Exception('At least one of stopLoss, takeProfit, or price must be provided');
        }

        try {
            $response = $this->client->patch($endpoint, [
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 404) {
                    throw new \Exception('Order not found: ' . $errorMessage);
                }
                
                throw new \Exception('Modify order failed: ' . $errorMessage);
            }

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'message' => 'Order modified successfully',
                'data' => $data,
            ];

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorData = json_decode($responseBody, true);
            
            throw new \Exception('Modify order failed: ' . ($errorData['message'] ?? $e->getMessage()));
        }
    }

    /**
     * Cancel order
     * 
     * Uses DELETE /users/current/accounts/{accountId}/orders/{orderId} endpoint
     * 
     * @param string $orderId Order ID
     * @return array Cancel result
     */
    public function cancelOrder(string $orderId): array
    {
        $accountId = $this->getAccountId();
        $endpoint = sprintf('/users/current/accounts/%s/orders/%s', $accountId, $orderId);

        try {
            $response = $this->client->delete($endpoint, [
                'headers' => [
                    'auth-token' => $this->credentials['api_token'],
                ],
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200 && $statusCode !== 204) {
                $responseBody = $response->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['message'] ?? "HTTP {$statusCode}";
                
                if ($statusCode === 404) {
                    throw new \Exception('Order not found: ' . $errorMessage);
                }
                
                throw new \Exception('Cancel order failed: ' . $errorMessage);
            }

            return [
                'success' => true,
                'message' => 'Order cancelled successfully',
            ];

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorData = json_decode($responseBody, true);
            
            throw new \Exception('Cancel order failed: ' . ($errorData['message'] ?? $e->getMessage()));
        }
    }

    /**
     * Place order (generic - delegates to market or limit based on order_type)
     * 
     * @param string $symbol Trading symbol
     * @param string $direction 'buy' or 'sell'
     * @param float $volume Order volume
     * @param string $orderType 'market' or 'limit'
     * @param float|null $price Price (required for limit orders)
     * @param float|null $sl Stop loss (optional)
     * @param float|null $tp Take profit (optional)
     * @param string|null $comment Order comment (optional)
     * @return array Order result
     */
    public function placeOrder(string $symbol, string $direction, float $volume, string $orderType = 'market', ?float $price = null, ?float $sl = null, ?float $tp = null, ?string $comment = null): array
    {
        // Ensure API token is available
        $this->ensureApiToken();
        
        if ($orderType === 'limit') {
            if ($price === null) {
                throw new \Exception('Price is required for limit orders');
            }
            return $this->placeLimitOrder($symbol, $direction, $volume, $price, $sl, $tp, $comment);
        } else {
            return $this->placeMarketOrder($symbol, $direction, $volume, $sl, $tp, $comment);
        }
    }

    /**
     * Create market order (alias for ExecutionJob compatibility)
     * 
     * @param string $symbol Trading symbol
     * @param string $side 'buy' or 'sell'
     * @param float $amount Order volume
     * @param array $params Additional parameters (stopLoss, takeProfit, comment)
     * @return array Order result
     */
    public function createMarketOrder(string $symbol, string $side, float $amount, array $params = []): array
    {
        return $this->placeMarketOrder(
            $symbol,
            $side,
            $amount,
            $params['stopLoss'] ?? null,
            $params['takeProfit'] ?? null,
            $params['comment'] ?? null
        );
    }

    /**
     * Create limit order (alias for ExecutionJob compatibility)
     * 
     * @param string $symbol Trading symbol
     * @param string $side 'buy' or 'sell'
     * @param float $amount Order volume
     * @param float $price Limit price
     * @param array $params Additional parameters (stopLoss, takeProfit, comment)
     * @return array Order result
     */
    public function createLimitOrder(string $symbol, string $side, float $amount, float $price, array $params = []): array
    {
        return $this->placeLimitOrder(
            $symbol,
            $side,
            $amount,
            $price,
            $params['stopLoss'] ?? null,
            $params['takeProfit'] ?? null,
            $params['comment'] ?? null
        );
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
            // MetaApi returns ISO 8601 datetime strings or Unix timestamps in seconds
            // Convert to milliseconds for JavaScript Date compatibility
            $timestamp = 0;
            if (isset($candle['time'])) {
                if (is_string($candle['time'])) {
                    // ISO 8601 string - convert to seconds, then to milliseconds
                    $timestamp = strtotime($candle['time']) * 1000;
                } else {
                    // Already a numeric timestamp
                    // If it's less than 10^10, assume it's in seconds (Unix timestamp)
                    // If it's 10^10 or more, assume it's already in milliseconds
                    $ts = (int) $candle['time'];
                    $timestamp = $ts < 10000000000 ? $ts * 1000 : $ts;
                }
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
            // MetaApi returns ISO 8601 datetime strings or Unix timestamps in seconds
            // Convert to milliseconds for JavaScript Date compatibility
            $timestamp = 0;
            if (isset($tick['time'])) {
                if (is_string($tick['time'])) {
                    // ISO 8601 string - convert to seconds, then to milliseconds
                    $timestamp = strtotime($tick['time']) * 1000;
                } else {
                    // Already a numeric timestamp
                    // If it's less than 10^10, assume it's in seconds (Unix timestamp)
                    // If it's 10^10 or more, assume it's already in milliseconds
                    $ts = (int) $tick['time'];
                    $timestamp = $ts < 10000000000 ? $ts * 1000 : $ts;
                }
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

    /**
     * Ensure API token is set - tries multiple sources
     * 
     * Priority order:
     * 1. account_token (account-specific token from Profile API - most secure)
     * 2. api_token (from credentials)
     * 3. config('trading-management.metaapi.api_token')
     * 4. Global settings
     * 
     * @throws \Exception If token cannot be found
     */
    protected function ensureApiToken(): void
    {
        // If already set, return
        if (!empty($this->credentials['api_token'])) {
            return;
        }

        // Priority 1: Account-specific token (scoped to account, more secure)
        if (empty($this->credentials['api_token']) && !empty($this->credentials['account_token'])) {
            $this->credentials['api_token'] = $this->credentials['account_token'];
            return;
        }

        // Priority 2: Config
        if (empty($this->credentials['api_token'])) {
            $this->credentials['api_token'] = config('trading-management.metaapi.api_token');
        }
        
        // Priority 3: Global settings
        if (empty($this->credentials['api_token'])) {
            $this->credentials['api_token'] = $this->getTokenFromGlobalSettings();
        }

        // Still empty? Throw exception
        if (empty($this->credentials['api_token'])) {
            throw new \Exception('MetaApi API token is required. Please configure it in Global Settings (Trading Management > Config > Global Settings), generate an account token, or set METAAPI_TOKEN in .env file');
        }
    }

    protected function getTokenFromGlobalSettings(): ?string
    {
        try {
            $globalConfig = \App\Services\GlobalConfigurationService::get('metaapi_global_settings', []);
            if (!empty($globalConfig['api_token'])) {
                try {
                    return \Illuminate\Support\Facades\Crypt::decryptString($globalConfig['api_token']);
                } catch (\Exception $e) {
                    // If decryption fails, assume it's stored as plain text
                    return $globalConfig['api_token'];
                }
            }
        } catch (\Exception $e) {
            Log::debug('Failed to get MetaApi token from global settings', [
                'error' => $e->getMessage()
            ]);
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
}

