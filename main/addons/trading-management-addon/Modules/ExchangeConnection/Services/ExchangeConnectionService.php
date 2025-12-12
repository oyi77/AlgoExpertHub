<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Services;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiAdapter;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MtapiGrpcAdapter;
use Illuminate\Support\Facades\Log;

/**
 * ExchangeConnectionService
 * 
 * Service for managing exchange connections with health checks and stabilization
 */
class ExchangeConnectionService
{
    /**
     * Test connection health
     * 
     * @param ExchangeConnection $connection
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function testConnection(ExchangeConnection $connection): array
    {
        try {
            $adapter = $this->getAdapter($connection);
            
            if (!$adapter) {
                return [
                    'success' => false,
                    'message' => 'Unsupported connection type or provider',
                    'data' => [],
                ];
            }

            // Update status to testing
            $connection->update(['status' => 'testing']);

            // Test based on connection type
            $result = $this->performHealthCheck($adapter, $connection);

            // Update connection status based on result
            if ($result['success']) {
                $connection->markAsActive();
            } else {
                $connection->markAsError($result['message']);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Connection test failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            $connection->markAsError($e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Perform health check based on adapter type
     * 
     * @param mixed $adapter
     * @param ExchangeConnection $connection
     * @return array
     */
    protected function performHealthCheck($adapter, ExchangeConnection $connection): array
    {
        // Try testConnection method first
        if (method_exists($adapter, 'testConnection')) {
            return $adapter->testConnection();
        }

        // Fallback: Try to fetch balance or account info
        if (method_exists($adapter, 'fetchBalance')) {
            try {
                $balance = $adapter->fetchBalance();
                return [
                    'success' => true,
                    'message' => 'Connection test successful',
                    'data' => ['balance' => $balance],
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch balance: ' . $e->getMessage(),
                    'data' => [],
                ];
            }
        }

        if (method_exists($adapter, 'getAccountInfo')) {
            try {
                $accountInfo = $adapter->getAccountInfo();
                return [
                    'success' => true,
                    'message' => 'Connection test successful',
                    'data' => ['account_info' => $accountInfo],
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Failed to get account info: ' . $e->getMessage(),
                    'data' => [],
                ];
            }
        }

        // If no test method available, return success (connection exists)
        return [
            'success' => true,
            'message' => 'Connection test completed (no specific test method available)',
            'data' => [],
        ];
    }

    /**
     * Get adapter instance for connection
     * 
     * @param ExchangeConnection $connection
     * @return mixed|null
     */
    public function getAdapter(ExchangeConnection $connection)
    {
        $connectionType = $connection->connection_type ?? null;
        $provider = $connection->provider ?? $connection->exchange_name ?? null;
        $type = $connection->type ?? null; // legacy: 'crypto' or 'fx'
        
        // Determine if this is a crypto exchange
        $isCrypto = false;
        
        // Check connection_type first (new field)
        if ($connectionType === 'CRYPTO_EXCHANGE') {
            $isCrypto = true;
        }
        // Check legacy type field
        elseif ($type === 'crypto') {
            $isCrypto = true;
        }
        // Check provider/exchange_name for known crypto exchanges
        elseif ($provider && in_array(strtolower($provider), ['binance', 'coinbase', 'coinbasepro', 'kraken', 'bitfinex', 'okx', 'bybit', 'huobi', 'kucoin', 'gate', 'mexc'])) {
            $isCrypto = true;
        }
        
        // Crypto exchanges always use CCXT adapter
        if ($isCrypto) {
            return new CcxtAdapter(
                $connection->credentials ?? [],
                $provider ?? 'binance'
            );
        }
        
        // For FX brokers (MT4/MT5), select adapter based on provider
        if ($provider === 'metaapi') {
            return new MetaApiAdapter($connection->credentials ?? []);
        } elseif ($provider === 'mtapi_grpc' || 
                  (isset($connection->credentials['provider']) && $connection->credentials['provider'] === 'mtapi_grpc')) {
            $credentials = $connection->credentials ?? [];
            $globalSettings = \App\Services\GlobalConfigurationService::get('mtapi_global_settings', []);
            
            if (!empty($globalSettings['base_url'])) {
                $credentials['base_url'] = $globalSettings['base_url'];
            }
            if (!empty($globalSettings['timeout'])) {
                $credentials['timeout'] = $globalSettings['timeout'];
            }
            
            return new MtapiGrpcAdapter($credentials);
        } else {
            // Default: MTAPI REST adapter (for FX brokers)
            return new MtapiAdapter($connection->credentials ?? []);
        }
    }

    /**
     * Verify connection is stabilized (tested and active)
     * 
     * @param ExchangeConnection $connection
     * @return bool
     */
    public function isStabilized(ExchangeConnection $connection): bool
    {
        // Connection must be active and tested recently
        return $connection->isActive() && 
               $connection->last_tested_at && 
               $connection->last_tested_at->isAfter(now()->subHours(24));
    }

    /**
     * Stabilize connection (test and activate if successful)
     * 
     * @param ExchangeConnection $connection
     * @return array ['success' => bool, 'message' => string]
     */
    public function stabilize(ExchangeConnection $connection, bool $autoActivate = false): array
    {
        $result = $this->testConnection($connection);
        
        if ($result['success']) {
            // If autoActivate is true (e.g., when starting bot), activate the connection
            if ($autoActivate && !$connection->is_active) {
                $connection->update([
                    'status' => 'active',
                    'is_active' => true,
                    'last_tested_at' => now(),
                ]);
            }
            
            return [
                'success' => true,
                'message' => $autoActivate ? 'Connection stabilized and activated' : 'Connection stabilized and ready for activation',
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Connection test failed',
        ];
    }
}
