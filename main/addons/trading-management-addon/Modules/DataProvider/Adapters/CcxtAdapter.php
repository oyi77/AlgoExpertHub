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
     * Fetch candle data
     */
    public function fetchCandles(string $symbol, string $timeframe, int $limit = 100): array
    {
        try {
            $this->initializeCcxt();
            
            // Convert timeframe to CCXT format (1h, 4h, 1d, etc.)
            $ccxtTimeframe = $this->convertTimeframe($timeframe);
            
            // Fetch OHLCV data
            $ohlcv = $this->ccxtInstance->fetch_ohlcv($symbol, $ccxtTimeframe, null, $limit);
            
            // Transform to standard format
            $candles = [];
            foreach ($ohlcv as $candle) {
                $candles[] = [
                    'timestamp' => $candle[0],
                    'open' => $candle[1],
                    'high' => $candle[2],
                    'low' => $candle[3],
                    'close' => $candle[4],
                    'volume' => $candle[5],
                ];
            }
            
            return [
                'success' => true,
                'data' => $candles,
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
}

