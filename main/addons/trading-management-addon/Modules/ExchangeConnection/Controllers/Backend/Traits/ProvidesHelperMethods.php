<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use App\Services\GlobalConfigurationService;
use Illuminate\Support\Facades\Crypt;

trait ProvidesHelperMethods
{
    /**
     * Get appropriate adapter based on connection type and provider
     * 
     * Adapter selection logic:
     * - CRYPTO_EXCHANGE: Always uses CcxtAdapter (CCXT library for crypto exchanges)
     * - FX_BROKER:
     *   - provider='metaapi': MetaApiAdapter (MetaAPI.cloud for MT4/MT5)
     *   - provider='mtapi_grpc': MtapiGrpcAdapter (MTAPI gRPC for MT4/MT5)
     *   - provider='mtapi' or default: MtapiAdapter (MTAPI REST for MT4/MT5)
     * 
     * @param ExchangeConnection $connection
     * @return DataProviderInterface
     */
    protected function getAdapter(ExchangeConnection $connection)
    {
        // Get connection type and provider with fallbacks
        $connectionType = $connection->connection_type ?? $connection->type ?? null;
        $provider = $connection->provider ?? $connection->exchange_name ?? null;
        $credentials = $connection->credentials ?? [];
        
        \Log::debug('getAdapter called', [
            'connection_id' => $connection->id,
            'provider' => $provider,
            'has_account_id' => isset($credentials['account_id']),
            'credentials_keys' => array_keys($credentials),
        ]);
        
        // Determine if crypto exchange (check connection_type first, then legacy type field)
        $isCrypto = false;
        if ($connectionType === 'CRYPTO_EXCHANGE' || $connectionType === 'crypto') {
            $isCrypto = true;
        } elseif (!$connectionType && $provider) {
            // Fallback: check provider name for known crypto exchanges
            $cryptoExchanges = ['binance', 'coinbase', 'coinbasepro', 'kraken', 'bitfinex', 'okx', 'bybit', 'huobi', 'kucoin', 'gate', 'mexc'];
            if (in_array(strtolower($provider), $cryptoExchanges)) {
                $isCrypto = true;
            }
        }
        
        // Crypto exchanges always use CCXT adapter
        if ($isCrypto) {
            if (!$provider) {
                throw new \Exception('Provider/exchange name is required for crypto exchange connections');
            }
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter(
                $provider,
                $credentials
            );
        }
        
        // For FX brokers (MT4/MT5), select adapter based on provider
        if ($provider === 'metaapi') {
            // MetaAPI.cloud adapter for MT4/MT5 connections
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter(
                $credentials
            );
        } elseif ($provider === 'mtapi_grpc' || 
                  (is_array($credentials) && isset($credentials['provider']) && $credentials['provider'] === 'mtapi_grpc')) {
            // MTAPI gRPC adapter
            $globalSettings = GlobalConfigurationService::get('mtapi_global_settings', []);
            
            if (!empty($globalSettings['base_url'])) {
                $credentials['base_url'] = $globalSettings['base_url'];
            }
            if (!empty($globalSettings['timeout'])) {
                $credentials['timeout'] = $globalSettings['timeout'];
            }
            
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter($credentials);
        } else {
            // Default: MTAPI REST adapter
            return new \Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter(
                $credentials
            );
        }
    }

    /**
     * Get default symbol based on connection type
     */
    protected function getDefaultSymbol(ExchangeConnection $exchangeConnection, $adapter = null): string
    {
        // Try to get available symbols from adapter
        if ($adapter && method_exists($adapter, 'getAvailableSymbols')) {
            try {
                $availableSymbols = $adapter->getAvailableSymbols();
                if (!empty($availableSymbols)) {
                    // For FX brokers, prefer XAUUSD or XAUUSDc
                    if ($exchangeConnection->connection_type === 'FX_BROKER') {
                        $preferredSymbols = ['XAUUSDc', 'XAUUSD', 'EURUSD', 'GBPUSD', 'USDJPY'];
                        foreach ($preferredSymbols as $prefSymbol) {
                            if (in_array($prefSymbol, $availableSymbols)) {
                                return $prefSymbol;
                            }
                        }
                        // If preferred not found, return first available
                        return $availableSymbols[0];
                    } else {
                        // For crypto exchanges, prefer BTCUSDT, BTC/USDT, etc.
                        $preferredSymbols = ['BTCUSDT', 'BTC/USDT', 'BTC-USDT', 'BTC_USDT'];
                        foreach ($preferredSymbols as $prefSymbol) {
                            if (in_array($prefSymbol, $availableSymbols)) {
                                return $prefSymbol;
                            }
                        }
                        // Try case-insensitive match
                        foreach ($availableSymbols as $sym) {
                            if (stripos($sym, 'BTC') !== false && stripos($sym, 'USDT') !== false) {
                                return $sym;
                            }
                        }
                        // If preferred not found, return first available
                        return $availableSymbols[0];
                    }
                }
            } catch (\Exception $e) {
                // Fall back to defaults if fetching symbols fails
            }
        }

        // Fallback to connection-type-based defaults
        if ($exchangeConnection->connection_type === 'FX_BROKER') {
            return 'XAUUSDc'; // Try XAUUSDc first (common on many brokers)
        } else {
            return 'BTCUSDT'; // Default for crypto exchanges
        }
    }

    /**
     * Get MetaApi API token from global settings
     * 
     * @return string|null
     */
    protected function getMetaApiTokenFromGlobalSettings(): ?string
    {
        try {
            $globalConfig = GlobalConfigurationService::get('metaapi_global_settings', []);
            
            if (!empty($globalConfig['api_token'])) {
                try {
                    // Try to decrypt (if encrypted)
                    return Crypt::decryptString($globalConfig['api_token']);
                } catch (\Exception $e) {
                    // If decryption fails, assume it's not encrypted
                    return $globalConfig['api_token'];
                }
            }
        } catch (\Exception $e) {
            \Log::debug('Failed to get MetaApi token from global settings', ['error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Get MetaApi base URL from global settings
     * 
     * @return string
     */
    protected function getMetaApiBaseUrlFromGlobalSettings(): string
    {
        try {
            $globalConfig = GlobalConfigurationService::get('metaapi_global_settings', []);
            return $globalConfig['base_url'] ?? 'https://mt-client-api-v1.london.agiliumtrade.ai';
        } catch (\Exception $e) {
            return 'https://mt-client-api-v1.london.agiliumtrade.ai';
        }
    }
}

