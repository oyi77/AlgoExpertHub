<?php

namespace App\Services\Trading;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MarketDataService
{
    protected $coingeckoBaseUrl = 'https://api.coingecko.com/api/v3';
    protected $cacheTtl = 30; // 30 seconds cache
    protected $maxRetries = 3;

    /**
     * Supported cryptocurrencies for the platform
     */
    protected $supportedCryptos = [
        'bitcoin' => ['symbol' => 'BTC', 'name' => 'Bitcoin'],
        'ethereum' => ['symbol' => 'ETH', 'name' => 'Ethereum'],
        'tether' => ['symbol' => 'USDT', 'name' => 'Tether'],
        'binancecoin' => ['symbol' => 'BNB', 'name' => 'Binance Coin'],
        'dogecoin' => ['symbol' => 'DOGE', 'name' => 'Dogecoin'],
        'cardano' => ['symbol' => 'ADA', 'name' => 'Cardano'],
        'solana' => ['symbol' => 'SOL', 'name' => 'Solana'],
        'polkadot' => ['symbol' => 'DOT', 'name' => 'Polkadot'],
        'chainlink' => ['symbol' => 'LINK', 'name' => 'Chainlink'],
        'litecoin' => ['symbol' => 'LTC', 'name' => 'Litecoin'],
    ];

    /**
     * Major forex pairs for simulation
     */
    protected $forexPairs = [
        'EURUSD' => ['base' => 'EUR', 'quote' => 'USD', 'name' => 'Euro / US Dollar'],
        'GBPUSD' => ['base' => 'GBP', 'quote' => 'USD', 'name' => 'British Pound / US Dollar'],
        'USDJPY' => ['base' => 'USD', 'quote' => 'JPY', 'name' => 'US Dollar / Japanese Yen'],
        'AUDUSD' => ['base' => 'AUD', 'quote' => 'USD', 'name' => 'Australian Dollar / US Dollar'],
        'USDCAD' => ['base' => 'USD', 'quote' => 'CAD', 'name' => 'US Dollar / Canadian Dollar'],
        'USDCHF' => ['base' => 'USD', 'quote' => 'CHF', 'name' => 'US Dollar / Swiss Franc'],
        'NZDUSD' => ['base' => 'NZD', 'quote' => 'USD', 'name' => 'New Zealand Dollar / US Dollar'],
        'EURJPY' => ['base' => 'EUR', 'quote' => 'JPY', 'name' => 'Euro / Japanese Yen'],
        'GBPJPY' => ['base' => 'GBP', 'quote' => 'JPY', 'name' => 'British Pound / Japanese Yen'],
        'AUDJPY' => ['base' => 'AUD', 'quote' => 'JPY', 'name' => 'Australian Dollar / Japanese Yen'],
    ];

    /**
     * Get cryptocurrency market data
     */
    public function getCryptoData($limit = 10)
    {
        $cacheKey = "market_data_crypto_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($limit) {
            try {
                $cryptoIds = array_keys($this->supportedCryptos);

                $response = Http::timeout(10)->retry($this->maxRetries)->get($this->coingeckoBaseUrl . '/coins/markets', [
                    'vs_currency' => 'usd',
                    'ids' => implode(',', array_slice($cryptoIds, 0, $limit)),
                    'order' => 'market_cap_desc',
                    'per_page' => $limit,
                    'page' => 1,
                    'sparkline' => false,
                    'price_change_percentage' => '1h,24h,7d'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->formatCryptoData($data);
                }

                Log::warning('CoinGecko API failed, using fallback data', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

            } catch (Exception $e) {
                Log::error('CoinGecko API error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Return simulated data as fallback
            return $this->getSimulatedCryptoData($limit);
        });
    }

    /**
     * Get forex market data (simulated)
     */
    public function getForexData($limit = 10)
    {
        $cacheKey = "market_data_forex_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($limit) {
            return $this->getSimulatedForexData($limit);
        });
    }

