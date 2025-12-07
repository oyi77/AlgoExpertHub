<?php

namespace Addons\TradingManagement\Modules\DataProvider\Adapters;

use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;

/**
 * CCXT Adapter
 * 
 * Connects to crypto exchanges via CCXT library
 */
class CcxtAdapter implements DataProviderInterface
{
    protected $credentials;
    protected $exchange;
    protected $ccxtInstance;

    public function __construct(array $credentials, string $exchangeName)
    {
        $this->credentials = $credentials;
        $this->exchange = $exchangeName;
    }

    /**
     * Test connection
     */
    public function test(): array
    {
        try {
            $this->initializeCcxt();
            
            // Test by fetching markets
            $markets = $this->ccxtInstance->load_markets();
            
            return [
                'success' => true,
                'message' => 'Connected successfully to ' . $this->exchange,
                'data' => [
                    'markets_count' => count($markets),
                    'exchange' => $this->exchange,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch OHLCV data (interface requirement)
     */
    public function fetchOHLCV(string $symbol, string $timeframe, int $limit = 100, ?int $since = null): array
    {
        try {
            $this->initializeCcxt();
            
            // Convert timeframe to CCXT format (1h, 4h, 1d, etc.)
            $ccxtTimeframe = $this->convertTimeframe($timeframe);
            
            // Fetch OHLCV data (CCXT expects timestamp in milliseconds for since parameter)
            $sinceMs = $since ? $since * 1000 : null;
            $ohlcv = $this->ccxtInstance->fetch_ohlcv($symbol, $ccxtTimeframe, $sinceMs, $limit);
            
            // Transform to standard format
            $candles = [];
            foreach ($ohlcv as $candle) {
                $candles[] = [
                    'timestamp' => $candle[0], // Already in milliseconds from CCXT
                    'open' => (float) $candle[1],
                    'high' => (float) $candle[2],
                    'low' => (float) $candle[3],
                    'close' => (float) $candle[4],
                    'volume' => (float) $candle[5],
                ];
            }
            
            return $candles;
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch OHLCV data: ' . $e->getMessage());
        }
    }

    /**
     * Fetch candle data (backward compatibility)
     */
    public function fetchCandles(string $symbol, string $timeframe, int $limit = 100): array
    {
        try {
            $data = $this->fetchOHLCV($symbol, $timeframe, $limit);
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch current price
     */
    public function fetchCurrentPrice(string $symbol): array
    {
        try {
            $this->initializeCcxt();
            
            $ticker = $this->ccxtInstance->fetch_ticker($symbol);
            
            return [
                'success' => true,
                'data' => [
                    'bid' => $ticker['bid'],
                    'ask' => $ticker['ask'],
                    'last' => $ticker['last'],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize CCXT instance
     */
    protected function initializeCcxt(): void
    {
        if ($this->ccxtInstance) {
            return;
        }

        // Dynamically create CCXT exchange instance
        $exchangeClass = "\\ccxt\\" . $this->exchange;
        
        if (!class_exists($exchangeClass)) {
            throw new \Exception("Exchange {$this->exchange} not supported by CCXT");
        }

        $config = [
            'apiKey' => $this->credentials['api_key'] ?? '',
            'secret' => $this->credentials['api_secret'] ?? '',
        ];

        // Add passphrase if provided (required by some exchanges like OKX, KuCoin)
        if (!empty($this->credentials['api_passphrase'])) {
            $config['password'] = $this->credentials['api_passphrase'];
        }

        $this->ccxtInstance = new $exchangeClass($config);
    }

    /**
     * Convert timeframe to CCXT format
     */
    protected function convertTimeframe(string $timeframe): string
    {
        $mapping = [
            'M1' => '1m',
            'M5' => '5m',
            'M15' => '15m',
            'M30' => '30m',
            'H1' => '1h',
            'H4' => '4h',
            'D1' => '1d',
            'W1' => '1w',
            'MN' => '1M',
        ];

        return $mapping[$timeframe] ?? $timeframe;
    }

    /**
     * Connect to exchange
     */
    public function connect(array $credentials): bool
    {
        try {
            $this->credentials = array_merge($this->credentials, $credentials);
            $this->initializeCcxt();
            $this->ccxtInstance->load_markets();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Disconnect
     */
    public function disconnect(): void
    {
        $this->ccxtInstance = null;
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->ccxtInstance !== null;
    }

    /**
     * Fetch tick data
     */
    public function fetchTicks(string $symbol, int $limit = 100): array
    {
        try {
            $this->initializeCcxt();
            $trades = $this->ccxtInstance->fetch_trades($symbol, null, $limit);
            
            $ticks = [];
            foreach ($trades as $trade) {
                $ticks[] = [
                    'timestamp' => $trade['timestamp'],
                    'symbol' => $symbol,
                    'bid' => $trade['price'],
                    'ask' => $trade['price'],
                    'last' => $trade['price'],
                    'volume' => $trade['amount'],
                ];
            }
            
            return $ticks;
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch ticks: ' . $e->getMessage());
        }
    }

    /**
     * Get account info
     */
    public function getAccountInfo(): array
    {
        try {
            $this->initializeCcxt();
            $balance = $this->ccxtInstance->fetch_balance();
            
            return [
                'balance' => $balance['total']['USD'] ?? $balance['total']['USDT'] ?? 0,
                'equity' => $balance['total']['USD'] ?? $balance['total']['USDT'] ?? 0,
                'margin' => 0,
                'free_margin' => $balance['free']['USD'] ?? $balance['free']['USDT'] ?? 0,
                'currency' => 'USD',
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch account info: ' . $e->getMessage());
        }
    }

    /**
     * Get available symbols
     */
    public function getAvailableSymbols(): array
    {
        try {
            $this->initializeCcxt();
            $markets = $this->ccxtInstance->load_markets();
            return array_keys($markets);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Test connection
     */
    public function testConnection(): array
    {
        return $this->test();
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'ccxt_' . strtolower($this->exchange);
    }
}

