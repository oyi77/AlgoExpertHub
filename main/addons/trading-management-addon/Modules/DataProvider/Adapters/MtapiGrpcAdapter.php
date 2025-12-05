<?php

namespace Addons\TradingManagement\Modules\DataProvider\Adapters;

use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;
use Addons\TradingManagement\Modules\DataProvider\Services\MtapiGrpcClient;
use Illuminate\Support\Facades\Log;

/**
 * MTAPI gRPC Adapter
 * 
 * Implements DataProviderInterface for mtapi.io MT5 connections using gRPC
 * 
 * Credentials Required:
 * - user: MT account number
 * - password: MT account password
 * - host: MT server host
 * - port: MT server port
 * - base_url: mtapi.io gRPC endpoint (optional, defaults to mt5grpc.mtapi.io:443)
 */
class MtapiGrpcAdapter implements DataProviderInterface
{
    protected MtapiGrpcClient $client;
    protected array $credentials;
    protected bool $connected = false;

    public function __construct(array $credentials = [])
    {
        $this->credentials = $credentials;
        
        $baseUrl = $credentials['base_url'] ?? 'mt5grpc.mtapi.io:443';
        $timeout = $credentials['timeout'] ?? 30;
        
        $this->client = new MtapiGrpcClient($baseUrl, $timeout);
    }

    /**
     * Connect to MT5 account via gRPC
     */
    public function connect(array $credentials): bool
    {
        $this->credentials = array_merge($this->credentials, $credentials);

        $required = ['user', 'password', 'host', 'port'];
        foreach ($required as $field) {
            if (empty($this->credentials[$field])) {
                throw new \Exception("Missing required credential: {$field}");
            }
        }

        try {
            $connectionId = $this->client->connect(
                $this->credentials['user'],
                $this->credentials['password'],
                $this->credentials['host'],
                $this->credentials['port']
            );

            $this->connected = !empty($connectionId);
            return $this->connected;
        } catch (\Exception $e) {
            $this->connected = false;
            Log::error('MTAPI gRPC connection failed', [
                'error' => $e->getMessage(),
                'host' => $this->credentials['host'] ?? 'unknown',
            ]);
            throw $e;
        }
    }

    /**
     * Disconnect from MT5 account
     */
    public function disconnect(): void
    {
        try {
            $this->client->disconnect();
        } catch (\Exception $e) {
            Log::warning('MTAPI gRPC disconnect error', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->connected = false;
        }
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected && $this->client->isConnected();
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
        if (!$this->isConnected()) {
            throw new \Exception('Not connected. Call connect() first.');
        }

        try {
            $bars = $this->client->getPriceHistory($symbol, $timeframe, $limit, $since);
            
            return $this->normalizeOHLCVData($bars);
        } catch (\Exception $e) {
            Log::error('MTAPI gRPC fetchOHLCV failed', [
                'error' => $e->getMessage(),
                'symbol' => $symbol,
                'timeframe' => $timeframe,
            ]);
            throw $e;
        }
    }

    /**
     * Fetch tick data (not fully supported via gRPC - returns empty)
     */
    public function fetchTicks(string $symbol, int $limit = 100): array
    {
        // MTAPI gRPC doesn't provide direct tick-by-tick data via standard endpoints
        // Would require WebSocket subscription which is more complex
        Log::warning('fetchTicks not fully supported via MTAPI gRPC', [
            'symbol' => $symbol,
        ]);
        return [];
    }

    /**
     * Get account information
     */
    public function getAccountInfo(): array
    {
        if (!$this->isConnected()) {
            throw new \Exception('Not connected. Call connect() first.');
        }

        try {
            $summary = $this->client->getAccountSummary();
            $details = $this->client->getAccountDetails();
            
            return array_merge($summary, $details);
        } catch (\Exception $e) {
            Log::error('MTAPI gRPC getAccountInfo failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get available symbols
     */
    public function getAvailableSymbols(): array
    {
        if (!$this->isConnected()) {
            throw new \Exception('Not connected. Call connect() first.');
        }

        try {
            return $this->client->getSymbols();
        } catch (\Exception $e) {
            Log::warning('MTAPI gRPC getAvailableSymbols failed', [
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
            if (!$this->isConnected()) {
                // Try to connect if not connected
                $this->connect($this->credentials);
            }

            $accountInfo = $this->getAccountInfo();
            $latency = round((microtime(true) - $start) * 1000, 2); // ms

            return [
                'success' => true,
                'message' => sprintf(
                    'Connected successfully. Balance: %.2f %s, Equity: %.2f',
                    $accountInfo['balance'] ?? 0,
                    $accountInfo['currency'] ?? 'USD',
                    $accountInfo['equity'] ?? 0
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
        return 'mtapi_grpc';
    }

    /**
     * Normalize OHLCV data to standard format
     * 
     * @param array $data Raw data from gRPC
     * @return array Normalized data [[timestamp, open, high, low, close, volume], ...]
     */
    protected function normalizeOHLCVData(array $data): array
    {
        $normalized = [];

        foreach ($data as $candle) {
            // Handle different timestamp formats
            $timestamp = $candle['timestamp'] ?? 0;
            if (is_object($timestamp) && method_exists($timestamp, 'getSeconds')) {
                // gRPC Timestamp object
                $timestamp = $timestamp->getSeconds();
            }

            $normalized[] = [
                'timestamp' => $timestamp,
                'open' => (float) ($candle['open'] ?? 0),
                'high' => (float) ($candle['high'] ?? 0),
                'low' => (float) ($candle['low'] ?? 0),
                'close' => (float) ($candle['close'] ?? 0),
                'volume' => (float) ($candle['volume'] ?? 0),
            ];
        }

        return $normalized;
    }
}