    /**
     * Get combined market data for landing page
     */
    public function getLandingPageData()
    {
        $cacheKey = "market_data_landing_page";

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            $cryptoData = $this->getCryptoData(5);
            $forexData = $this->getForexData(5);

            return [
                'cryptocurrencies' => $cryptoData,
                'forex_pairs' => $forexData,
                'last_updated' => now()->toISOString(),
                'source' => 'mixed' // 'api' or 'simulated'
            ];
        });
    }

    /**
     * Get specific trading pair data
     */
    public function getTradingPairData($symbol, $type = 'crypto')
    {
        if ($type === 'crypto') {
            $cryptoId = $this->getCryptoIdBySymbol($symbol);
            if ($cryptoId) {
                return $this->getSpecificCryptoData($cryptoId);
            }
        } elseif ($type === 'forex') {
            return $this->getSpecificForexData($symbol);
        }

        return null;
    }

    /**
     * Format CoinGecko API response
     */
    protected function formatCryptoData($data)
    {
        return array_map(function ($item) {
            $symbol = strtoupper($item['symbol']);
            return [
                'symbol' => $symbol,
                'name' => $item['name'],
                'price' => $item['current_price'],
                'change_24h' => $item['price_change_percentage_24h'] ?? 0,
                'change_1h' => $item['price_change_percentage_1h_in_currency'] ?? 0,
                'change_7d' => $item['price_change_percentage_7d_in_currency'] ?? 0,
                'volume' => $item['total_volume'],
                'market_cap' => $item['market_cap'],
                'image' => $item['image'],
                'last_updated' => now()->toISOString(),
                'source' => 'api'
            ];
        }, $data);
    }

    /**
     * Generate simulated cryptocurrency data
     */
    protected function getSimulatedCryptoData($limit)
    {
        $data = [];
        $cryptos = array_slice($this->supportedCryptos, 0, $limit, true);

        foreach ($cryptos as $id => $crypto) {
            $basePrice = $this->getBaseCryptoPrice($crypto['symbol']);
            $changePercent = $this->generateRealisticChange();

            $data[] = [
                'symbol' => $crypto['symbol'],
                'name' => $crypto['name'],
                'price' => round($basePrice * (1 + $changePercent / 100), 2),
                'change_24h' => $changePercent,
                'change_1h' => $changePercent * 0.1,
                'change_7d' => $changePercent * 2.5,
                'volume' => rand(1000000, 100000000),
                'market_cap' => rand(1000000000, 100000000000),
                'image' => '', // No image for simulated data
                'last_updated' => now()->toISOString(),
                'source' => 'simulated'
            ];
        }

        return $data;
    }

    /**
     * Generate simulated forex data
     */
    protected function getSimulatedForexData($limit)
    {
        $data = [];
        $pairs = array_slice($this->forexPairs, 0, $limit, true);

        foreach ($pairs as $pair => $pairData) {
            $basePrice = $this->getBaseForexPrice($pair);
            $changePercent = $this->generateRealisticChange(0.02, 0.15); // Smaller changes for forex

            $data[] = [
                'symbol' => $pair,
                'name' => $pairData['name'],
                'price' => round($basePrice * (1 + $changePercent / 100), 4),
                'change_24h' => $changePercent,
                'change_1h' => $changePercent * 0.05,
                'change_7d' => $changePercent * 1.2,
                'volume' => rand(100000, 10000000),
                'spread' => rand(1, 5), // Pips
                'last_updated' => now()->toISOString(),
                'source' => 'simulated'
            ];
        }

        return $data;
    }

    /**
     * Get base prices for cryptocurrencies
     */
    protected function getBaseCryptoPrice($symbol)
    {
        $basePrices = [
            'BTC' => 45000,
            'ETH' => 2800,
            'USDT' => 1.00,
            'BNB' => 320,
            'DOGE' => 0.085,
            'ADA' => 0.45,
            'SOL' => 95,
            'DOT' => 7.20,
            'LINK' => 14.50,
            'LTC' => 75
        ];

        return $basePrices[$symbol] ?? 1.00;
    }

    /**
     * Get base prices for forex pairs
     */
    protected function getBaseForexPrice($pair)
    {
        $basePrices = [
            'EURUSD' => 1.0850,
            'GBPUSD' => 1.2750,
            'USDJPY' => 148.50,
            'AUDUSD' => 0.6650,
            'USDCAD' => 1.3450,
            'USDCHF' => 0.8950,
            'NZDUSD' => 0.6120,
            'EURJPY' => 161.20,
            'GBPJPY' => 189.50,
            'AUDJPY' => 98.80
        ];

        return $basePrices[$pair] ?? 1.0000;
    }

    /**
     * Generate realistic price changes
     */
    protected function generateRealisticChange($min = -2.0, $max = 2.0)
    {
        // Use normal distribution for more realistic changes
        $mean = 0;
        $stdDev = ($max - $min) / 6; // 99.7% of values within range

        do {
            $u1 = mt_rand() / mt_getrandmax();
            $u2 = mt_rand() / mt_getrandmax();
            $z = sqrt(-2 * log($u1)) * cos(2 * pi() * $u2);
            $change = $mean + $stdDev * $z;
        } while ($change < $min || $change > $max);

        return round($change, 2);
    }

    /**
     * Get crypto ID by symbol
     */
    protected function getCryptoIdBySymbol($symbol)
    {
        $symbol = strtolower($symbol);
        foreach ($this->supportedCryptos as $id => $crypto) {
            if (strtolower($crypto['symbol']) === $symbol) {
                return $id;
            }
        }
        return null;
    }

    /**
     * Get specific crypto data from API
     */
    protected function getSpecificCryptoData($cryptoId)
    {
        try {
            $response = Http::timeout(10)->retry($this->maxRetries)->get($this->coingeckoBaseUrl . '/coins/' . $cryptoId, [
                'localization' => false,
                'tickers' => false,
                'market_data' => true,
                'community_data' => false,
                'developer_data' => false,
                'sparkline' => false
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatSpecificCryptoData($data);
            }
        } catch (Exception $e) {
            Log::error('Failed to get specific crypto data', [
                'crypto_id' => $cryptoId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Get specific forex data (simulated)
     */
    protected function getSpecificForexData($symbol)
    {
        $pairData = $this->forexPairs[$symbol] ?? null;
        if (!$pairData) return null;

        $basePrice = $this->getBaseForexPrice($symbol);
        $changePercent = $this->generateRealisticChange(0.01, 0.1);

        return [
            'symbol' => $symbol,
            'name' => $pairData['name'],
            'price' => round($basePrice * (1 + $changePercent / 100), 4),
            'change_24h' => $changePercent,
            'change_1h' => $changePercent * 0.05,
            'change_7d' => $changePercent * 1.2,
            'bid' => round($basePrice * (1 + $changePercent / 100) - 0.0001, 4),
            'ask' => round($basePrice * (1 + $changePercent / 100) + 0.0001, 4),
            'spread' => 0.0002,
            'last_updated' => now()->toISOString(),
            'source' => 'simulated'
        ];
    }

    /**
     * Format specific crypto data
     */
    protected function formatSpecificCryptoData($data)
    {
        $marketData = $data['market_data'] ?? [];

        return [
            'symbol' => strtoupper($data['symbol']),
            'name' => $data['name'],
            'price' => $marketData['current_price']['usd'] ?? 0,
            'change_24h' => $marketData['price_change_percentage_24h'] ?? 0,
            'change_1h' => $marketData['price_change_percentage_1h_in_currency']['usd'] ?? 0,
            'change_7d' => $marketData['price_change_percentage_7d_in_currency']['usd'] ?? 0,
            'volume' => $marketData['total_volume']['usd'] ?? 0,
            'market_cap' => $marketData['market_cap']['usd'] ?? 0,
            'high_24h' => $marketData['high_24h']['usd'] ?? 0,
            'low_24h' => $marketData['low_24h']['usd'] ?? 0,
            'last_updated' => now()->toISOString(),
            'source' => 'api'
        ];
    }

    /**
     * Clear all market data cache
     */
    public function clearCache()
    {
        Cache::forget('market_data_crypto_5');
        Cache::forget('market_data_crypto_10');
        Cache::forget('market_data_forex_5');
        Cache::forget('market_data_forex_10');
        Cache::forget('market_data_landing_page');

        return true;
    }
}