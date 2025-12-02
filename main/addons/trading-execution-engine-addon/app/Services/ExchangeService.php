<?php

namespace Addons\TradingExecutionEngine\App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeService
{
    /**
     * Get list of available cryptocurrency exchanges from ccxt.
     *
     * @return array [exchange_id => ['name' => string, 'example' => array]]
     */
    public function getCryptoExchanges(): array
    {
        return Cache::remember('crypto_exchanges_list', 3600, function () {
            try {
                if (!class_exists('\ccxt\Exchange')) {
                    // Return common exchanges if ccxt not installed
                    return $this->getDefaultCryptoExchanges();
                }

                // Get all exchanges from ccxt
                // ccxt stores exchanges list as static property
                $allExchanges = [];
                if (property_exists('\ccxt\Exchange', 'exchanges')) {
                    try {
                        $allExchanges = \ccxt\Exchange::$exchanges ?? [];
                    } catch (\Exception $e) {
                        // Try reflection if direct access fails
                        try {
                            $reflection = new \ReflectionClass('\ccxt\Exchange');
                            if ($reflection->hasProperty('exchanges')) {
                                $property = $reflection->getProperty('exchanges');
                                $property->setAccessible(true);
                                $allExchanges = $property->getValue() ?? [];
                            }
                        } catch (\Exception $e2) {
                            Log::warning('Could not access ccxt exchanges list', ['error' => $e2->getMessage()]);
                        }
                    }
                }
                
                // Filter only the ones that support spot trading and are enabled
                $exchanges = [];
                $popularExchanges = [
                    'binance', 'coinbasepro', 'kraken', 'kucoin', 'okx', 'bybit',
                    'bitget', 'huobi', 'gate', 'bitfinex', 'bitmex', 'ftx',
                    'cryptocom', 'gemini', 'binanceus', 'bitstamp', 'coinbase',
                    'bittrex', 'poloniex', 'upbit', 'bithumb'
                ];

                foreach ($allExchanges as $exchangeId) {
                    try {
                        // Check if exchange class exists
                        $exchangeClass = "\\ccxt\\{$exchangeId}";
                        if (!class_exists($exchangeClass)) {
                            continue;
                        }

                        // Get exchange info to determine if it needs passphrase
                        $exchangeInfo = $this->getExchangeInfo($exchangeId);
                        
                        $exchanges[$exchangeId] = [
                            'name' => $exchangeInfo['name'] ?? ucfirst($exchangeId),
                            'example' => $this->getCredentialsExample($exchangeId),
                            'popular' => in_array(strtolower($exchangeId), array_map('strtolower', $popularExchanges)),
                        ];
                    } catch (\Exception $e) {
                        // Skip exchanges that fail to initialize
                        continue;
                    }
                }

                // Sort by popularity first, then alphabetically
                uasort($exchanges, function ($a, $b) {
                    if ($a['popular'] !== $b['popular']) {
                        return $b['popular'] ? 1 : -1;
                    }
                    return strcmp($a['name'], $b['name']);
                });

                return $exchanges;
            } catch (\Exception $e) {
                Log::error('Failed to get crypto exchanges from ccxt', [
                    'error' => $e->getMessage()
                ]);
                return $this->getDefaultCryptoExchanges();
            }
        });
    }

    /**
     * Get list of available forex brokers.
     *
     * @return array [broker_id => ['name' => string, 'example' => array]]
     */
    public function getForexBrokers(): array
    {
        return [
            'mt4' => [
                'name' => 'MT4 Broker',
                'example' => [
                    'api_key' => 'your_mtapi_key',
                    'api_secret' => 'your_mtapi_secret',
                    'account_id' => 'your_account_id'
                ],
            ],
            'mt5' => [
                'name' => 'MT5 Broker',
                'example' => [
                    'api_key' => 'your_mtapi_key',
                    'api_secret' => 'your_mtapi_secret',
                    'account_id' => 'your_account_id'
                ],
            ],
        ];
    }

    /**
     * Get default list of popular crypto exchanges if ccxt is not available.
     *
     * @return array
     */
    protected function getDefaultCryptoExchanges(): array
    {
        return [
            'binance' => [
                'name' => 'Binance',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret'],
                'popular' => true,
            ],
            'coinbasepro' => [
                'name' => 'Coinbase Pro',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret', 'api_passphrase' => 'your_passphrase'],
                'popular' => true,
            ],
            'kraken' => [
                'name' => 'Kraken',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret'],
                'popular' => true,
            ],
            'kucoin' => [
                'name' => 'KuCoin',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret', 'api_passphrase' => 'your_passphrase'],
                'popular' => true,
            ],
            'okx' => [
                'name' => 'OKX',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret', 'api_passphrase' => 'your_passphrase'],
                'popular' => true,
            ],
            'bybit' => [
                'name' => 'Bybit',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret'],
                'popular' => true,
            ],
            'bitget' => [
                'name' => 'Bitget',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret', 'api_passphrase' => 'your_passphrase'],
                'popular' => true,
            ],
            'huobi' => [
                'name' => 'Huobi',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret'],
                'popular' => true,
            ],
            'gate' => [
                'name' => 'Gate.io',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret'],
                'popular' => true,
            ],
            'bitfinex' => [
                'name' => 'Bitfinex',
                'example' => ['api_key' => 'your_api_key', 'api_secret' => 'your_api_secret'],
                'popular' => true,
            ],
        ];
    }

    /**
     * Get exchange info from ccxt.
     *
     * @param string $exchangeId
     * @return array
     */
    protected function getExchangeInfo(string $exchangeId): array
    {
        try {
            $exchangeClass = "\\ccxt\\{$exchangeId}";
            if (!class_exists($exchangeClass)) {
                return ['name' => ucfirst($exchangeId)];
            }

            // Try to get exchange description without initializing
            $reflection = new \ReflectionClass($exchangeClass);
            $constants = $reflection->getConstants();
            
            return [
                'name' => $constants['name'] ?? ucfirst($exchangeId),
                'id' => $exchangeId,
            ];
        } catch (\Exception $e) {
            return ['name' => ucfirst($exchangeId)];
        }
    }

    /**
     * Get credentials example for an exchange.
     *
     * @param string $exchangeId
     * @return array
     */
    protected function getCredentialsExample(string $exchangeId): array
    {
        // Exchanges that typically require passphrase
        $passphraseExchanges = ['coinbasepro', 'kucoin', 'okx', 'bitget', 'cryptocom'];
        
        $example = [
            'api_key' => 'your_api_key',
            'api_secret' => 'your_api_secret',
        ];

        if (in_array(strtolower($exchangeId), $passphraseExchanges)) {
            $example['api_passphrase'] = 'your_passphrase';
        }

        return $example;
    }
}

