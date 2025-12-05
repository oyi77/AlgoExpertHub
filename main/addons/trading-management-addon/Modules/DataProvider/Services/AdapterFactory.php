<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter;
use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;

/**
 * Adapter Factory
 * 
 * Creates appropriate adapter based on connection type
 */
class AdapterFactory
{
    /**
     * Create adapter instance for a data connection
     * 
     * @param DataConnection $connection
     * @return DataProviderInterface
     * @throws \Exception If unsupported type
     */
    public function create(DataConnection $connection): DataProviderInterface
    {
        return match ($connection->type) {
            'mtapi' => new MtapiAdapter($connection->credentials),
            'mtapi_grpc' => new MtapiGrpcAdapter($connection->credentials),
            'ccxt_crypto' => new \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter($connection->credentials, $connection->provider),
            'custom_api' => new \Addons\TradingManagement\Modules\DataProvider\Adapters\CustomApiAdapter($connection->credentials),
            default => throw new \Exception("Unsupported data provider type: {$connection->type}"),
        };
    }

    /**
     * Get supported provider types
     * 
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        return [
            'mtapi' => [
                'name' => 'mtapi.io (MT4/MT5) REST',
                'description' => 'Connect to MT4/MT5 brokers via mtapi.io REST API for Forex market data',
                'credentials' => ['api_key', 'account_id'],
                'exchanges' => ['MT4', 'MT5'],
            ],
            'mtapi_grpc' => [
                'name' => 'mtapi.io (MT4/MT5) gRPC',
                'description' => 'Connect to MT4/MT5 brokers via mtapi.io gRPC for Forex market data (faster, real-time)',
                'credentials' => ['user', 'password', 'host', 'port'],
                'exchanges' => ['MT4', 'MT5'],
            ],
            'ccxt_crypto' => [
                'name' => 'CCXT (Crypto Exchanges)',
                'description' => 'Connect to 100+ crypto exchanges via CCXT library',
                'credentials' => ['api_key', 'api_secret', 'api_passphrase'],
                'exchanges' => [
                    'binance', 'coinbase', 'kraken', 'bitfinex', 'bybit', 'okx', 
                    'kucoin', 'huobi', 'gate', 'bitget', 'mexc', 'bingx'
                ],
            ],
            'custom_api' => [
                'name' => 'Custom API',
                'description' => 'Connect to custom data provider via REST API',
                'credentials' => ['api_url', 'api_key', 'api_secret'],
                'exchanges' => [],
            ],
        ];
    }

    /**
     * Validate credentials for a provider type
     * 
     * @param string $type Provider type
     * @param array $credentials
     * @return array ['valid' => bool, 'missing' => array]
     */
    public static function validateCredentials(string $type, array $credentials): array
    {
        $required = match ($type) {
            'mtapi' => ['api_key', 'account_id'],
            'mtapi_grpc' => ['user', 'password', 'host', 'port'],
            'ccxt_crypto' => ['api_key', 'api_secret'],
            default => [],
        };

        $missing = [];
        foreach ($required as $field) {
            if (empty($credentials[$field])) {
                $missing[] = $field;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing,
        ];
    }
}

