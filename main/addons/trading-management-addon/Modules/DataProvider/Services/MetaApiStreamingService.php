<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version4X;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

/**
 * MetaAPI Streaming Service
 * 
 * Handles Socket.IO connection to MetaAPI for real-time market data streaming
 * Based on MetaAPI JavaScript SDK protocol (metacloudws.js)
 * 
 * Protocol Details (from MetaAPI JavaScript SDK):
 * - Uses Socket.IO client (not raw WebSocket)
 * - First subscribe to account: { type: "subscribe", accountId, sessionId }
 * - Then subscribe to market data: { type: "subscribeToMarketData", symbol, subscriptions: [{ type: "quotes" }, { type: "candles", timeframe: "1h" }] }
 * - Unsubscribe: { type: "unsubscribeFromMarketData", symbol, subscriptions: [{ type: "candles" }] }
 * - Receive "prices" packets: { type: "prices", prices: [...], candles: [...], ticks: [...] }
 * - Candles format: { symbol, timeframe, time, brokerTime, open, high, low, close, tickVolume, spread, volume }
 * - Prices format: { symbol, bid, ask, time, brokerTime, ... }
 * - Ticks format: { symbol, time, brokerTime, bid, ask, last, volume, side }
 */
class MetaApiStreamingService
{
    protected ?Client $client = null;
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

    public function __construct(string $apiToken, string $accountId)
    {
        $this->apiToken = $apiToken;
        $this->accountId = $accountId;
        
        // MetaAPI uses Socket.IO, not raw WebSocket
        // URL format: https://{hostname}.{region}-{instance}.{domain}
        // Note: In JavaScript SDK, URL is retrieved dynamically from domain client
        // For PHP, we use a configurable default URL
        // The actual URL should match your MetaAPI account's region
        $this->websocketUrl = config('trading-management.metaapi.streaming.websocket_url', 'https://mt-client-api-v1.london.agiliumtrade.ai');
        $this->redisPrefix = config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream');
        $this->streamTtl = config('trading-management.metaapi.streaming.stream_ttl', 60);
    }

