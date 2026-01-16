<?php

namespace App\Services\Trading;

use App\Services\Trading\TwelveDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MarketDataService
{
    protected $cacheTtl = 900; // 15 minutes (900 seconds) for Twelve Data API
    protected $maxRetries = 3;
    protected TwelveDataService $twelveDataService;

    public function __construct(TwelveDataService $twelveDataService = null)
    {
        $this->twelveDataService = $twelveDataService ?? new TwelveDataService();
    }

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
     * Indices for simulation
     */
    protected $indices = [
        'US30' => ['name' => 'Dow Jones Industrial Average', 'basePrice' => 34500],
        'US100' => ['name' => 'NASDAQ 100', 'basePrice' => 15200],
        'US500' => ['name' => 'S&P 500', 'basePrice' => 4450],
        'UK100' => ['name' => 'FTSE 100', 'basePrice' => 7650],
        'GER40' => ['name' => 'DAX 40', 'basePrice' => 16200],
        'JPN225' => ['name' => 'Nikkei 225', 'basePrice' => 33500],
        'AUS200' => ['name' => 'ASX 200', 'basePrice' => 7200],
        'FRA40' => ['name' => 'CAC 40', 'basePrice' => 7250],
    ];

    /**
     * Commodities for simulation
     */
    protected $commodities = [
        'XAUUSD' => ['name' => 'Gold / US Dollar', 'basePrice' => 2035.50],
        'XAGUSD' => ['name' => 'Silver / US Dollar', 'basePrice' => 24.80],
        'XPDUSD' => ['name' => 'Palladium / US Dollar', 'basePrice' => 1050.00],
        'XPTUSD' => ['name' => 'Platinum / US Dollar', 'basePrice' => 950.00],
        'WTIUSD' => ['name' => 'Crude Oil WTI', 'basePrice' => 78.50],
        'BRENTUSD' => ['name' => 'Brent Crude Oil', 'basePrice' => 82.30],
        'NATGAS' => ['name' => 'Natural Gas', 'basePrice' => 2.85],
        'COPPER' => ['name' => 'Copper', 'basePrice' => 3.75],
    ];

    /**
     * Stocks for simulation
     */
    protected $stocks = [
        'AAPL' => ['name' => 'Apple Inc.', 'basePrice' => 185.50],
        'MSFT' => ['name' => 'Microsoft Corporation', 'basePrice' => 380.25],
        'GOOGL' => ['name' => 'Alphabet Inc.', 'basePrice' => 142.80],
        'AMZN' => ['name' => 'Amazon.com Inc.', 'basePrice' => 148.90],
        'TSLA' => ['name' => 'Tesla Inc.', 'basePrice' => 245.60],
        'META' => ['name' => 'Meta Platforms Inc.', 'basePrice' => 485.30],
        'NVDA' => ['name' => 'NVIDIA Corporation', 'basePrice' => 495.20],
        'JPM' => ['name' => 'JPMorgan Chase & Co.', 'basePrice' => 158.40],
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

                $response = Http::timeout(10)->retry($this->maxRetries)->get('https://api.coingecko.com/api/v3/coins/markets', [
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
     * Get forex market data (via Twelve Data)
     */
    public function getForexData($limit = 10)
    {
        $cacheKey = "market_data_forex_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($limit) {
            $data = [];
            $pairs = array_slice($this->forexPairs, 0, $limit, true);

            foreach ($pairs as $pair => $pairData) {
                // Convert pair name from 'EURUSD' to 'EUR/USD' format for API
                $from = substr($pair, 0, 3);
                $to = substr($pair, 3, 6);
                $result = $this->twelveDataService->getExchangeRate($from, $to);

                $data[] = [
                    'symbol' => $pair,
                    'name' => $pairData['name'],
                    'price' => $result['price'],
                    'change_24h' => $result['change_24h'],
                    'change_1h' => $result['change_1h'],
                    'change_7d' => $result['change_7d'],
                    'volume' => $result['volume'] ?? 0,
                    'source' => $result['source'],
                    'last_updated' => $result['timestamp'],
                ];
            }

            return $data;
        });
    }

            return $data;
        });
    }

    /**
     * Get indices market data (via Twelve Data)
     */
    public function getIndicesData($limit = 10)
    {
        $cacheKey = "market_data_indices_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($limit) {
            $data = [];
            $indices = array_slice($this->indices, 0, $limit, true);

            foreach ($indices as $index => $indexData) {
                $result = $this->twelveDataService->getQuote($index);

                $data[] = [
                    'symbol' => $index,
                    'name' => $indexData['name'],
                    'price' => $result['price'],
                    'change_24h' => isset($result['change']) ? $result['change'] : 0,
                    'change_1h' => isset($result['percent_change']) ? $result['percent_change'] : 0,
                    'change_7d' => isset($result['change']) ? $result['change'] : 0,
                    'volume' => $result['volume'] ?? 0,
                    'source' => $result['source'],
                    'last_updated' => $result['timestamp'],
                ];
            }

            return $data;
        });
    }

    /**
     * Get commodities market data (via Twelve Data)
     */
    public function getCommoditiesData($limit = 10)
    {
        $cacheKey = "market_data_commodities_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($limit) {
            $data = [];
            $commodities = array_slice($this->commodities, 0, $limit, true);

            foreach ($commodities as $symbol => $commodityData) {
                $result = $this->twelveDataService->getQuote($symbol);

                $data[] = [
                    'symbol' => $symbol,
                    'name' => $commodityData['name'],
                    'price' => $result['price'],
                    'change_24h' => isset($result['change']) ? $result['change'] : 0,
                    'change_1h' => isset($result['percent_change']) ? $result['percent_change'] : 0,
                    'change_7d' => isset($result['percent_change']) ? $result['percent_change'] : 0,
                    'volume' => $result['volume'] ?? 0,
                    'source' => $result['source'],
                    'last_updated' => $result['timestamp'],
                ];
            }

            return $data;
        });
    }

    /**
     * Get stocks market data (via Twelve Data)
     */
    public function getStocksData($limit = 10)
    {
        $cacheKey = "market_data_stocks_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($limit) {
            $data = [];
            $stocks = array_slice($this->stocks, 0, $limit, true);

            foreach ($stocks as $symbol => $stockData) {
                $result = $this->twelveDataService->getQuote($symbol);

                $data[] = [
                    'symbol' => $symbol,
                    'name' => $stockData['name'],
                    'price' => $result['price'],
                    'change_24h' => isset($result['change']) ? $result['change'] : 0,
                    'change_1h' => isset($result['percent_change']) ? $result['percent_change'] : 0,
                    'change_7d' => isset($result['percent_change']) ? $result['percent_change'] : 0,
                    'volume' => $result['volume'] ?? 0,
                    'source' => $result['source'],
                    'last_updated' => $result['timestamp'],
                ];
            }

            return $data;
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
            $indicesData = $this->getIndicesData(5);
            $commoditiesData = $this->getCommoditiesData(5);
            $stocksData = $this->getStocksData(5);

            // Determine data sources
            $cryptoSource = !empty($cryptoData) && $cryptoData[0]['source'] === 'api' ? 'api' : 'simulated';
            $forexSource = !empty($forexData) && $forexData[0]['source'] === 'api' ? 'api' : 'simulated';
            $indicesSource = !empty($indicesData) && $indicesData[0]['source'] === 'api' ? 'api' : 'simulated';
            $commoditiesSource = !empty($commoditiesData) && $commoditiesData[0]['source'] === 'api' ? 'api' : 'simulated';
            $stocksSource = !empty($stocksData) && $stocksData[0]['source'] === 'api' ? 'api' : 'simulated';

            // If at least one real data source, show 'api', otherwise 'simulated'
            $overallSource = ($cryptoSource === 'api' || $forexSource === 'api' || $indicesSource === 'api' ||
                          $commoditiesSource === 'api' || $stocksSource === 'api') ? 'api' : 'simulated';

            return [
                'cryptocurrencies' => $cryptoData,
                'forex_pairs' => $forexData,
                'indices' => $indicesData,
                'commodities' => $commoditiesData,
                'stocks' => $stocksData,
                'last_updated' => now()->toISOString(),
                'source' => $overallSource,
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
            // Convert forex symbol format (EUR/USD) to pair name for Twelve Data
            $from = substr($symbol, 0, 3);
            $to = substr($symbol, 4, 6);
            $result = $this->twelveDataService->getExchangeRate($from, $to);

            return [
                'symbol' => $symbol,
                'name' => "{$from}/{$to}",
                'price' => $result['price'],
                'change_24h' => $result['change_24h'],
                'change_1h' => $result['change_1h'],
                'change_7d' => $result['change_7d'],
                'volume' => $result['volume'],
                'source' => $result['source'],
                'last_updated' => $result['timestamp'],
            ];
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
             $response = Http::timeout(10)->retry($this->maxRetries)->get('https://api.coingecko.com/api/v3/coins/' . $cryptoId, [
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
     * Generate simulated indices data
     */
    protected function getSimulatedIndicesData($limit)
    {
        $data = [];
        $indices = array_slice($this->indices, 0, $limit, true);

        foreach ($indices as $symbol => $indexData) {
            $changePercent = $this->generateRealisticChange(-0.5, 0.5);

            $data[] = [
                'symbol' => $symbol,
                'name' => $indexData['name'],
                'price' => round($indexData['basePrice'] * (1 + $changePercent / 100), 2),
                'change_24h' => $changePercent,
                'change_1h' => $changePercent * 0.1,
                'change_7d' => $changePercent * 2.0,
                'volume' => rand(1000000, 50000000),
                'last_updated' => now()->toISOString(),
                'source' => 'simulated'
            ];
        }

        return $data;
    }

    /**
     * Generate simulated commodities data
     */
    protected function getSimulatedCommoditiesData($limit)
    {
        $data = [];
        $commodities = array_slice($this->commodities, 0, $limit, true);

        foreach ($commodities as $symbol => $commodityData) {
            $changePercent = $this->generateRealisticChange(-1.0, 1.0);
            $price = round($commodityData['basePrice'] * (1 + $changePercent / 100), 2);

            $data[] = [
                'symbol' => $symbol,
                'name' => $commodityData['name'],
                'price' => $price,
                'change_24h' => $changePercent,
                'change_1h' => $changePercent * 0.08,
                'change_7d' => $changePercent * 1.5,
                'volume' => rand(500000, 20000000),
                'last_updated' => now()->toISOString(),
                'source' => 'simulated'
            ];
        }

        return $data;
    }

    /**
     * Generate simulated stocks data
     */
    protected function getSimulatedStocksData($limit)
    {
        $data = [];
        $stocks = array_slice($this->stocks, 0, $limit, true);

        foreach ($stocks as $symbol => $stockData) {
            $changePercent = $this->generateRealisticChange(-2.0, 2.0);

            $data[] = [
                'symbol' => $symbol,
                'name' => $stockData['name'],
                'price' => round($stockData['basePrice'] * (1 + $changePercent / 100), 2),
                'change_24h' => $changePercent,
                'change_1h' => $changePercent * 0.15,
                'change_7d' => $changePercent * 3.0,
                'volume' => rand(10000000, 100000000),
                'market_cap' => rand(100000000000, 3000000000000),
                'last_updated' => now()->toISOString(),
                'source' => 'simulated'
            ];
        }

        return $data;
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
        Cache::forget('market_data_indices_10');
        Cache::forget('market_data_commodities_10');
        Cache::forget('market_data_stocks_10');
        Cache::forget('market_data_landing_page');

        return true;
    }
}