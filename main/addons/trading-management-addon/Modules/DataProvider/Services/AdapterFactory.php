<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter;
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
            // 'ccxt_crypto' => new CcxtAdapter($connection->credentials, $connection->provider),
            // 'custom_api' => new CustomApiAdapter($connection->credentials),
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
                'name' => 'mtapi.io (MT4/MT5)',
                'description' => 'Connect to MT4/MT5 brokers via mtapi.io',
                'credentials' => ['api_key', 'account_id'],
            ],
            // Future:
            // 'ccxt_crypto' => [
            //     'name' => 'CCXT (Crypto Exchanges)',
            //     'description' => 'Connect to 100+ crypto exchanges',
            //     'credentials' => ['api_key', 'api_secret', 'api_passphrase'],
            // ],
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

