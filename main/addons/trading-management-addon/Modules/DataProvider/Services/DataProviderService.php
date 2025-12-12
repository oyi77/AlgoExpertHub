<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use Addons\TradingManagement\Modules\DataProvider\Adapters\CCXTAdapter;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter;
use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

class DataProviderService
{
    protected array $providers = [];

    /**
     * Get or create a data provider instance
     * 
     * @param string $type Provider type (mtapi, ccxt)
     * @param string|null $identifier Specific identifier (e.g., exchange ID)
     * @param array $credentials Credentials for connection
     * @return DataProviderInterface
     * @throws Exception
     */
    public function getProvider(string $type, ?string $identifier = null, array $credentials = []): DataProviderInterface
    {
        $key = $type . ($identifier ? '_' . $identifier : '');

        if (isset($this->providers[$key])) {
            return $this->providers[$key];
        }

        $provider = match ($type) {
            'mtapi' => new MtapiAdapter($credentials),
            'ccxt' => new CCXTAdapter($identifier, $credentials),
            default => throw new Exception("Unknown provider type: $type"),
        };

        $this->providers[$key] = $provider;

        return $provider;
    }

    /**
     * Fetch OHLCV data from a specific connection
     * 
     * @param array $connection Connection configuration
     * @param string $symbol
     * @param string $timeframe
     * @param int $limit
     * @return array
     */
    public function fetchMarketData(array $connection, string $symbol, string $timeframe, int $limit = 100): array
    {
        try {
            $provider = $this->getProvider(
                $connection['type'], 
                $connection['identifier'] ?? null, 
                $connection['credentials'] ?? []
            );

            if (!$provider->isConnected()) {
                $provider->connect($connection['credentials'] ?? []);
            }

            return $provider->fetchOHLCV($symbol, $timeframe, $limit);
        } catch (Exception $e) {
            Log::error("Failed to fetch market data", [
                'connection_id' => $connection['id'] ?? 'unknown',
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Test connection
     * 
     * @param string $type
     * @param string|null $identifier
     * @param array $credentials
     * @return array
     */
    public function testConnection(string $type, ?string $identifier, array $credentials): array
    {
        try {
            $provider = $this->getProvider($type, $identifier, $credentials);
            return $provider->testConnection();
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'latency' => 0
            ];
        }
    }
}
