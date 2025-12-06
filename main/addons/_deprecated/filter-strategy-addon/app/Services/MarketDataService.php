<?php

namespace Addons\FilterStrategyAddon\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Services\ConnectionService;
use Illuminate\Support\Facades\Log;

class MarketDataService
{
    protected ?ConnectionService $connectionService = null;

    public function __construct(?ConnectionService $connectionService = null)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Get OHLCV candles for a symbol and timeframe
     * 
     * @param string $symbol Symbol (e.g., BTC/USDT, EURUSD)
     * @param string $timeframe Timeframe (e.g., 1h, 4h, 1d)
     * @param int $limit Number of candles to fetch
     * @param ExecutionConnection|null $connection Optional connection to use
     * @return array Array of candles: [['timestamp' => int, 'open' => float, 'high' => float, 'low' => float, 'close' => float, 'volume' => float], ...]
     */
    public function getOhlcv(
        string $symbol,
        string $timeframe,
        int $limit = 100,
        ?ExecutionConnection $connection = null
    ): array {
        try {
            // If connection provided, try to use its adapter
            if ($connection && $this->connectionService) {
                $adapter = $this->connectionService->getAdapter($connection);
                
                if ($adapter && method_exists($adapter, 'fetchOHLCV')) {
                    $candles = $adapter->fetchOHLCV($symbol, $timeframe, $limit);
                    return $this->formatCandles($candles);
                }
            }

            // Fallback: Try to use CCXT directly if available (for crypto)
            if (class_exists(\ccxt\Exchange::class)) {
                // This would require exchange credentials - for now return empty
                // In production, you'd need to configure a default exchange connection
                Log::debug("MarketDataService: CCXT available but no connection provided", [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                ]);
            }

            // Return empty array if no data source available
            Log::warning("MarketDataService: No data source available", [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("MarketDataService: Failed to fetch OHLCV", [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'error' => $e->getMessage(),
            ]);
            
            // Fail-safe: return empty array instead of throwing
            return [];
        }
    }

    /**
     * Get latest price for a symbol
     * 
     * @param string $symbol
     * @param ExecutionConnection|null $connection
     * @return float|null
     */
    public function getLatestPrice(string $symbol, ?ExecutionConnection $connection = null): ?float
    {
        try {
            if ($connection && $this->connectionService) {
                $adapter = $this->connectionService->getAdapter($connection);
                
                if ($adapter) {
                    $price = $adapter->getCurrentPrice($symbol);
                    if ($price !== null) {
                        return (float) $price;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("MarketDataService: Failed to fetch latest price", [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Format candles from adapter to standard format
     * 
     * @param array $rawCandles Raw candles from adapter (can be CCXT format or custom)
     * @return array Formatted candles
     */
    protected function formatCandles(array $rawCandles): array
    {
        $formatted = [];

        foreach ($rawCandles as $candle) {
            // Handle CCXT format: [timestamp, open, high, low, close, volume]
            if (is_array($candle) && count($candle) >= 6) {
                $formatted[] = [
                    'timestamp' => (int) $candle[0],
                    'open' => (float) $candle[1],
                    'high' => (float) $candle[2],
                    'low' => (float) $candle[3],
                    'close' => (float) $candle[4],
                    'volume' => (float) $candle[5],
                ];
            }
            // Handle associative array format
            elseif (is_array($candle) && isset($candle['open'])) {
                $formatted[] = [
                    'timestamp' => (int) ($candle['timestamp'] ?? $candle['time'] ?? time()),
                    'open' => (float) $candle['open'],
                    'high' => (float) $candle['high'],
                    'low' => (float) $candle['low'],
                    'close' => (float) $candle['close'],
                    'volume' => (float) ($candle['volume'] ?? 0),
                ];
            }
        }

        return $formatted;
    }
}

