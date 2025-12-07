<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CcxtExchangeService
{
    /**
     * Get list of available cryptocurrency exchanges from CCXT
     *
     * Dynamically fetches all supported exchanges from CCXT library.
     * According to CCXT docs, exchanges are listed in \ccxt\Exchange::$exchanges
     *
     * @return array [exchange_id => ['id' => string, 'name' => string, 'needs_passphrase' => bool, 'popular' => bool]]
     */
    public function getCryptoExchanges(): array
    {
        return Cache::remember('ccxt_exchanges_list', 3600, function () {
            try {
                if (!class_exists('\ccxt\Exchange')) {
                    Log::warning('CCXT library not installed, using default exchanges');
                    return $this->getDefaultCryptoExchanges();
                }

                // Get all exchanges from CCXT static property
                // CCXT stores all available exchanges in \ccxt\Exchange::$exchanges
                $allExchanges = $this->getCcxtExchangesList();

                if (empty($allExchanges)) {
                    Log::warning('CCXT exchanges list is empty, using default exchanges');
                    return $this->getDefaultCryptoExchanges();
                }
                
                // Process each exchange
                $exchanges = [];
                $popularExchanges = $this->getPopularExchangesList();
                $passphraseExchanges = $this->getPassphraseRequiredExchanges();

                foreach ($allExchanges as $exchangeId) {
                    try {
                        $exchangeInfo = $this->getExchangeInfo($exchangeId);
                        
                        if (empty($exchangeInfo)) {
                            continue; // Skip if we can't get info
                        }
                        
                        $exchanges[$exchangeId] = [
                            'id' => $exchangeId,
                            'name' => $exchangeInfo['name'] ?? $this->formatExchangeName($exchangeId),
                            'needs_passphrase' => $this->checkIfPassphraseRequired($exchangeId, $passphraseExchanges),
                            'popular' => in_array(strtolower($exchangeId), array_map('strtolower', $popularExchanges)),
                        ];
                    } catch (\Exception $e) {
                        // Skip exchanges that fail to process
                        Log::debug('Skipping exchange processing', [
                            'exchange' => $exchangeId,
                            'error' => $e->getMessage()
                        ]);
                        continue;
                    }
                }

                // Sort: popular first, then alphabetically by name
                uasort($exchanges, function ($a, $b) {
                    if ($a['popular'] !== $b['popular']) {
                        return $b['popular'] ? 1 : -1; // Popular first
                    }
                    return strcmp($a['name'], $b['name']); // Then alphabetically
                });

                Log::info('Loaded exchanges from CCXT', [
                    'count' => count($exchanges),
                    'popular_count' => count(array_filter($exchanges, fn($e) => $e['popular']))
                ]);

                return $exchanges;
            } catch (\Exception $e) {
                Log::error('Failed to get crypto exchanges from ccxt', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return $this->getDefaultCryptoExchanges();
            }
        });
    }

    /**
     * Get list of all exchanges from CCXT
     *
     * @return array
     */
    protected function getCcxtExchangesList(): array
    {
        try {
            // Method 1: Direct static property access
            if (property_exists('\ccxt\Exchange', 'exchanges')) {
                $exchanges = \ccxt\Exchange::$exchanges ?? [];
                if (!empty($exchanges)) {
                    return $exchanges;
                }
            }

            // Method 2: Reflection access
            $reflection = new \ReflectionClass('\ccxt\Exchange');
            if ($reflection->hasProperty('exchanges')) {
                $property = $reflection->getProperty('exchanges');
                $property->setAccessible(true);
                $exchanges = $property->getValue() ?? [];
                if (!empty($exchanges)) {
                    return $exchanges;
                }
            }

            // Method 3: Check if there's a method to get exchanges
            if ($reflection->hasMethod('getExchanges')) {
                $method = $reflection->getMethod('getExchanges');
                $method->setAccessible(true);
                return $method->invoke(null) ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::warning('Could not access CCXT exchanges list', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get exchange info from CCXT
     *
     * Tries to get exchange name and metadata without fully initializing the exchange
     *
     * @param string $exchangeId
     * @return array
     */
    protected function getExchangeInfo(string $exchangeId): array
    {
        try {
            $exchangeClass = "\\ccxt\\{$exchangeId}";
            if (!class_exists($exchangeClass)) {
                return [];
            }

            $reflection = new \ReflectionClass($exchangeClass);
            $constants = $reflection->getConstants();
            
            // CCXT stores exchange name in 'name' constant or 'description'
            $name = $constants['name'] 
                ?? $constants['description'] 
                ?? $this->formatExchangeName($exchangeId);
            
            return [
                'name' => $name,
                'id' => $exchangeId,
            ];
        } catch (\Exception $e) {
            Log::debug('Could not get exchange info', [
                'exchange' => $exchangeId,
                'error' => $e->getMessage()
            ]);
            return ['name' => $this->formatExchangeName($exchangeId)];
        }
    }

    /**
     * Format exchange name from ID
     *
     * @param string $exchangeId
     * @return string
     */
    protected function formatExchangeName(string $exchangeId): string
    {
        // Convert snake_case or kebab-case to Title Case
        $name = str_replace(['_', '-'], ' ', $exchangeId);
        $name = ucwords($name);
        
        // Handle special cases
        $name = str_replace('Usd', 'USD', $name);
        $name = str_replace('Btc', 'BTC', $name);
        $name = str_replace('Eth', 'ETH', $name);
        
        return $name;
    }

    /**
     * Check if exchange requires passphrase
     *
     * @param string $exchangeId
     * @param array $knownPassphraseExchanges
     * @return bool
     */
    protected function checkIfPassphraseRequired(string $exchangeId, array $knownPassphraseExchanges): bool
    {
        // Check against known list
        if (in_array(strtolower($exchangeId), array_map('strtolower', $knownPassphraseExchanges))) {
            return true;
        }

        // Try to check via CCXT if possible (some exchanges have password parameter in API)
        try {
            $exchangeClass = "\\ccxt\\{$exchangeId}";
            if (class_exists($exchangeClass)) {
                $reflection = new \ReflectionClass($exchangeClass);
                $constants = $reflection->getConstants();
                
                // Some exchanges indicate they need password in their description or constants
                // This is a heuristic - actual requirement should be checked from CCXT docs
                if (isset($constants['requiresPassword']) && $constants['requiresPassword']) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Ignore errors in this check
        }

        return false;
    }

    /**
     * Get list of popular exchanges (for sorting/display priority)
     *
     * @return array
     */
    protected function getPopularExchangesList(): array
    {
        return [
            'binance', 'binanceus', 'coinbase', 'coinbasepro', 'kraken', 
            'kucoin', 'okx', 'bybit', 'bitget', 'huobi', 'gate', 
            'bitfinex', 'bitmex', 'cryptocom', 'gemini', 'bitstamp',
            'bittrex', 'poloniex', 'upbit', 'bithumb', 'mexc', 
            'bingx', 'bitmart', 'okcoin', 'wavesexchange', 'hitbtc',
            'coincheck', 'zaif', 'exmo', 'cex', 'yobit'
        ];
    }

    /**
     * Get list of exchanges that require API passphrase
     *
     * Based on CCXT documentation and common API requirements
     *
     * @return array
     */
    protected function getPassphraseRequiredExchanges(): array
    {
        return [
            'coinbasepro', 'coinbase', 'kucoin', 'okx', 'okcoin',
            'bitget', 'cryptocom', 'ascendex', 'ascendexfutures',
            'idex', 'krakenfutures', 'probit'
        ];
    }

    /**
     * Get default list of popular crypto exchanges if CCXT is not available
     *
     * @return array
     */
    protected function getDefaultCryptoExchanges(): array
    {
        return [
            'binance' => [
                'id' => 'binance',
                'name' => 'Binance',
                'needs_passphrase' => false,
                'popular' => true,
            ],
            'coinbasepro' => [
                'id' => 'coinbasepro',
                'name' => 'Coinbase Pro',
                'needs_passphrase' => true,
                'popular' => true,
            ],
            'coinbase' => [
                'id' => 'coinbase',
                'name' => 'Coinbase',
                'needs_passphrase' => true,
                'popular' => true,
            ],
            'kraken' => [
                'id' => 'kraken',
                'name' => 'Kraken',
                'needs_passphrase' => false,
                'popular' => true,
            ],
            'bybit' => [
                'id' => 'bybit',
                'name' => 'Bybit',
                'needs_passphrase' => false,
                'popular' => true,
            ],
            'kucoin' => [
                'id' => 'kucoin',
                'name' => 'KuCoin',
                'needs_passphrase' => true,
                'popular' => true,
            ],
            'okx' => [
                'id' => 'okx',
                'name' => 'OKX',
                'needs_passphrase' => true,
                'popular' => true,
            ],
            'bitfinex' => [
                'id' => 'bitfinex',
                'name' => 'Bitfinex',
                'needs_passphrase' => false,
                'popular' => true,
            ],
            'gate' => [
                'id' => 'gate',
                'name' => 'Gate.io',
                'needs_passphrase' => false,
                'popular' => true,
            ],
            'huobi' => [
                'id' => 'huobi',
                'name' => 'Huobi',
                'needs_passphrase' => false,
                'popular' => true,
            ],
            'bitget' => [
                'id' => 'bitget',
                'name' => 'Bitget',
                'needs_passphrase' => true,
                'popular' => true,
            ],
            'mexc' => [
                'id' => 'mexc',
                'name' => 'MEXC',
                'needs_passphrase' => false,
                'popular' => true,
            ],
            'bingx' => [
                'id' => 'bingx',
                'name' => 'BingX',
                'needs_passphrase' => false,
                'popular' => true,
            ],
        ];
    }
}

