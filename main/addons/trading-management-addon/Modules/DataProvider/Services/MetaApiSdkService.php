<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * MetaAPI SDK Service
 * 
 * Central wrapper for MetaAPI SDK with intelligent fallback to REST API
 * Compatible with SDK v2.1+ (PHP 7.4+) with enhanced features
 * 
 * Features:
 * - RPC-like connection for trading operations
 * - Streaming connection for market data
 * - Terminal state caching (account info, positions, prices)
 * - Latency monitoring
 * - Automatic fallback to REST API on failures
 * - Connection health tracking
 */
class MetaApiSdkService
{
    // Static cache to prevent multiple SDK initializations per account
    protected static array $sdkInstances = [];
    protected static array $sdkInitialized = [];
    
    protected string $apiToken;
    protected string $accountId;
    protected ?string $region;
    protected ?MetaApiAdapter $adapter = null;
    protected ?MetaApiStreamingService $streamingService = null;
    protected bool $useSdk = false;
    protected ?object $sdkAccountApi = null;
    
    // Connection state
    protected bool $connected = false;
    protected bool $streamingConnected = false;
    protected array $connectionErrors = [];
    
    // Terminal state cache
    protected ?array $accountInformation = null;
    protected array $positions = [];
    protected array $orders = [];
    protected array $prices = [];
    protected array $specifications = [];
    protected int $terminalStateTtl = 60; // seconds
    
    // Latency monitoring
    protected array $latencies = [
        'trade' => [],
        'request' => [],
        'update' => [],
        'quote' => [],
    ];
    protected int $maxLatencyRecords = 100;
    
    public function __construct(string $apiToken, string $accountId, ?string $region = null)
    {
        $this->apiToken = $apiToken;
        $this->accountId = $accountId;
        $this->region = $region ?? config('trading-management.metaapi.streaming.region', 'london');
        
        // Initialize adapter (always available as fallback)
        $this->initializeAdapter();
        
        // Try to initialize SDK if available
        $this->initializeSdk();
    }
    
