<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter;

/**
 * MetaAPI Streaming Service
 * 
 * Handles Socket.IO connection to MetaAPI for real-time market data streaming
 * Based on MetaAPI Python SDK implementation (metaapi_websocket_client.py)
 * 
 * Implementation Details (matching Python SDK):
 * - Dynamically fetches server URL from provisioning API (DomainClient.get_settings())
 * - Uses Socket.IO v4 client with path '/ws' (not '/socket.io')
 * - Connection URL format: https://{hostname}.{region}-{instance}.{domain}
 * - Query params: auth-token, clientId (10 decimal float), protocol=3
 * - Headers: Client-Id (matches clientId query param)
 * - Client ID format: "{:01.10f}" (e.g., "0.1234567890")
 * - Session ID: Generated as 32-char hex string, updated from subscribe response
 * - Request ID: Generated as 32-char hex string for each request
 * 
 * Protocol Flow:
 * 1. Connect Socket.IO → Wait for 'connect' event
 * 2. Subscribe to account: { type: "subscribe", accountId, sessionId, requestId, application: "MetaApi" }
 * 3. Receive 'response' event with sessionId
 * 4. Subscribe to market data: { type: "subscribeToMarketData", accountId, symbol, subscriptions, requestId, application: "MetaApi" }
 * 5. Receive data via 'synchronization' event with type: "prices"
 * 
 * Data Packet Format:
 * - Synchronization event: { type: "prices", prices: [...], candles: [...], ticks: [...] }
 * - Candles: { symbol, timeframe, time, brokerTime, open, high, low, close, tickVolume, spread, volume }
 * - Prices: { symbol, bid, ask, time, brokerTime, ... }
 * - Ticks: { symbol, time, brokerTime, bid, ask, last, volume, side }
 */
class MetaApiStreamingService
{
    protected $client = null;
    protected string $websocketUrl;
    protected string $apiToken;
    protected string $accountId;
    protected string $redisPrefix;
    protected int $streamTtl;
    protected bool $connected = false;
    protected bool $accountSubscribed = false;
    protected ?string $sessionId = null;
    protected array $subscribedSymbols = [];
    protected array $requestResolves = [];
    protected ?string $region = null;
    protected int $instanceNumber = 0;
    protected string $domain = 'agiliumtrade.agiliumtrade.ai';
    protected ?array $urlSettings = null;
    protected int $urlSettingsCacheTime = 0;
    protected const URL_SETTINGS_CACHE_TTL = 600; // 10 minutes
    
    // Fallback mode: use REST API polling instead of Socket.IO
    protected bool $usePollingFallback = false;
    protected ?MetaApiAdapter $pollingAdapter = null;
    protected int $pollingInterval = 5; // seconds

    public function __construct(string $apiToken, string $accountId, ?string $region = null)
    {
        $this->apiToken = $apiToken;
        $this->accountId = $accountId;
        $this->region = $region ?? config('trading-management.metaapi.streaming.region', 'london');
        $this->domain = config('trading-management.metaapi.streaming.domain', 'agiliumtrade.agiliumtrade.ai');
        $this->redisPrefix = config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream');
        $this->streamTtl = config('trading-management.metaapi.streaming.stream_ttl', 60);
        
        // Try to fetch account region dynamically if not provided
        if (!$region) {
            $this->fetchAccountRegion();
        }
    }
    
