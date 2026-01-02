<?php

namespace App\Services;

use App\Services\Trading\MarketDataService as TradingMarketDataService;
use Illuminate\Support\Facades\Log;

class TradingPairProviderService
{
    protected $tradingMarketDataService;

    public function __construct(TradingMarketDataService $tradingMarketDataService)
    {
        $this->tradingMarketDataService = $tradingMarketDataService;
    }

    /**
     * Get all trading pairs with market data
     *
     * @param string $category
     * @return array
     */
    public function getTradingPairs(string $category = 'all'): array
    {
        try {
            // Define quote currencies that should not be used as base assets
            $quoteAssets = ['USDT', 'BUSD', 'USDC', 'DAI', 'TUSD', 'PAX', 'USDP', 'UST'];
            
            // Get crypto pairs
            $cryptoData = $this->tradingMarketDataService->getCryptoData(50);
            // Filter out quote assets to prevent pairs like USDT/USDT
            $cryptoData = array_filter($cryptoData, function ($item) use ($quoteAssets) {
                return !in_array(strtoupper($item['symbol']), $quoteAssets);
            });
            
            $cryptoPairs = array_map(function ($item) {
                return [
                    'symbol' => $item['symbol'] . 'USDT',
                    'displaySymbol' => $item['symbol'] . '/USDT',
                    'name' => $item['name'],
                    'category' => 'crypto',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '100x',
                    'icon' => $item['image'] ?? null,
                ];
            }, $cryptoData);

            // Get forex pairs
            $forexData = $this->tradingMarketDataService->getForexData(20);
            $forexPairs = array_map(function ($item) {
                $symbol = $item['symbol'];
                // Format display symbol for forex (EURUSD -> EUR/USD)
                $displaySymbol = $symbol;
                if (strlen($symbol) === 6) {
                    $displaySymbol = substr($symbol, 0, 3) . '/' . substr($symbol, 3, 3);
                }
                
                return [
                    'symbol' => $symbol,
                    'displaySymbol' => $displaySymbol,
                    'name' => $item['name'],
                    'category' => 'forex',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '200x',
                    'icon' => null,
                ];
            }, $forexData);
            
            // Filter out forex pairs where base = quote (e.g., EUR/EUR)
            $forexPairs = array_filter($forexPairs, function ($pair) {
                $symbol = $pair['symbol'];
                if (strlen($symbol) === 6) {
                    $base = substr($symbol, 0, 3);
                    $quote = substr($symbol, 3, 3);
                    return $base !== $quote;
                }
                return true;
            });

            // Get indices
            $indicesData = $this->tradingMarketDataService->getIndicesData(15);
            $indicesPairs = array_map(function ($item) {
                return [
                    'symbol' => $item['symbol'],
                    'displaySymbol' => $item['symbol'],
                    'name' => $item['name'],
                    'category' => 'indices',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '50x',
                    'icon' => null,
                ];
            }, $indicesData);

            // Get commodities
            $commoditiesData = $this->tradingMarketDataService->getCommoditiesData(15);
            $commoditiesPairs = array_map(function ($item) {
                $symbol = $item['symbol'];
                // Format display symbol (XAUUSD -> XAU/USD)
                $displaySymbol = $symbol;
                if (strlen($symbol) === 6 && strpos($symbol, 'USD') !== false) {
                    $base = substr($symbol, 0, 3);
                    $displaySymbol = $base . '/USD';
                }
                
                return [
                    'symbol' => $symbol,
                    'displaySymbol' => $displaySymbol,
                    'name' => $item['name'],
                    'category' => 'commodities',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '100x',
                    'icon' => null,
                ];
            }, $commoditiesData);
            
            // Filter out commodity pairs where base = quote (e.g., USD/USD)
            $commoditiesPairs = array_filter($commoditiesPairs, function ($pair) {
                $symbol = $pair['symbol'];
                if (strlen($symbol) === 6 && strpos($symbol, 'USD') !== false) {
                    $base = substr($symbol, 0, 3);
                    return $base !== 'USD';
                }
                return true;
            });

            // Get stocks
            $stocksData = $this->tradingMarketDataService->getStocksData(15);
            $stocksPairs = array_map(function ($item) {
                return [
                    'symbol' => $item['symbol'],
                    'displaySymbol' => $item['symbol'],
                    'name' => $item['name'],
                    'category' => 'stocks',
                    'price' => $item['price'],
                    'change24h' => $item['change_24h'] ?? 0,
                    'volume' => $item['volume'] ?? 0,
                    'leverage' => '5x',
                    'icon' => null,
                ];
            }, $stocksData);

            // Combine all pairs
            $allPairs = array_merge($cryptoPairs, $forexPairs, $indicesPairs, $commoditiesPairs, $stocksPairs);

            // Filter by category
            if ($category !== 'all') {
                $allPairs = array_filter($allPairs, function ($pair) use ($category) {
                    return $pair['category'] === $category;
                });
            }

            // Format volume for display and fix symbol format
            $allPairs = array_map(function ($pair) {
                $volume = $pair['volume'];
                if ($volume >= 1000000000) {
                    $pair['volumeDisplay'] = number_format($volume / 1000000000, 2) . 'B';
                } elseif ($volume >= 1000000) {
                    $pair['volumeDisplay'] = number_format($volume / 1000000, 2) . 'M';
                } else {
                    $pair['volumeDisplay'] = number_format($volume, 0);
                }
                
                // Ensure displaySymbol is properly formatted
                if (!isset($pair['displaySymbol']) || empty($pair['displaySymbol'])) {
                    if ($pair['category'] === 'crypto') {
                        $pair['displaySymbol'] = str_replace('USDT', '/USDT', $pair['symbol']);
                    } else {
                        // For forex, format like EUR/USD
                        $symbol = $pair['symbol'];
                        if (strlen($symbol) === 6) {
                            $pair['displaySymbol'] = substr($symbol, 0, 3) . '/' . substr($symbol, 3, 3);
                        } else {
                            $pair['displaySymbol'] = $symbol;
                        }
                    }
                }
                
                return $pair;
            }, $allPairs);

            return [
                'data' => array_values($allPairs),
                'counts' => [
                    'all' => count($cryptoPairs) + count($forexPairs) + count($indicesPairs) + count($commoditiesPairs) + count($stocksPairs),
                    'crypto' => count($cryptoPairs),
                    'forex' => count($forexPairs),
                    'indices' => count($indicesPairs),
                    'commodities' => count($commoditiesPairs),
                    'stocks' => count($stocksPairs),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch trading pairs', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
