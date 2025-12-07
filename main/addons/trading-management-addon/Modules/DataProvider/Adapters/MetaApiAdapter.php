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
        
        // Prefer account token if available (more secure, scoped to account)
        // Fallback to main API token: credentials -> config -> global settings
        if (empty($this->credentials['api_token'])) {
            // First check for account-specific token (if generated via Profile API)
            $this->credentials['api_token'] = $this->credentials['account_token']
                ?? config('trading-management.metaapi.api_token')
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
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->credentials['account_id'];
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
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->credentials['account_id'];
        if (empty($accountId)) {
            throw new \Exception('MetaApi account ID is required');
        }
        
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
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->credentials['account_id'];
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
    public function fetchPositions(): array
    {
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->credentials['account_id'];
        if (empty($accountId)) {
            throw new \Exception('MetaApi account ID is required');
        }
        
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
     * 
     * @return array Array of order data
     */
    public function fetchOrders(): array
    {
        // Ensure API token is available
        $this->ensureApiToken();
        
        $accountId = $this->credentials['account_id'];
        if (empty($accountId)) {
            throw new \Exception('MetaApi account ID is required');
        }
        
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
                return [];
            }

            // Normalize order data
            return array_map(function ($order) {
                return [
                    'id' => $order['id'] ?? null,
                    'symbol' => $order['symbol'] ?? null,
                    'type' => $order['type'] ?? null, // ORDER_TYPE_BUY_LIMIT, ORDER_TYPE_SELL_LIMIT, etc.
                    'volume' => isset($order['volume']) ? (float) $order['volume'] : 0,
                    'openPrice' => isset($order['openPrice']) ? (float) $order['openPrice'] : 0,
                    'stopLoss' => isset($order['stopLoss']) ? (float) $order['stopLoss'] : null,
                    'takeProfit' => isset($order['takeProfit']) ? (float) $order['takeProfit'] : null,
                    'time' => $order['time'] ?? null,
                    'expirationTime' => $order['expirationTime'] ?? null,
                    'state' => $order['state'] ?? null, // ORDER_STATE_STARTED, ORDER_STATE_FILLED, etc.
                    'comment' => $order['comment'] ?? null,
                ];
            }, $data);

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            
            if ($statusCode === 401) {
                throw new \Exception('MetaApi authentication failed. Please check your API token.');
            } elseif ($statusCode === 404) {
                throw new \Exception('MetaApi account not found. Please check your account ID.');
            }
            
            throw new \Exception('Failed to fetch orders: ' . $e->getMessage());
        }
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
        $accountId = $this->credentials['account_id'];
        $endpoint = sprintf('/users/current/accounts/%s/trade', $accountId);

        // Map direction to MetaAPI order type
        $orderType = strtolower($direction) === 'buy' ? 'ORDER_TYPE_BUY' : 'ORDER_TYPE_SELL';

        $body = [
            'actionType' => 'ORDER_TYPE_MARKET',
            'symbol' => $symbol,
            'volume' => $volume,
        ];

        // Set order type based on direction
        if ($orderType === 'ORDER_TYPE_BUY') {
            $body['type'] = 'ORDER_TYPE_BUY';
        } else {
            $body['type'] = 'ORDER_TYPE_SELL';
        }

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

            // MetaAPI returns TradeResponse with numericTicket (order ID) or position ID
            return [
                'success' => true,
                'orderId' => $data['numericTicket'] ?? $data['orderId'] ?? null,
                'positionId' => $data['positionId'] ?? null,
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
        $accountId = $this->credentials['account_id'];
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

            return [
                'success' => true,
                'orderId' => $data['numericTicket'] ?? $data['orderId'] ?? null,
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
        $accountId = $this->credentials['account_id'];
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
        $accountId = $this->credentials['account_id'];
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
        $accountId = $this->credentials['account_id'];
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
        $accountId = $this->credentials['account_id'];
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
            \Illuminate\Support\Facades\Log::debug('Failed to get MetaApi token from global settings', [
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

