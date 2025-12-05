<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use Grpc\ChannelCredentials;
use Illuminate\Support\Facades\Log;

/**
 * MTAPI gRPC Client Service
 * 
 * Handles gRPC connections to mtapi.io MT5 servers
 * 
 * Note: Proto files need to be generated from mt5.proto
 * Download from: https://git.mtapi.io/root/grpc-proto/-/raw/main/mt5/protos/mt5.proto
 * Generate using: docker run -v $(pwd):/defs namely/protoc-all -f ./mt5.proto -l php -o generated
 */
class MtapiGrpcClient
{
    protected string $baseUrl;
    protected ?string $connectionId = null;
    protected $connectionClient = null;
    protected $mt5Client = null;
    protected $subscriptionsClient = null;
    protected $streamsClient = null;
    protected int $timeout;

    public function __construct(string $baseUrl = 'mt5grpc.mtapi.io:443', int $timeout = 30)
    {
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
    }

    /**
     * Initialize gRPC clients
     * 
     * @return void
     */
    protected function initializeClients(): void
    {
        if ($this->connectionClient !== null) {
            return;
        }

        try {
            // Check if gRPC extension is loaded
            if (!extension_loaded('grpc')) {
                throw new \Exception('gRPC PHP extension is not loaded. Please install it via: pecl install grpc');
            }

            // Load generated proto classes
            $this->loadProtoClasses();

            // Create clients with SSL credentials
            $credentials = ChannelCredentials::createSsl();
            $options = [
                'credentials' => $credentials,
                'timeout' => $this->timeout * 1000, // Convert to milliseconds
            ];

            // Connection client for Connect/Disconnect
            if (class_exists('\Mt5grpc\ConnectionClient')) {
                $this->connectionClient = new \Mt5grpc\ConnectionClient($this->baseUrl, $options);
            }

            // MT5 client for account operations
            if (class_exists('\Mt5grpc\MT5Client')) {
                $this->mt5Client = new \Mt5grpc\MT5Client($this->baseUrl, $options);
            }

            // Subscriptions client
            if (class_exists('\Mt5grpc\SubscriptionsClient')) {
                $this->subscriptionsClient = new \Mt5grpc\SubscriptionsClient($this->baseUrl, $options);
            }

            // Streams client
            if (class_exists('\Mt5grpc\StreamsClient')) {
                $this->streamsClient = new \Mt5grpc\StreamsClient($this->baseUrl, $options);
            }
        } catch (\Exception $e) {
            Log::error('Failed to initialize MTAPI gRPC clients', [
                'error' => $e->getMessage(),
                'base_url' => $this->baseUrl,
            ]);
            throw $e;
        }
    }

    /**
     * Load generated proto classes
     * 
     * @return void
     */
    protected function loadProtoClasses(): void
    {
        $generatedPath = __DIR__ . '/../generated';
        
        // Load metadata
        $metadataFile = $generatedPath . '/GPBMetadata/Mt5.php';
        if (file_exists($metadataFile)) {
            require_once $metadataFile;
        }

        // Load all gRPC client classes
        $grpcPath = $generatedPath . '/Mt5grpc';
        if (is_dir($grpcPath)) {
            $files = glob($grpcPath . '/*.php');
            foreach ($files as $file) {
                require_once $file;
            }
        } else {
            Log::warning('MT5 gRPC generated classes not found. Proto files need to be generated.', [
                'path' => $grpcPath,
            ]);
        }
    }