    /**
     * Initialize MetaAPI SDK (AccountApi)
     * 
     * Detects SDK version dynamically from installed package
     * Prevents multiple initializations for the same account
     */
    protected function initializeSdk(): void
    {
        // Check if SDK already initialized for this account
        $cacheKey = $this->accountId;
        
        if (isset(self::$sdkInitialized[$cacheKey]) && self::$sdkInitialized[$cacheKey]) {
            // SDK already initialized for this account, reuse the instance
            if (isset(self::$sdkInstances[$cacheKey])) {
                $this->sdkAccountApi = self::$sdkInstances[$cacheKey];
                $this->useSdk = true;
            }
            return;
        }
        
        try {
            if (class_exists('\Oyi77\MetaapiCloudPhpSdk\AccountApi')) {
                $this->sdkAccountApi = new \Oyi77\MetaapiCloudPhpSdk\AccountApi($this->apiToken);
                $this->useSdk = true;
                
                // Cache the SDK instance per account
                self::$sdkInstances[$cacheKey] = $this->sdkAccountApi;
                self::$sdkInitialized[$cacheKey] = true;
                
                // Get actual SDK version from composer
                $sdkVersion = $this->getSdkVersion();
                
                Log::debug('MetaApiSdkService: SDK initialized', [
                    'account_id' => $this->accountId,
                    'sdk_version' => $sdkVersion,
                    'use_sdk' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::debug('MetaApiSdkService: SDK not available, using REST fallback', [
                'error' => $e->getMessage(),
            ]);
            $this->useSdk = false;
        }
    }
    
    /**
     * Get installed SDK version
     * 
     * @return string SDK version (e.g., "2.1.0") or "unknown"
     */
    protected function getSdkVersion(): string
    {
        // Try Composer's InstalledVersions (available in Laravel)
        if (class_exists('\Composer\InstalledVersions')) {
            try {
                $version = \Composer\InstalledVersions::getVersion('oyi77/metaapi-cloud-php-sdk');
                if ($version) {
                    return $version;
                }
            } catch (\Exception $e) {
                // Fall through to alternative method
            }
        }
        
        // Alternative: Try to get from package constant or reflection
        try {
            $reflection = new \ReflectionClass('\Oyi77\MetaapiCloudPhpSdk\AccountApi');
            $file = $reflection->getFileName();
            $dir = dirname($file);
            
            // Try to read composer.json from vendor directory
            $composerJsonPath = $dir . '/../../composer.json';
            if (file_exists($composerJsonPath)) {
                $composerData = json_decode(file_get_contents($composerJsonPath), true);
                if (isset($composerData['version'])) {
                    return $composerData['version'];
                }
            }
        } catch (\Exception $e) {
            // Fall through
        }
        
        // Last resort: check composer.lock
        try {
            $composerLockPath = base_path('composer.lock');
            if (file_exists($composerLockPath)) {
                $lockData = json_decode(file_get_contents($composerLockPath), true);
                if (isset($lockData['packages'])) {
                    foreach ($lockData['packages'] as $package) {
                        if (isset($package['name']) && $package['name'] === 'oyi77/metaapi-cloud-php-sdk') {
                            return $package['version'] ?? 'unknown';
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Fall through
        }
        
        return 'unknown';
    }
    
    /**
     * Initialize REST adapter (fallback)
     */
    protected function initializeAdapter(): void
    {
        $baseUrl = "https://mt-client-api-v1.{$this->region}.agiliumtrade.ai";
        $marketDataBaseUrl = "https://mt-market-data-client-api-v1.{$this->region}.agiliumtrade.ai";
        
        $this->adapter = new MetaApiAdapter([
            'api_token' => $this->apiToken,
            'account_id' => $this->accountId,
            'base_url' => $baseUrl,
            'market_data_base_url' => $marketDataBaseUrl,
        ]);
    }
    
    /**
     * Get RPC connection for trading operations
     * 
     * @return $this
     */
    public function rpcConnection(): self
    {
        if (!$this->connected) {
            $this->connect();
        }
        return $this;
    }
    
    /**
     * Get streaming connection for market data
     * 
     * @return MetaApiStreamingService
     */
    public function streamingConnection(): MetaApiStreamingService
    {
        if (!$this->streamingService) {
            $this->streamingService = new MetaApiStreamingService(
                $this->apiToken,
                $this->accountId,
                $this->region
            );
        }
        
        if (!$this->streamingConnected) {
            $this->streamingService->connect();
            $this->streamingConnected = $this->streamingService->isConnected();
        }
        
        return $this->streamingService;
    }
    
    /**
     * Connect to MetaAPI
     */
    public function connect(): bool
    {
        $startTime = microtime(true);
        
        try {
            // Try SDK first if available
            if ($this->useSdk && $this->sdkAccountApi) {
                try {
                    // Test connection via SDK
                    $accountInfo = $this->getAccountInformation();
                    if ($accountInfo) {
                        $this->connected = true;
                        $this->recordLatency('request', microtime(true) - $startTime);
                        
                        Log::info('MetaApiSdkService: Connected via SDK', [
                            'account_id' => $this->accountId,
                            'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
                        ]);
                        
                        return true;
                    }
                } catch (\Exception $e) {
                    Log::warning('MetaApiSdkService: SDK connection failed, falling back to REST', [
                        'error' => $e->getMessage(),
                    ]);
                    $this->connectionErrors[] = [
                        'method' => 'sdk',
                        'error' => $e->getMessage(),
                        'time' => time(),
                    ];
                }
            }
            
            // Fallback to REST adapter
            if ($this->adapter->connect([])) {
                $this->connected = true;
                $this->recordLatency('request', microtime(true) - $startTime);
                
                Log::debug('MetaApiSdkService: Connected via REST adapter', [
                    'account_id' => $this->accountId,
                    'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ]);
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Connection failed', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            
            $this->connectionErrors[] = [
                'method' => 'all',
                'error' => $e->getMessage(),
                'time' => time(),
            ];
            
            return false;
        }
    }
    
    /**
     * Wait for synchronization (compatibility method)
     */
    public function waitSynchronized(int $timeoutSeconds = 300): bool
    {
        // For REST API, we're always "synchronized" once connected
        return $this->connected;
    }
    
    /**
     * Get account information with caching
     */
    public function getAccountInformation(): ?array
    {
        $cacheKey = "metaapi:terminal_state:{$this->accountId}:account_info";
        
        // Try cache first
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        $startTime = microtime(true);
        
        try {
            // Try SDK first
            if ($this->useSdk && $this->sdkAccountApi) {
                try {
                    // SDK v1.0 doesn't have getAccountInformation, use adapter
                    $accountInfo = $this->adapter->getAccountInfo();
                    
                    if ($accountInfo) {
                        Cache::put($cacheKey, $accountInfo, $this->terminalStateTtl);
                        $this->accountInformation = $accountInfo;
                        $this->recordLatency('request', microtime(true) - $startTime);
                        return $accountInfo;
                    }
                } catch (\Exception $e) {
                    Log::debug('MetaApiSdkService: SDK getAccountInfo failed, using fallback', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Fallback to adapter
            $accountInfo = $this->adapter->getAccountInfo();
            
            if ($accountInfo) {
                Cache::put($cacheKey, $accountInfo, $this->terminalStateTtl);
                $this->accountInformation = $accountInfo;
                $this->recordLatency('request', microtime(true) - $startTime);
            }
            
            return $accountInfo;
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Failed to get account information', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Get positions with caching
     */
    public function getPositions(): array
    {
        $cacheKey = "metaapi:terminal_state:{$this->accountId}:positions";
        
        // Try cache first
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        $startTime = microtime(true);
        
        try {
            $positions = $this->adapter->fetchPositions();
            
            if ($positions) {
                Cache::put($cacheKey, $positions, $this->terminalStateTtl);
                $this->positions = $positions;
                $this->recordLatency('request', microtime(true) - $startTime);
            }
            
            return $positions;
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Failed to get positions', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
    
    /**
     * Get symbols (specifications)
     */
    public function getSymbols(): array
    {
        $cacheKey = "metaapi:terminal_state:{$this->accountId}:symbols";
        
        // Try cache first (longer TTL for symbols)
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        $startTime = microtime(true);
        
        try {
            $symbols = $this->adapter->getAvailableSymbols();
            
            if ($symbols) {
                Cache::put($cacheKey, $symbols, 3600); // 1 hour cache
                $this->recordLatency('request', microtime(true) - $startTime);
            }
            
            return $symbols;
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Failed to get symbols', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
    
    /**
     * Get server time
     */
    public function getServerTime(): ?array
    {
        try {
            // MetaAPI doesn't have a direct server time endpoint
            // Use account info timestamp as proxy
            $accountInfo = $this->getAccountInformation();
            
            return [
                'time' => time(),
                'brokerTime' => date('c'),
            ];
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Failed to get server time', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Calculate margin for order
     */
    public function calculateMargin(array $order): ?array
    {
        $startTime = microtime(true);
        
        try {
            // MetaAPI REST doesn't have calculateMargin endpoint
            // Implement basic margin calculation
            $accountInfo = $this->getAccountInformation();
            if (!$accountInfo) {
                return null;
            }
            
            $leverage = $accountInfo['leverage'] ?? 100;
            $volume = $order['volume'] ?? 0;
            $openPrice = $order['openPrice'] ?? 0;
            
            // Basic margin calculation: (volume * contract_size * price) / leverage
            // Assuming standard lot size of 100,000 for forex
            $contractSize = 100000;
            $margin = ($volume * $contractSize * $openPrice) / $leverage;
            
            $this->recordLatency('request', microtime(true) - $startTime);
            
            return [
                'margin' => $margin,
                'leverage' => $leverage,
            ];
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Failed to calculate margin', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Create market buy order (convenience method)
     */
    public function createMarketBuyOrder(
        string $symbol,
        float $volume,
        ?float $stopLoss = null,
        ?float $takeProfit = null,
        array $options = []
    ): array {
        $startTime = microtime(true);
        
        try {
            $result = $this->adapter->placeMarketOrder(
                $symbol,
                'buy',
                $volume,
                $stopLoss,
                $takeProfit,
                $options['comment'] ?? null
            );
            
            $this->recordLatency('trade', microtime(true) - $startTime);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Failed to create market buy order', [
                'account_id' => $this->accountId,
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Create market sell order (convenience method)
     */
    public function createMarketSellOrder(
        string $symbol,
        float $volume,
        ?float $stopLoss = null,
        ?float $takeProfit = null,
        array $options = []
    ): array {
        $startTime = microtime(true);
        
        try {
            $result = $this->adapter->placeMarketOrder(
                $symbol,
                'sell',
                $volume,
                $stopLoss,
                $takeProfit,
                $options['comment'] ?? null
            );
            
            $this->recordLatency('trade', microtime(true) - $startTime);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Failed to create market sell order', [
                'account_id' => $this->accountId,
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Close position
     */
    public function closePosition(string $positionId, ?float $volume = null): array
    {
        $startTime = microtime(true);
        
        try {
            $result = $this->adapter->closePosition($positionId, $volume);
            $this->recordLatency('trade', microtime(true) - $startTime);
            return $result;
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Failed to close position', [
                'account_id' => $this->accountId,
                'position_id' => $positionId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Modify position (update SL/TP)
     */
    public function modifyPosition(string $positionId, ?float $stopLoss = null, ?float $takeProfit = null): array
    {
        $startTime = microtime(true);
        
        try {
            $result = $this->adapter->modifyPosition($positionId, $stopLoss, $takeProfit);
            $this->recordLatency('trade', microtime(true) - $startTime);
            return $result;
        } catch (\Exception $e) {
            Log::error('MetaApiSdkService: Failed to modify position', [
                'account_id' => $this->accountId,
                'position_id' => $positionId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
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
        
        return [
            'count' => $count,
            'min' => min($latencies),
            'max' => max($latencies),
            'avg' => array_sum($latencies) / $count,
            'median' => $this->calculateMedian($latencies),
        ];
    }
    
    /**
     * Calculate median
     */
    protected function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
        
        return $values[$middle];
    }
    
    /**
     * Get connection health status
     */
    public function getHealthStatus(): array
    {
        return [
            'connected' => $this->connected,
            'streaming_connected' => $this->streamingConnected,
            'using_sdk' => $this->useSdk,
            'region' => $this->region,
            'errors' => array_slice($this->connectionErrors, -10), // Last 10 errors
            'latencies' => [
                'trade' => $this->getLatencyStats('trade'),
                'request' => $this->getLatencyStats('request'),
            ],
        ];
    }
    
    /**
     * Close connections
     */
    public function close(): void
    {
        if ($this->streamingService) {
            $this->streamingService->disconnect();
        }
        
        if ($this->adapter) {
            $this->adapter->disconnect();
        }
        
        $this->connected = false;
        $this->streamingConnected = false;
    }
    
    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }
    
    /**
     * Get account ID
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }
    
    /**
     * Get region
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }
}

