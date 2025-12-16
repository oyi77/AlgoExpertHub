<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketDataService
{
    protected string $binanceWsUrl = 'wss://stream.binance.com:9443/ws';
    protected string $binanceApiUrl = 'https://api.binance.com/api/v3';

    /**
     * Get current price for a symbol
     */
    public function getCurrentPrice(string $symbol): ?float
    {
        $cacheKey = "market_price_{$symbol}";
        
        return Cache::remember($cacheKey, 5, function () use ($symbol) {
            try {
                $response = Http::timeout(5)->get("{$this->binanceApiUrl}/ticker/price", [
                    'symbol' => $this->formatSymbol($symbol),
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return (float) ($data['price'] ?? null);
                }

                Log::warning('Failed to fetch price from Binance', [
                    'symbol' => $symbol,
                    'status' => $response->status(),
                ]);

                return null;

            } catch (\Exception $e) {
                Log::error('Error fetching market price', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Get orderbook for a symbol
     */
    public function getOrderbook(string $symbol, int $limit = 20): ?array
    {
        $cacheKey = "orderbook_{$symbol}_{$limit}";
        
        return Cache::remember($cacheKey, 2, function () use ($symbol, $limit) {
            try {
                $response = Http::timeout(5)->get("{$this->binanceApiUrl}/depth", [
                    'symbol' => $this->formatSymbol($symbol),
                    'limit' => $limit,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return [
                        'bids' => array_map(function ($bid) {
                            return [
                                'price' => (float) $bid[0],
                                'quantity' => (float) $bid[1],
                            ];
                        }, $data['bids'] ?? []),
                        'asks' => array_map(function ($ask) {
                            return [
                                'price' => (float) $ask[0],
                                'quantity' => (float) $ask[1],
                            ];
                        }, $data['asks'] ?? []),
                    ];
                }

                return null;

            } catch (\Exception $e) {
                Log::error('Error fetching orderbook', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Get recent trades for a symbol
     */
    public function getRecentTrades(string $symbol, int $limit = 50): ?array
    {
        $cacheKey = "recent_trades_{$symbol}_{$limit}";
        
        return Cache::remember($cacheKey, 2, function () use ($symbol, $limit) {
            try {
                $response = Http::timeout(5)->get("{$this->binanceApiUrl}/trades", [
                    'symbol' => $this->formatSymbol($symbol),
                    'limit' => $limit,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return array_map(function ($trade) {
                        return [
                            'id' => $trade['id'],
                            'price' => (float) $trade['price'],
                            'quantity' => (float) $trade['qty'],
                            'time' => $trade['time'],
                            'isBuyerMaker' => $trade['isBuyerMaker'],
                        ];
                    }, $data);
                }

                return null;

            } catch (\Exception $e) {
                Log::error('Error fetching recent trades', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Get candlestick data for charting
     */
    public function getCandlestickData(string $symbol, string $interval = '1m', int $limit = 100): ?array
    {
        $cacheKey = "candlestick_{$symbol}_{$interval}_{$limit}";
        
        return Cache::remember($cacheKey, 60, function () use ($symbol, $interval, $limit) {
            try {
                $response = Http::timeout(10)->get("{$this->binanceApiUrl}/klines", [
                    'symbol' => $this->formatSymbol($symbol),
                    'interval' => $interval,
                    'limit' => $limit,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return array_map(function ($candle) {
                        return [
                            'time' => $candle[0],
                            'open' => (float) $candle[1],
                            'high' => (float) $candle[2],
                            'low' => (float) $candle[3],
                            'close' => (float) $candle[4],
                            'volume' => (float) $candle[5],
                        ];
                    }, $data);
                }

                return null;

            } catch (\Exception $e) {
                Log::error('Error fetching candlestick data', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Get 24h ticker statistics
     */
    public function get24hStats(string $symbol): ?array
    {
        $cacheKey = "24h_stats_{$symbol}";
        
        return Cache::remember($cacheKey, 10, function () use ($symbol) {
            try {
                $response = Http::timeout(5)->get("{$this->binanceApiUrl}/ticker/24hr", [
                    'symbol' => $this->formatSymbol($symbol),
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return [
                        'priceChange' => (float) $data['priceChange'],
                        'priceChangePercent' => (float) $data['priceChangePercent'],
                        'highPrice' => (float) $data['highPrice'],
                        'lowPrice' => (float) $data['lowPrice'],
                        'volume' => (float) $data['volume'],
                        'quoteVolume' => (float) $data['quoteVolume'],
                    ];
                }

                return null;

            } catch (\Exception $e) {
                Log::error('Error fetching 24h stats', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Format symbol for Binance API (e.g., BTC/USD -> BTCUSDT)
     */
    protected function formatSymbol(string $symbol): string
    {
        // Remove slashes and convert to uppercase
        $symbol = str_replace(['/', '-', '_', ' '], '', strtoupper($symbol));
        
        // If already in correct format (ends with USDT, BUSD, etc.), return as-is
        if (preg_match('/(USDT|BUSD|BTC|ETH|BNB)$/', $symbol)) {
            return $symbol;
        }
        
        // Otherwise, append USDT
        return $symbol . 'USDT';
    }
}