    /**
     * Connect to MetaAPI Socket.IO server
     * 
     * Note: ElephantIO library capabilities may vary. For long-running connections,
     * you may need to:
     * 1. Keep connection alive in a loop
     * 2. Periodically check for new messages
     * 3. Handle reconnection automatically
     */
    public function connect(): bool
    {
        try {
            // Use Socket.IO v4 client (or v2/v3 if v4 not supported)
            // MetaAPI uses Socket.IO protocol
            $this->client = new Client(
                new Version4X($this->websocketUrl, [
                    'query' => [
                        'auth-token' => $this->apiToken,
                    ],
                    'extraHeaders' => [
                        'auth-token' => $this->apiToken,
                    ],
                ])
            );

            $this->client->initialize();
            $this->connected = true;
            
            Log::info('MetaAPI Socket.IO connected', [
                'account_id' => $this->accountId,
            ]);
            
            // Setup message listeners (if supported by library)
            $this->setupListeners();
            
            // Subscribe to account first (required before market data subscriptions)
            $this->subscribeToAccount();
            
            return true;
        } catch (\Exception $e) {
            $this->connected = false;
            Log::error('Failed to connect to MetaAPI Socket.IO', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Setup Socket.IO event listeners
     * 
     * Note: ElephantIO's event handling may work differently than JavaScript SDK
     * We set up listeners for 'synchronization' and 'response' events
     */
    protected function setupListeners(): void
    {
        if (!$this->client) {
            return;
        }

        try {
            // Listen for synchronization packets (market data updates)
            // In MetaAPI SDK, these come via 'synchronization' event
            if (method_exists($this->client, 'on')) {
                $this->client->on('synchronization', function ($data) {
                    $this->handleSynchronizationPacket($data);
                });

                // Listen for response packets (RPC responses)
                $this->client->on('response', function ($data) {
                    $this->handleResponsePacket($data);
                });
            } else {
                // If on() method doesn't exist, we'll need to poll manually
                Log::warning('ElephantIO on() method not available, will need polling approach');
            }
        } catch (\Exception $e) {
            Log::warning('Failed to setup event listeners, will use polling', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Subscribe to account (required before subscribing to market data)
     */
    protected function subscribeToAccount(): void
    {
        if (!$this->connected || !$this->client || $this->accountSubscribed) {
            return;
        }

        try {
            // Generate request ID
            $requestId = bin2hex(random_bytes(16));
            
            // Subscribe to account
            $request = [
                'type' => 'subscribe',
                'accountId' => $this->accountId,
                'requestId' => $requestId,
                'application' => 'MetaApi',
            ];

            if ($this->sessionId) {
                $request['sessionId'] = $this->sessionId;
            }

            // Store request resolver
            $this->requestResolves[$requestId] = [
                'type' => 'subscribe',
                'resolve' => function($response) {
                    if (isset($response['sessionId'])) {
                        $this->sessionId = $response['sessionId'];
                    }
                    $this->accountSubscribed = true;
                    Log::info('Account subscribed to MetaAPI', [
                        'account_id' => $this->accountId,
                        'session_id' => $this->sessionId,
                    ]);
                },
            ];

            // Send request via Socket.IO
            $this->client->emit('request', $request);
            
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
        if (!$this->connected || !$this->client) {
            if (!$this->connect()) {
                return false;
            }
        }

        // Wait for account subscription
        if (!$this->accountSubscribed) {
            // Wait a bit for account subscription to complete
            $attempts = 0;
            while (!$this->accountSubscribed && $attempts < 10) {
                usleep(500000); // 0.5 seconds
                $attempts++;
            }
            
            if (!$this->accountSubscribed) {
                Log::error('Account not subscribed yet, cannot subscribe to market data', [
                    'account_id' => $this->accountId,
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

            // Generate request ID
            $requestId = bin2hex(random_bytes(16));
            
            // Subscribe to market data
            $request = [
                'type' => 'subscribeToMarketData',
                'accountId' => $this->accountId,
                'symbol' => $symbol,
                'subscriptions' => $subscriptions,
                'requestId' => $requestId,
                'application' => 'MetaApi',
            ];

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

            // Send request via Socket.IO
            $this->client->emit('request', $request);
            $this->subscribedSymbols[$key] = true;
            
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

            // Generate request ID
            $requestId = bin2hex(random_bytes(16));
            
            // Unsubscribe from market data
            $request = [
                'type' => 'unsubscribeFromMarketData',
                'accountId' => $this->accountId,
                'symbol' => $symbol,
                'subscriptions' => $subscriptions,
                'requestId' => $requestId,
                'application' => 'MetaApi',
            ];

            // Send request via Socket.IO
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
        
        // Handle errors in response
        if (isset($data['error'])) {
            Log::error('MetaAPI Socket.IO response error', [
                'account_id' => $this->accountId,
                'error' => $data['error'],
                'message' => $data['message'] ?? null,
            ]);
        }
    }

    /**
     * Wait for and receive messages
     * 
     * Note: ElephantIO may require polling or waiting for messages
     * This method attempts to receive messages from the Socket.IO connection
     */
    public function waitForMessages(int $timeoutSeconds = 1): ?array
    {
        if (!$this->connected || !$this->client) {
            return null;
        }

        try {
            // ElephantIO may support waiting for messages
            // The actual implementation depends on the library's capabilities
            // For now, we rely on event listeners set up in setupListeners()
            
            // If the library supports it, we could use:
            // $message = $this->client->wait('synchronization', $timeoutSeconds);
            // But this depends on ElephantIO's API
            
            // Return null to indicate no immediate message
            // Messages are handled via event listeners
            return null;
        } catch (\Exception $e) {
            Log::error('Error waiting for messages', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
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