    /**
     * Connect to MT5 account
     * 
     * @param string $user Account number
     * @param string $password Account password
     * @param string $host Server host
     * @param string|int $port Server port
     * @return string Connection ID (token)
     * @throws \Exception
     */
    public function connect(string $user, string $password, string $host, $port): string
    {
        $this->initializeClients();

        if (!$this->connectionClient || !class_exists('\Mt5grpc\ConnectRequest')) {
            throw new \Exception('gRPC proto classes not loaded. Please generate proto files.');
        }

        try {
            $request = new \Mt5grpc\ConnectRequest();
            $request->setUser((int)$user);
            $request->setPassword($password);
            $request->setHost($host);
            $request->setPort((string)$port);

            list($response, $status) = $this->connectionClient->Connect($request)->wait();

            if ($status->code !== \Grpc\STATUS_OK) {
                throw new \Exception("gRPC connection failed: {$status->details}");
            }

            $error = $response->getError();
            if ($error !== null) {
                throw new \Exception("MT5 connection error: " . $error->getMessage());
            }

            $this->connectionId = $response->getResult();
            
            return $this->connectionId;
        } catch (\Exception $e) {
            Log::error('MTAPI gRPC connect failed', [
                'error' => $e->getMessage(),
                'host' => $host,
                'port' => $port,
            ]);
            throw $e;
        }
    }