    /**
     * Fetch account region from MetaAPI by trying to get account information
     * Tries common regions and determines correct one from successful API response
     */
    protected function fetchAccountRegion(): void
    {
        $regions = ['london', 'new-york', 'tokyo', 'singapore', 'sydney'];
        $adapter = new MetaApiAdapter([
            'api_token' => $this->apiToken,
            'account_id' => $this->accountId,
        ]);
        
        // Try each region until we find one that works
        foreach ($regions as $region) {
            try {
                // Set base URL for this region
                $adapter = new MetaApiAdapter([
                    'api_token' => $this->apiToken,
                    'account_id' => $this->accountId,
                    'base_url' => "https://mt-client-api-v1.{$region}.agiliumtrade.ai",
                ]);
                
                // Try to get account info (this will fail if wrong region or account not connected)
                $accountInfo = $adapter->getAccountInfo();
                
                // Success! This is the correct region
                $this->region = $region;
                Log::info('Determined MetaAPI account region', [
                    'account_id' => $this->accountId,
                    'region' => $region,
                ]);
                return;
            } catch (\Exception $e) {
                // Wrong region or other error, continue trying
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, 'not connected to broker') !== false) {
                    // Account not connected - log but continue
                    Log::warning('MetaAPI account not connected to broker', [
                        'account_id' => $this->accountId,
                        'region' => $region,
                    ]);
                }
                continue;
            }
        }
        
        // If all regions failed, keep default 'london'
        Log::warning('Could not determine MetaAPI account region, using default', [
            'account_id' => $this->accountId,
            'default_region' => $this->region,
        ]);
    }

    /**
     * Get server URL dynamically from MetaAPI provisioning API
     * 
     * Based on SDK's DomainClient.get_settings() method
     * 
     * @return array|null URL settings with 'url', 'domain', 'hostname'
     */
    protected function getServerUrlSettings(): ?array
    {
        // Return cached settings if still valid (10 minutes cache)
        if ($this->urlSettings && (time() - $this->urlSettingsCacheTime) < self::URL_SETTINGS_CACHE_TTL) {
            return $this->urlSettings;
        }
        
        try {
            // Fetch URL settings from MetaAPI provisioning API
            // Endpoint: GET https://mt-provisioning-api-v1.{domain}/users/current/servers/mt-client-api
            $provisioningUrl = "https://mt-provisioning-api-v1.{$this->domain}/users/current/servers/mt-client-api";
            
            $ch = curl_init($provisioningUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'auth-token: ' . $this->apiToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                // Enable SSL verification (matches SDK behavior)
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new \Exception("cURL error: {$error}");
            }
            
            if ($httpCode !== 200) {
                throw new \Exception("HTTP {$httpCode}: {$response}");
            }
            
            $settings = json_decode($response, true);
            if (!$settings || !isset($settings['domain']) || !isset($settings['hostname'])) {
                throw new \Exception('Invalid response format from provisioning API');
            }
            
            // Construct WebSocket URL: https://{hostname}.{region}-{instance}.{domain}
            // Instance 0 = 'a', 1 = 'b', etc.
            $instanceChar = chr(97 + $this->instanceNumber); // 'a' for 0, 'b' for 1, etc.
            $websocketUrl = "https://{$settings['hostname']}.{$this->region}-{$instanceChar}.{$settings['domain']}";
            
            $this->urlSettings = [
                'url' => $websocketUrl,
                'domain' => $settings['domain'],
                'hostname' => $settings['hostname'],
            ];
            $this->urlSettingsCacheTime = time();
            $this->websocketUrl = $websocketUrl;
            
            Log::info('Fetched MetaAPI server URL dynamically', [
                'account_id' => $this->accountId,
                'url' => $websocketUrl,
                'region' => $this->region,
                'instance' => $this->instanceNumber,
            ]);
            
            return $this->urlSettings;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch server URL dynamically, using fallback', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to config or default URL
            $fallbackUrl = config('trading-management.metaapi.streaming.websocket_url');
            if (!$fallbackUrl) {
                // Construct default URL based on region
                $instanceChar = chr(97 + $this->instanceNumber);
                $fallbackUrl = "https://mt-client-api-v1.{$this->region}-{$instanceChar}.{$this->domain}";
            }
            
            $this->websocketUrl = $fallbackUrl;
            return [
                'url' => $fallbackUrl,
                'domain' => $this->domain,
                'hostname' => 'mt-client-api-v1',
            ];
        }
    }

    /**
     * Connect to MetaAPI Socket.IO server
     * 
     * Matches Python SDK's connection flow exactly:
     * 1. Retry loop until connected (with suppress exceptions)
     * 2. Build URL with query params directly
     * 3. Wait for connect event
     * 4. Handle connection errors properly
     */
    public function connect(): bool
    {
        // First, verify account is connected to broker via REST API
        try {
            $adapter = new MetaApiAdapter([
                'api_token' => $this->apiToken,
                'account_id' => $this->accountId,
                'base_url' => "https://mt-client-api-v1.{$this->region}.agiliumtrade.ai",
            ]);
            
            // Test if we can get account info (this verifies account is connected and region is correct)
            $accountInfo = $adapter->getAccountInfo();
            
            Log::info('Verified MetaAPI account connection before Socket.IO connect', [
                'account_id' => $this->accountId,
                'region' => $this->region,
                'balance' => $accountInfo['balance'] ?? null,
            ]);
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            
            // If account not connected, log and fall back to polling (which will also fail but with better error)
            if (strpos($errorMsg, 'not connected to broker') !== false) {
                Log::error('MetaAPI account not connected to broker, cannot establish streaming', [
                    'account_id' => $this->accountId,
                    'region' => $this->region,
                    'error' => $errorMsg,
                ]);
                // Still try polling fallback, but it will likely also fail
                return $this->enablePollingFallback();
            }
            
            // If region mismatch, try to fetch correct region
            if (strpos($errorMsg, 'does not match the account region') !== false) {
                Log::warning('MetaAPI region mismatch, attempting to determine correct region', [
                    'account_id' => $this->accountId,
                    'current_region' => $this->region,
                ]);
                $this->fetchAccountRegion();
                
                // Retry with correct region
                try {
                    $adapter = new MetaApiAdapter([
                        'api_token' => $this->apiToken,
                        'account_id' => $this->accountId,
                        'base_url' => "https://mt-client-api-v1.{$this->region}.agiliumtrade.ai",
                    ]);
                    $accountInfo = $adapter->getAccountInfo();
                } catch (\Exception $e2) {
                    Log::error('Failed to verify account after region fix', [
                        'account_id' => $this->accountId,
                        'region' => $this->region,
                        'error' => $e2->getMessage(),
                    ]);
                    return $this->enablePollingFallback();
                }
            } else {
                // Other error, log and fall back
                Log::warning('Failed to verify MetaAPI account before Socket.IO connect', [
                    'account_id' => $this->accountId,
                    'error' => $errorMsg,
                ]);
                return $this->enablePollingFallback();
            }
        }
        
        // Check if ElephantIO is available
        if (!class_exists('\ElephantIO\Client') || !class_exists('\ElephantIO\Engine\SocketIO\Version4X')) {
            Log::error('ElephantIO library not found. Please install: composer require wisembly/elephant.io', [
                'account_id' => $this->accountId,
            ]);
            $this->connected = false;
            return $this->enablePollingFallback();
        }
        
        // Get server URL dynamically (matches SDK behavior)
        $urlSettings = $this->getServerUrlSettings();
        if (!$urlSettings) {
            Log::error('Failed to get server URL settings', [
                'account_id' => $this->accountId,
            ]);
            return $this->enablePollingFallback();
        }
        
        $clientClass = '\ElephantIO\Client';
        $versionClass = '\ElephantIO\Engine\SocketIO\Version4X';
        $maxRetries = 5;
        $retryDelay = 1; // seconds
        
        // Retry loop matching SDK's "while not socket_instance.connected" pattern
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                // Generate client ID with exactly 10 decimal places (matches SDK format)
                // SDK uses: "{:01.10f}".format(random()) which produces "0.1234567890"
                $clientId = sprintf("%01.10f", mt_rand() / mt_getrandmax());
                
                // Generate initial session ID (will be updated from subscribe response)
                // SDK uses random_id() which generates a hex string (typically 32 chars)
                $this->sessionId = bin2hex(random_bytes(16));
                
                // Build URL with query params directly (matches SDK exactly)
                // Python SDK: url = f'{server_url}?auth-token={token}&clientId={client_id}&protocol=3'
                $baseUrl = $urlSettings['url'];
                $urlWithParams = $baseUrl . '?' . http_build_query([
                    'auth-token' => $this->apiToken,
                    'clientId' => $clientId,
                    'protocol' => 3,
                ]);
                
                Log::debug('Attempting MetaAPI Socket.IO connection', [
                    'account_id' => $this->accountId,
                    'attempt' => $attempt + 1,
                    'url' => $baseUrl, // Log base URL, not with params for security
                    'client_id' => $clientId,
                ]);
                
                // Create client with URL containing query params (matches SDK)
                // SDK passes: socket_instance.connect(url, socketio_path='ws', headers={'Client-Id': client_id})
                $this->client = new $clientClass(
                    new $versionClass($urlWithParams, [
                        'sio_path' => '/ws', // Socket.IO path (matches SDK)
                        'context' => [
                            'headers' => [
                                'Client-Id' => $clientId, // Must match clientId query param
                            ],
                            // SSL: Let ElephantIO handle SSL verification (matches SDK behavior)
                            // SDK doesn't disable SSL verification
                        ],
                    ])
                );

                // Connect to server (matches SDK's connect() call)
                $this->client->connect();
                
                // Wait a moment for connection to establish (SDK waits for 'connect' event)
                usleep(200000); // 0.2 seconds
                
                // Verify connection is established
                if ($this->client->isConnected()) {
                    $this->connected = true;
                    
                    Log::info('MetaAPI Socket.IO connected successfully', [
                        'account_id' => $this->accountId,
                        'client_id' => $clientId,
                        'url' => $baseUrl,
                        'region' => $this->region,
                        'attempt' => $attempt + 1,
                    ]);
                    
                    // Subscribe to account first (required before market data subscriptions)
                    // SDK does this after connect event
                    $this->subscribeToAccount();
                    
                    // Process any immediate responses
                    $this->processPendingResponses(5);
                    
                    return true;
                } else {
                    throw new \Exception('Connection established but isConnected() returned false');
                }
                
            } catch (\Exception $e) {
                $this->connected = false;
                $this->client = null;
                
                $errorMsg = $e->getMessage();
                $errorLower = strtolower($errorMsg);
                
                // Check for specific error types
                $isHandshakeError = strpos($errorLower, 'handshake') !== false || 
                                   strpos($errorLower, 'unable to perform') !== false ||
                                   strpos($errorLower, 'connection') !== false && strpos($errorLower, 'establish') !== false;
                
                Log::warning('MetaAPI Socket.IO connection attempt failed', [
                    'account_id' => $this->accountId,
                    'attempt' => $attempt + 1,
                    'max_attempts' => $maxRetries,
                    'error' => $errorMsg,
                    'is_handshake_error' => $isHandshakeError,
                    'url' => $baseUrl,
                    'region' => $this->region,
                ]);
                
                // If handshake error, it might be a library issue - fall back immediately
                if ($isHandshakeError && $attempt >= 2) {
                    Log::error('MetaAPI Socket.IO handshake failed repeatedly, falling back to REST API polling', [
                        'account_id' => $this->accountId,
                        'attempt' => $attempt + 1,
                    ]);
                    return $this->enablePollingFallback();
                }
                
                // If this was the last attempt, fall back to polling
                if ($attempt === $maxRetries - 1) {
                    Log::error('All MetaAPI Socket.IO connection attempts failed, falling back to REST API polling', [
                        'account_id' => $this->accountId,
                        'final_error' => $errorMsg,
                    ]);
                    return $this->enablePollingFallback();
                }
                
                // Wait before retry (exponential backoff like SDK)
                sleep($retryDelay);
                $retryDelay = min($retryDelay * 2, 5); // Max 5 seconds
            }
        }
        
        return $this->enablePollingFallback();
    }

    /**
     * Setup Socket.IO event listeners
     * 
     * Note: ElephantIO doesn't have an on() method like JavaScript SDK
     * We use wait() in a polling loop instead (handled in processMessages/receiveMessages)
     */
    protected function setupListeners(): void
    {
        // ElephantIO doesn't support event listeners like JavaScript
        // Messages are received via wait() method in polling loop
        // This method is kept for compatibility but does nothing
        Log::info('ElephantIO uses polling via wait() method, not event listeners');
    }

    /**
     * Subscribe to account (required before subscribing to market data)
     * 
     * Matches SDK's subscribe request format exactly
     */
    protected function subscribeToAccount(): void
    {
        if (!$this->connected || !$this->client || $this->accountSubscribed) {
            return;
        }

        try {
            // Generate request ID (32 char hex string, matches SDK's random_id())
            $requestId = bin2hex(random_bytes(16));
            
            // Subscribe to account (matches SDK format exactly)
            // SDK includes sessionId in subscribe request if available
            $request = [
                'type' => 'subscribe',
                'accountId' => $this->accountId,
                'requestId' => $requestId,
                'application' => 'MetaApi',
            ];

            // Include sessionId if we have one (matches SDK behavior)
            if ($this->sessionId) {
                $request['sessionId'] = $this->sessionId;
            }

            // Store request resolver (matches SDK's requestResolves pattern)
            $this->requestResolves[$requestId] = [
                'type' => 'subscribe',
                'resolve' => function($response) {
                    // Update sessionId from response (critical for SDK compatibility)
                    if (isset($response['sessionId']) && $response['sessionId']) {
                        $this->sessionId = $response['sessionId'];
                    }
                    $this->accountSubscribed = true;
                    Log::info('Account subscribed to MetaAPI', [
                        'account_id' => $this->accountId,
                        'session_id' => $this->sessionId,
                    ]);
                },
            ];

            // Send request via Socket.IO (matches SDK's emit('request', request))
            $this->client->emit('request', $request);
            
            Log::debug('Sent subscribe request', [
                'account_id' => $this->accountId,
                'request_id' => $requestId,
                'has_session_id' => !empty($this->sessionId),
            ]);
            
            // Wait for response (SDK waits for response event)
            // Process responses with timeout matching SDK's request_timeout
            $responseReceived = false;
            $maxWaitTime = 10; // seconds (matching SDK's default requestTimeout)
            $startTime = time();
            
            while (!$responseReceived && (time() - $startTime) < $maxWaitTime) {
                $this->processPendingResponses(5);
                
                // Check if subscription was confirmed
                if ($this->accountSubscribed) {
                    $responseReceived = true;
                    break;
                }
                
                usleep(200000); // 0.2 seconds
            }
            
            if (!$this->accountSubscribed) {
                Log::warning('Subscribe request sent but no response received within timeout', [
                    'account_id' => $this->accountId,
                    'request_id' => $requestId,
                    'waited_seconds' => time() - $startTime,
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to account', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Subscribe to market data for symbol/timeframe
     * 
     * @param string $symbol Trading symbol (e.g., 'EURUSD', 'GBPUSD')
     * @param string $timeframe Timeframe (e.g., '1h', '4h', '1d') or 'quotes' for quotes, 'ticks' for ticks
     */
    public function subscribe(string $symbol, string $timeframe): bool
    {
        // If in polling mode, just mark as subscribed (data will be polled separately)
        if ($this->usePollingFallback) {
            $key = $symbol . ':' . $timeframe;
            if (!in_array($key, $this->subscribedSymbols)) {
                $this->subscribedSymbols[] = $key;
            }
            return true;
        }
        
        if (!$this->connected || !$this->client) {
            if (!$this->connect()) {
                return false;
            }
        }

        // Wait for account subscription (matches SDK's wait pattern)
        if (!$this->accountSubscribed) {
            // Wait for account subscription to complete (SDK waits for response)
            $maxWaitTime = 10; // seconds
            $startTime = time();
            $attempts = 0;
            
            while (!$this->accountSubscribed && (time() - $startTime) < $maxWaitTime) {
                // Process any pending responses
                $this->processPendingResponses(3);
                
                if (!$this->accountSubscribed) {
                    usleep(500000); // 0.5 seconds
                    $attempts++;
                }
            }
            
            if (!$this->accountSubscribed) {
                Log::error('Account not subscribed yet, cannot subscribe to market data', [
                    'account_id' => $this->accountId,
                    'waited_seconds' => time() - $startTime,
                    'attempts' => $attempts,
                ]);
                return false;
            }
        }

        try {
            $key = $symbol . ':' . $timeframe;
            
            // Determine subscription type based on timeframe
            $subscriptions = [];
            
            if ($timeframe === 'quotes' || $timeframe === 'quote') {
                $subscriptions[] = ['type' => 'quotes'];
            } elseif ($timeframe === 'ticks' || $timeframe === 'tick') {
                $subscriptions[] = ['type' => 'ticks'];
            } else {
                // Convert timeframe to MetaAPI format (e.g., 'H1' -> '1h', 'M15' -> '15m')
                $metaApiTimeframe = $this->convertTimeframeToMetaApi($timeframe);
                $subscriptions[] = [
                    'type' => 'candles',
                    'timeframe' => $metaApiTimeframe,
                ];
            }

            // Generate request ID (32 char hex string, matches SDK's random_id())
            $requestId = bin2hex(random_bytes(16));
            
            // Subscribe to market data (matches SDK format exactly)
            $request = [
                'type' => 'subscribeToMarketData',
                'accountId' => $this->accountId,
                'symbol' => $symbol,
                'subscriptions' => $subscriptions,
                'requestId' => $requestId,
                'application' => 'MetaApi',
            ];
            
            // Note: SDK doesn't include sessionId in market data subscriptions
            // Only in initial subscribe request

            // Store request resolver
            $this->requestResolves[$requestId] = [
                'type' => 'subscribeToMarketData',
                'resolve' => function($response) use ($symbol, $timeframe) {
                    Log::info('Subscribed to MetaAPI market data', [
                        'account_id' => $this->accountId,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                    ]);
                },
            ];

            // Send request via Socket.IO (matches SDK's emit('request', request))
            $this->client->emit('request', $request);
            $this->subscribedSymbols[$key] = true;
            
            Log::debug('Sent subscribeToMarketData request', [
                'account_id' => $this->accountId,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'request_id' => $requestId,
            ]);
            
            // Process responses (SDK waits for response event)
            $this->processPendingResponses(5);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to market data', [
                'account_id' => $this->accountId,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Unsubscribe from market data
     */
    public function unsubscribe(string $symbol, string $timeframe): bool
    {
        if (!$this->connected || !$this->client || !$this->accountSubscribed) {
            return false;
        }

        try {
            $key = $symbol . ':' . $timeframe;
            
            // Determine subscription type
            $subscriptions = [];
            
            if ($timeframe === 'quotes' || $timeframe === 'quote') {
                $subscriptions[] = ['type' => 'quotes'];
            } elseif ($timeframe === 'ticks' || $timeframe === 'tick') {
                $subscriptions[] = ['type' => 'ticks'];
            } else {
                $metaApiTimeframe = $this->convertTimeframeToMetaApi($timeframe);
                $subscriptions[] = [
                    'type' => 'candles',
                    'timeframe' => $metaApiTimeframe, // Include timeframe for candles
                ];
            }

            // Generate request ID (32 char hex string, matches SDK's random_id())
            $requestId = bin2hex(random_bytes(16));
            
            // Unsubscribe from market data (matches SDK format exactly)
            $request = [
                'type' => 'unsubscribeFromMarketData',
                'accountId' => $this->accountId,
                'symbol' => $symbol,
                'subscriptions' => $subscriptions,
                'requestId' => $requestId,
                'application' => 'MetaApi',
            ];

            // Send request via Socket.IO (matches SDK's emit('request', request))
            $this->client->emit('request', $request);
            unset($this->subscribedSymbols[$key]);
            
            Log::info('Unsubscribed from MetaAPI market data', [
                'account_id' => $this->accountId,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe from market data', [
                'account_id' => $this->accountId,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle synchronization packets (market data updates)
     * 
     * Called when Socket.IO receives a 'synchronization' event
     */
    protected function handleSynchronizationPacket($data): void
    {
        // Convert to array if needed
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        
        if (!is_array($data) || !isset($data['type'])) {
            return;
        }

        // Handle "prices" packet type (contains prices, candles, ticks arrays)
        if ($data['type'] === 'prices') {
            $this->handlePricesPacket($data);
        }
        // Handle "authenticated" packet (account subscription confirmed)
        elseif ($data['type'] === 'authenticated') {
            if (isset($data['sessionId'])) {
                $this->sessionId = $data['sessionId'];
            }
            $this->accountSubscribed = true;
            
            Log::info('Account authenticated on MetaAPI Socket.IO', [
                'account_id' => $this->accountId,
                'session_id' => $this->sessionId,
            ]);
        }
        // Handle "update" packet (account information, positions, orders updates)
        elseif ($data['type'] === 'update') {
            // Account info, positions, orders updates - we can log but don't need to process for market data
            // This is handled by other parts of the system
        }
        // Handle other packet types as needed
    }

    /**
     * Handle "prices" packet (contains prices, candles, ticks arrays)
     */
    protected function handlePricesPacket(array $data): void
    {
        $prices = $data['prices'] ?? [];
        $candles = $data['candles'] ?? [];
        $ticks = $data['ticks'] ?? [];

        // Process prices (quotes)
        foreach ($prices as $price) {
            if (isset($price['symbol'])) {
                $symbol = $price['symbol'];
                $cacheKey = $this->getCacheKey($symbol, 'quote');
                Redis::setex($cacheKey, $this->streamTtl, json_encode($price));
            }
        }

        // Process candles
        foreach ($candles as $candle) {
            if (isset($candle['symbol']) && isset($candle['timeframe'])) {
                $symbol = $candle['symbol'];
                $timeframe = $candle['timeframe'];
                $cacheKey = $this->getCacheKey($symbol, $timeframe);
                
                // Store candle data in Redis (for real-time consumption)
                Redis::setex($cacheKey, $this->streamTtl, json_encode($candle));
                
                // Also store in market_data table for historical data and indicator calculation
                $this->storeCandleInDatabase($candle);
            }
        }

        // Process ticks
        foreach ($ticks as $tick) {
            if (isset($tick['symbol'])) {
                $symbol = $tick['symbol'];
                $cacheKey = $this->getCacheKey($symbol, 'tick');
                Redis::setex($cacheKey, $this->streamTtl, json_encode($tick));
            }
        }
    }

    /**
     * Handle response packets (for RPC requests)
     * 
     * Called when Socket.IO receives a 'response' event
     * Matches SDK's on_response handler behavior
     */
    protected function handleResponsePacket($data): void
    {
        // Convert to array if needed
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        
        if (!is_array($data)) {
            return;
        }
        
        // Log response for debugging (matches SDK behavior)
        if (isset($data['requestId'])) {
            Log::debug('MetaAPI response received', [
                'account_id' => $this->accountId,
                'request_id' => $data['requestId'],
                'type' => $data['type'] ?? null,
            ]);
        }
        
        // Handle request ID matching (matches SDK's requestResolves pattern)
        if (isset($data['requestId']) && isset($this->requestResolves[$data['requestId']])) {
            $resolver = $this->requestResolves[$data['requestId']];
            unset($this->requestResolves[$data['requestId']]);
            
            if (isset($resolver['resolve']) && is_callable($resolver['resolve'])) {
                try {
                    $resolver['resolve']($data);
                } catch (\Exception $e) {
                    Log::error('Error in response resolver', [
                        'request_id' => $data['requestId'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        // Handle sessionId from subscribe response (critical for SDK compatibility)
        if (isset($data['sessionId']) && $data['sessionId']) {
            $this->sessionId = $data['sessionId'];
            Log::info('Session ID updated from response', [
                'account_id' => $this->accountId,
                'session_id' => $this->sessionId,
            ]);
        }
        
        // Handle errors in response
        if (isset($data['error'])) {
            Log::error('MetaAPI Socket.IO response error', [
                'account_id' => $this->accountId,
                'error' => $data['error'],
                'message' => $data['message'] ?? null,
                'request_id' => $data['requestId'] ?? null,
            ]);
        }
    }
    
    /**
     * Process pending responses (non-blocking check)
     * 
     * Helps catch immediate responses after sending requests
     */
    protected function processPendingResponses(int $maxAttempts = 5): void
    {
        if (!$this->connected || !$this->client) {
            return;
        }
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            try {
                $packet = $this->client->wait(null, 0.1); // 100ms timeout
                if ($packet) {
                    $event = $packet->event ?? null;
                    $data = $packet->data ?? $packet->args ?? [];
                    
                    if (is_array($data) && count($data) > 0 && is_array($data[0])) {
                        $data = $data[0];
                    }
                    
                    if ($event === 'response') {
                        $this->handleResponsePacket($data);
                    } elseif ($event === 'synchronization') {
                        $this->handleSynchronizationPacket($data);
                    }
                } else {
                    break; // No more messages
                }
            } catch (\Exception $e) {
                // Timeout is expected, break
                break;
            }
        }
    }

    /**
     * Wait for and receive messages
     * 
     * Uses ElephantIO's wait() method to poll for incoming messages
     * 
     * @param int $timeoutSeconds Timeout in seconds
     * @return array|null Message data or null if timeout/no message
     */
    public function waitForMessages(int $timeoutSeconds = 1): ?array
    {
        if (!$this->connected || !$this->client) {
            return null;
        }

        try {
            // Use ElephantIO's wait() method to receive messages
            // Pass null as event name to wait for any event
            $packet = $this->client->wait(null, $timeoutSeconds);
            
            if ($packet) {
                // Process the packet
                // Packet properties: event, data, args
                $event = $packet->event ?? null;
                $data = $packet->data ?? $packet->args ?? [];
                
                // If data is an array with first element, use that
                if (is_array($data) && count($data) > 0 && is_array($data[0])) {
                    $data = $data[0];
                }
                
                if ($event === 'synchronization') {
                    $this->handleSynchronizationPacket($data);
                } elseif ($event === 'response') {
                    $this->handleResponsePacket($data);
                } else {
                    // Unknown event, log it
                    Log::debug('Received Socket.IO event', [
                        'account_id' => $this->accountId,
                        'event' => $event,
                        'has_data' => !empty($data),
                    ]);
                }
                
                return [
                    'event' => $event,
                    'data' => $data,
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            // Timeout or connection error - this is normal, don't log as error
            if (strpos($e->getMessage(), 'timeout') === false && 
                strpos($e->getMessage(), 'Connection') === false) {
                Log::warning('Error waiting for messages', [
                    'account_id' => $this->accountId,
                    'error' => $e->getMessage(),
                ]);
            }
            return null;
        }
    }

    /**
     * Convert timeframe to MetaAPI format
     * 
     * @param string $timeframe Standard format (H1, M15, D1, etc.)
     * @return string MetaAPI format (1h, 15m, 1d, etc.)
     */
    protected function convertTimeframeToMetaApi(string $timeframe): string
    {
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

        return $mapping[strtoupper($timeframe)] ?? $timeframe;
    }

    /**
     * Disconnect from Socket.IO
     */
    public function disconnect(): void
    {
        if ($this->client) {
            try {
                $this->client->disconnect();
            } catch (\Exception $e) {
                Log::warning('Error disconnecting Socket.IO', ['error' => $e->getMessage()]);
            }
        }
        $this->connected = false;
        $this->accountSubscribed = false;
        $this->client = null;
    }

    /**
     * Enable polling fallback mode (REST API instead of Socket.IO)
     * 
     * This matches the SDK's behavior when WebSocket connection fails
     */
    protected function enablePollingFallback(): bool
    {
        if ($this->usePollingFallback) {
            return true; // Already enabled
        }
        
        try {
            Log::info('Enabling MetaAPI REST API polling fallback', [
                'account_id' => $this->accountId,
            ]);
            
            $this->usePollingFallback = true;
            
            // Initialize adapter with correct region
            if (!$this->pollingAdapter) {
                $baseUrl = "https://mt-client-api-v1.{$this->region}.agiliumtrade.ai";
                $marketDataBaseUrl = "https://mt-market-data-client-api-v1.{$this->region}.agiliumtrade.ai";
                
                $this->pollingAdapter = new MetaApiAdapter([
                    'api_token' => $this->apiToken,
                    'account_id' => $this->accountId,
                    'base_url' => $baseUrl,
                    'market_data_base_url' => $marketDataBaseUrl,
                ]);
            }
            
            // Test connection with retry (matching SDK retry logic)
            $maxRetries = 3;
            for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
                try {
                    if ($this->pollingAdapter->connect([])) {
                        $this->connected = true;
                        Log::info('MetaAPI polling fallback enabled successfully (REST API mode)', [
                            'account_id' => $this->accountId,
                            'region' => $this->region,
                            'polling_interval' => $this->pollingInterval . 's',
                        ]);
                        return true;
                    }
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    
                    // Check if error is about account not connected or wrong region
                    if (strpos($errorMsg, 'not connected to broker') !== false) {
                        Log::error('MetaAPI account not connected to broker', [
                            'account_id' => $this->accountId,
                            'region' => $this->region,
                            'attempt' => $attempt + 1,
                        ]);
                        // Don't retry if account not connected
                        break;
                    }
                    
                    // Check if it's a region mismatch error
                    if (strpos($errorMsg, 'does not match the account region') !== false) {
                        // Try to determine correct region
                        $this->fetchAccountRegion();
                        
                        // Recreate adapter with new region
                        $baseUrl = "https://mt-client-api-v1.{$this->region}.agiliumtrade.ai";
                        $marketDataBaseUrl = "https://mt-market-data-client-api-v1.{$this->region}.agiliumtrade.ai";
                        
                        $this->pollingAdapter = new MetaApiAdapter([
                            'api_token' => $this->apiToken,
                            'account_id' => $this->accountId,
                            'base_url' => $baseUrl,
                            'market_data_base_url' => $marketDataBaseUrl,
                        ]);
                    }
                    
                    if ($attempt === $maxRetries - 1) {
                        throw $e;
                    }
                    Log::warning('Polling adapter connection attempt failed, retrying', [
                        'account_id' => $this->accountId,
                        'attempt' => $attempt + 1,
                        'error' => $errorMsg,
                    ]);
                    sleep(1);
                }
            }
            
            Log::error('Failed to enable MetaAPI polling fallback after retries', [
                'account_id' => $this->accountId,
                'region' => $this->region,
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Failed to enable MetaAPI polling fallback', [
                'account_id' => $this->accountId,
                'region' => $this->region,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->usePollingFallback = false;
            $this->connected = false;
            return false;
        }
    }

    /**
     * Poll market data via REST API (fallback mode)
     * 
     * @param string $symbol Trading symbol
     * @param string $timeframe Timeframe
     * @return bool Success
     */
    public function pollMarketData(string $symbol, string $timeframe): bool
    {
        if (!$this->usePollingFallback || !$this->pollingAdapter) {
            Log::debug('Polling not available', [
                'account_id' => $this->accountId,
                'use_polling_fallback' => $this->usePollingFallback,
                'has_polling_adapter' => !is_null($this->pollingAdapter),
            ]);
            return false;
        }

        try {
            // MetaApiAdapter expects standard timeframe format (M1, M5, H1, etc.)
            // Convert from our format (5m, 1h) to standard (M5, H1)
            $standardTimeframe = $this->convertToStandardTimeframe($timeframe);
            
            Log::debug('Polling market data', [
                'account_id' => $this->accountId,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'standard_timeframe' => $standardTimeframe,
            ]);
            
            // Fetch latest candles (limit 100 to have enough for technical indicators)
            // Technical indicators need multiple candles (SMA needs period, RSI needs period+1, etc.)
            $candles = $this->pollingAdapter->fetchOHLCV($symbol, $standardTimeframe, 100);
            
            if (!empty($candles)) {
                $latestCandle = $candles[0];
                
                // Ensure we have the required candle fields
                // MetaApiAdapter returns normalized OHLCV, convert back to MetaAPI format
                $candleData = [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'open' => $latestCandle['open'] ?? 0,
                    'high' => $latestCandle['high'] ?? 0,
                    'low' => $latestCandle['low'] ?? 0,
                    'close' => $latestCandle['close'] ?? 0,
                    'volume' => $latestCandle['volume'] ?? 0,
                    'tickVolume' => $latestCandle['tick_volume'] ?? $latestCandle['volume'] ?? 0,
                    'time' => isset($latestCandle['timestamp']) 
                        ? date('c', (int)($latestCandle['timestamp'] / 1000))
                        : now()->toIso8601String(),
                    'brokerTime' => isset($latestCandle['broker_time']) 
                        ? $latestCandle['broker_time']
                        : now()->toIso8601String(),
                ];
                
                // Cache the data in same format as Socket.IO would (JSON string)
                $cacheKey = $this->getCacheKey($symbol, $timeframe);
                Redis::setex($cacheKey, $this->streamTtl, json_encode($candleData));
                
                Log::info('Polled market data via REST API and cached', [
                    'account_id' => $this->accountId,
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'cache_key' => $cacheKey,
                    'close_price' => $candleData['close'],
                    'timestamp' => $candleData['time'],
                ]);
                
                return true;
            } else {
                Log::warning('Polled market data but got empty result', [
                    'account_id' => $this->accountId,
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'standard_timeframe' => $standardTimeframe,
                ]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $isSymbolNotFound = strpos($errorMessage, 'symbol not defined') !== false || 
                                strpos($errorMessage, '404') !== false ||
                                strpos($errorMessage, 'not found') !== false;
            
            Log::warning('Failed to poll market data', [
                'account_id' => $this->accountId,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'error' => $errorMessage,
                'error_type' => get_class($e),
                'is_symbol_not_found' => $isSymbolNotFound,
                'trace' => $isSymbolNotFound ? null : $e->getTraceAsString(), // Only log trace for non-404 errors
            ]);
            
            // If symbol not found, try alternative symbol formats
            if ($isSymbolNotFound) {
                $alternatives = $this->getAlternativeSymbolFormats($symbol);
                foreach ($alternatives as $altSymbol) {
                    try {
                        $standardTimeframe = $this->convertToStandardTimeframe($timeframe);
                        $candles = $this->pollingAdapter->fetchOHLCV($altSymbol, $standardTimeframe, 1);
                        
                        if (!empty($candles)) {
                            $latestCandle = $candles[0];
                            $candleData = [
                                'symbol' => $symbol, // Keep original symbol in cache
                                'timeframe' => $timeframe,
                                'open' => $latestCandle['open'] ?? 0,
                                'high' => $latestCandle['high'] ?? 0,
                                'low' => $latestCandle['low'] ?? 0,
                                'close' => $latestCandle['close'] ?? 0,
                                'volume' => $latestCandle['volume'] ?? 0,
                                'tickVolume' => $latestCandle['tick_volume'] ?? $latestCandle['volume'] ?? 0,
                                'time' => isset($latestCandle['timestamp']) 
                                    ? date('c', (int)($latestCandle['timestamp'] / 1000))
                                    : now()->toIso8601String(),
                                'brokerTime' => isset($latestCandle['broker_time']) 
                                    ? $latestCandle['broker_time']
                                    : now()->toIso8601String(),
                            ];
                            
                            $cacheKey = $this->getCacheKey($symbol, $timeframe);
                            Redis::setex($cacheKey, $this->streamTtl, json_encode($candleData));
                            
                            Log::info('Polled market data using alternative symbol format', [
                                'account_id' => $this->accountId,
                                'original_symbol' => $symbol,
                                'alternative_symbol' => $altSymbol,
                                'timeframe' => $timeframe,
                                'cache_key' => $cacheKey,
                            ]);
                            
                            return true;
                        }
                    } catch (\Exception $altE) {
                        // Continue trying other alternatives
                        continue;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get alternative symbol formats to try if primary symbol fails
     */
    protected function getAlternativeSymbolFormats(string $symbol): array
    {
        $alternatives = [];
        
        // Common symbol format variations
        $upper = strtoupper($symbol);
        $lower = strtolower($symbol);
        
        // If symbol contains slash, try without slash
        if (strpos($symbol, '/') !== false) {
            $alternatives[] = str_replace('/', '', $symbol);
            $alternatives[] = str_replace('/', '', $upper);
        }
        
        // If symbol doesn't contain slash, try with slash
        if (strpos($symbol, '/') === false) {
            // Try common pair formats
            if (strlen($symbol) === 6) {
                // EURUSD -> EUR/USD
                $alternatives[] = substr($symbol, 0, 3) . '/' . substr($symbol, 3, 3);
            }
        }
        
        // XAUUSDC -> XAUUSD, XAUUSDc, XAUUSDm
        if (strpos($upper, 'XAUUSDC') !== false) {
            $alternatives[] = 'XAUUSD';
            $alternatives[] = 'XAUUSDc';
            $alternatives[] = 'XAUUSDm';
            $alternatives[] = 'GOLD';
        }
        
        // XAUUSD -> XAUUSDc, XAUUSDm, XAUUSDC
        if (strpos($upper, 'XAUUSD') !== false && strpos($upper, 'XAUUSDC') === false) {
            $alternatives[] = 'XAUUSDc';
            $alternatives[] = 'XAUUSDm';
            $alternatives[] = 'XAUUSDC';
            $alternatives[] = 'GOLD';
        }
        
        // Remove duplicates
        return array_unique($alternatives);
    }

    /**
     * Check if using polling fallback
     */
    public function isPollingMode(): bool
    {
        return $this->usePollingFallback;
    }

    /**
     * Convert timeframe to standard format (M1, M5, H1, etc.)
     * Used for MetaApiAdapter which expects standard format
     */
    protected function convertToStandardTimeframe(string $timeframe): string
    {
        // Reverse mapping: from MetaAPI format (1h, 5m) to standard (H1, M5)
        $mapping = [
            '1m' => 'M1', '2m' => 'M2', '3m' => 'M3', '4m' => 'M4', '5m' => 'M5',
            '6m' => 'M6', '10m' => 'M10', '12m' => 'M12', '15m' => 'M15', '20m' => 'M20', '30m' => 'M30',
            '1h' => 'H1', '2h' => 'H2', '3h' => 'H3', '4h' => 'H4', '6h' => 'H6', '8h' => 'H8', '12h' => 'H12',
            '1d' => 'D1', '1w' => 'W1', '1mn' => 'MN',
        ];
        
        $lower = strtolower($timeframe);
        if (isset($mapping[$lower])) {
            return $mapping[$lower];
        }
        
        // If already in standard format, return as-is
        if (preg_match('/^[MHDW][0-9]+$/', strtoupper($timeframe))) {
            return strtoupper($timeframe);
        }
        
        // Default fallback
        return 'H1';
    }

    /**
     * Get cache key for symbol/timeframe
     */
    protected function getCacheKey(string $symbol, string $timeframe): string
    {
        return sprintf('%s:%s:%s:%s', $this->redisPrefix, $this->accountId, $symbol, $timeframe);
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected && $this->accountSubscribed;
    }

    /**
     * Get subscribed symbols
     */
    public function getSubscribedSymbols(): array
    {
        return array_keys($this->subscribedSymbols);
    }

    /**
     * Set region for connection
     * 
     * @param string $region Region name (e.g., 'london', 'new-york', 'tokyo')
     */
    public function setRegion(string $region): void
    {
        $this->region = $region;
        // Clear URL cache to force refresh
        $this->urlSettings = null;
        $this->urlSettingsCacheTime = 0;
    }

    /**
     * Get current region
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * Get current WebSocket URL
     */
    public function getWebsocketUrl(): ?string
    {
        return $this->websocketUrl;
    }

    /**
     * Get URL settings (domain, hostname, url)
     */
    public function getUrlSettings(): ?array
    {
        return $this->urlSettings;
    }

    /**
     * Store candle in database via MarketDataService
     */
    protected function storeCandleInDatabase(array $candle): void
    {
        try {
            // Get data connection for this account
            $dataConnection = \Addons\TradingManagement\Modules\DataProvider\Models\DataConnection::where('credentials->account_id', $this->accountId)
                ->where('status', 'active')
                ->first();

            if (!$dataConnection) {
                return; // No data connection found for this account
            }

            // Convert MetaAPI candle format to OHLCV array
            $timestamp = $this->parseTimestamp($candle['time'] ?? $candle['brokerTime'] ?? null);
            if (!$timestamp) {
                return; // Invalid timestamp
            }

            $ohlcv = [
                'timestamp' => $timestamp,
                'open' => (float) ($candle['open'] ?? 0),
                'high' => (float) ($candle['high'] ?? 0),
                'low' => (float) ($candle['low'] ?? 0),
                'close' => (float) ($candle['close'] ?? 0),
                'volume' => (int) ($candle['volume'] ?? $candle['tickVolume'] ?? 0),
            ];

            // Store via MarketDataService
            $marketDataService = app(\Addons\TradingManagement\Modules\MarketData\Services\MarketDataService::class);
            $marketDataService->store($dataConnection, $candle['symbol'], $candle['timeframe'], [$ohlcv]);
        } catch (\Exception $e) {
            Log::warning('Failed to store candle in database', [
                'account_id' => $this->accountId,
                'symbol' => $candle['symbol'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parse timestamp from MetaAPI format
     */
    protected function parseTimestamp($time): ?int
    {
        if (!$time) {
            return null;
        }

        if (is_numeric($time)) {
            // Already a timestamp (milliseconds or seconds)
            return $time < 10000000000 ? $time * 1000 : $time;
        }

        if (is_string($time)) {
            // ISO 8601 string
            $timestamp = strtotime($time);
            return $timestamp ? $timestamp * 1000 : null;
        }

        return null;
    }
}