    /**
     * Disconnect from MT5 account
     * 
     * @return void
     */
    public function disconnect(): void
    {
        if (!$this->connectionId || !$this->connectionClient) {
            return;
        }

        try {
            if (class_exists('\Mt5grpc\DisconnectRequest')) {
                $request = new \Mt5grpc\DisconnectRequest();
                $request->setId($this->connectionId);
                
                $this->connectionClient->Disconnect($request)->wait();
            }
        } catch (\Exception $e) {
            Log::warning('MTAPI gRPC disconnect failed', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->connectionId = null;
        }
    }

    /**
     * Get account summary
     * 
     * @return array
     * @throws \Exception
     */
    public function getAccountSummary(): array
    {
        if (!$this->connectionId) {
            throw new \Exception('Not connected. Call connect() first.');
        }

        $this->initializeClients();

        if (!$this->mt5Client || !class_exists('\Mt5grpc\AccountSummaryRequest')) {
            throw new \Exception('gRPC proto classes not loaded.');
        }

        try {
            $request = new \Mt5grpc\AccountSummaryRequest();
            $request->setId($this->connectionId);

            list($response, $status) = $this->mt5Client->AccountSummary($request)->wait();

            if ($status->code !== \Grpc\STATUS_OK) {
                throw new \Exception("gRPC request failed: {$status->details}");
            }

            $error = $response->getError();
            if ($error !== null) {
                throw new \Exception("MT5 error: " . $error->getMessage());
            }

            $result = $response->getResult();
            
            return [
                'balance' => $result->getBalance(),
                'equity' => $result->getEquity(),
                'margin' => $result->getMargin(),
                'free_margin' => $result->getFreeMargin(),
                'margin_level' => $result->getMarginLevel(),
                'currency' => $result->getCurrency(),
                'leverage' => $result->getLeverage(),
                'profit' => $result->getProfit(),
                'credit' => $result->getCredit(),
            ];
        } catch (\Exception $e) {
            Log::error('MTAPI gRPC getAccountSummary failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get account details
     * 
     * @return array
     * @throws \Exception
     */
    public function getAccountDetails(): array
    {
        if (!$this->connectionId) {
            throw new \Exception('Not connected. Call connect() first.');
        }

        $this->initializeClients();

        if (!$this->mt5Client || !class_exists('\Mt5grpc\AccountDetailsRequest')) {
            throw new \Exception('gRPC proto classes not loaded.');
        }

        try {
            $request = new \Mt5grpc\AccountDetailsRequest();
            $request->setId($this->connectionId);

            list($response, $status) = $this->mt5Client->AccountDetails($request)->wait();

            if ($status->code !== \Grpc\STATUS_OK) {
                throw new \Exception("gRPC request failed: {$status->details}");
            }

            $error = $response->getError();
            if ($error !== null) {
                throw new \Exception("MT5 error: " . $error->getMessage());
            }

            $result = $response->getResult();
            
            return [
                'account' => $result->getAccount(),
                'name' => $result->getName(),
                'server' => $result->getServer(),
                'server_time' => $result->getServerTime(),
                'server_timezone' => $result->getServerTimezone(),
                'company' => $result->getCompany(),
                'currency' => $result->getCurrency(),
                'margin_stopout' => $result->getMarginStopout(),
                'margin_call' => $result->getMarginCall(),
                'group' => $result->getGroup(),
                'account_type' => $result->getAccountType(),
                'leverage' => $result->getLeverage(),
            ];
        } catch (\Exception $e) {
            Log::error('MTAPI gRPC getAccountDetails failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get available symbols
     * 
     * @return array
     * @throws \Exception
     */
    public function getSymbols(): array
    {
        if (!$this->connectionId) {
            throw new \Exception('Not connected. Call connect() first.');
        }

        $this->initializeClients();

        if (!$this->mt5Client || !class_exists('\Mt5grpc\SymbolsRequest')) {
            throw new \Exception('gRPC proto classes not loaded.');
        }

        try {
            $request = new \Mt5grpc\SymbolsRequest();
            $request->setId($this->connectionId);

            list($response, $status) = $this->mt5Client->Symbols($request)->wait();

            if ($status->code !== \Grpc\STATUS_OK) {
                throw new \Exception("gRPC request failed: {$status->details}");
            }

            $error = $response->getError();
            if ($error !== null) {
                throw new \Exception("MT5 error: " . $error->getMessage());
            }

            $symbols = [];
            $result = $response->getResult();
            
            // Handle different response structures
            if (method_exists($result, 'getSymbols')) {
                $symbolList = $result->getSymbols();
                foreach ($symbolList as $symbol) {
                    $symbols[] = is_string($symbol) ? $symbol : $symbol->getName();
                }
            } elseif (is_array($result)) {
                $symbols = $result;
            }

            return $symbols;
        } catch (\Exception $e) {
            Log::error('MTAPI gRPC getSymbols failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get price history
     * 
     * @param string $symbol
     * @param string $timeframe
     * @param int $limit
     * @param int|null $from
     * @return array
     * @throws \Exception
     */
    public function getPriceHistory(string $symbol, string $timeframe, int $limit = 100, ?int $from = null): array
    {
        if (!$this->connectionId) {
            throw new \Exception('Not connected. Call connect() first.');
        }

        $this->initializeClients();

        if (!$this->mt5Client || !class_exists('\Mt5grpc\PriceHistoryRequest')) {
            throw new \Exception('gRPC proto classes not loaded.');
        }

        try {
            $request = new \Mt5grpc\PriceHistoryRequest();
            $request->setId($this->connectionId);
            $request->setSymbol($symbol);
            $request->setTimeframe($this->convertTimeframe($timeframe));
            $request->setLimit($limit);
            
            if ($from !== null) {
                $request->setFrom($from);
            }

            list($response, $status) = $this->mt5Client->PriceHistory($request)->wait();

            if ($status->code !== \Grpc\STATUS_OK) {
                throw new \Exception("gRPC request failed: {$status->details}");
            }

            $error = $response->getError();
            if ($error !== null) {
                throw new \Exception("MT5 error: " . $error->getMessage());
            }

            $bars = [];
            $result = $response->getResult();
            
            if (method_exists($result, 'getBars')) {
                $barList = $result->getBars();
                foreach ($barList as $bar) {
                    $bars[] = [
                        'timestamp' => $bar->getTime(),
                        'open' => $bar->getOpen(),
                        'high' => $bar->getHigh(),
                        'low' => $bar->getLow(),
                        'close' => $bar->getClose(),
                        'volume' => $bar->getVolume(),
                    ];
                }
            }

            return $bars;
        } catch (\Exception $e) {
            Log::error('MTAPI gRPC getPriceHistory failed', [
                'error' => $e->getMessage(),
                'symbol' => $symbol,
            ]);
            throw $e;
        }
    }

    /**
     * Convert timeframe string to MT5 timeframe code
     * 
     * @param string $timeframe
     * @return int
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
     * Get connection ID
     * 
     * @return string|null
     */
    public function getConnectionId(): ?string
    {
        return $this->connectionId;
    }

    /**
     * Check connection status
     * 
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connectionId !== null;
    }
}
