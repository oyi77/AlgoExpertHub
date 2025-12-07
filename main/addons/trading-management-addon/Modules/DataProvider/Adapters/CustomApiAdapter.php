<?php

namespace Addons\TradingManagement\Modules\DataProvider\Adapters;

use Addons\TradingManagement\Shared\Contracts\DataProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Custom API Adapter
 * 
 * Connects to custom data provider via REST API
 */
class CustomApiAdapter implements DataProviderInterface
{
    protected $credentials;

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Test connection
     */
    public function test(): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->credentials['api_url'] . '/ping');
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch candle data
     */
    public function fetchCandles(string $symbol, string $timeframe, int $limit = 100): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->credentials['api_url'] . '/candles', [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'limit' => $limit,
                ]);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch candles',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch current price
     */
    public function fetchCurrentPrice(string $symbol): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->credentials['api_url'] . '/price', [
                    'symbol' => $symbol,
                ]);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch price',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get request headers
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . ($this->credentials['api_key'] ?? ''),
            'X-API-Secret' => $this->credentials['api_secret'] ?? '',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Connect to data provider
     */
    public function connect(array $credentials): bool
    {
        $this->credentials = array_merge($this->credentials, $credentials);
        $result = $this->test();
        return $result['success'] ?? false;
    }

    /**
     * Disconnect
     */
    public function disconnect(): void
    {
        // Nothing to disconnect for REST API
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return !empty($this->credentials['api_url']);
    }

    /**
     * Fetch OHLCV data (interface requirement)
     */
    public function fetchOHLCV(string $symbol, string $timeframe, int $limit = 100, ?int $since = null): array
    {
        $result = $this->fetchCandles($symbol, $timeframe, $limit);
        if (isset($result['success']) && $result['success']) {
            return $result['data'] ?? [];
        }
        throw new \Exception($result['message'] ?? 'Failed to fetch OHLCV data');
    }

    /**
     * Fetch tick data
     */
    public function fetchTicks(string $symbol, int $limit = 100): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->credentials['api_url'] . '/ticks', [
                    'symbol' => $symbol,
                    'limit' => $limit,
                ]);
            
            if ($response->successful()) {
                return $response->json('data', []);
            }
            
            throw new \Exception('Failed to fetch ticks');
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch ticks: ' . $e->getMessage());
        }
    }

    /**
     * Get account info
     */
    public function getAccountInfo(): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->credentials['api_url'] . '/account');
            
            if ($response->successful()) {
                return $response->json('data', []);
            }
            
            throw new \Exception('Failed to fetch account info');
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch account info: ' . $e->getMessage());
        }
    }

    /**
     * Get available symbols
     */
    public function getAvailableSymbols(): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->credentials['api_url'] . '/symbols');
            
            if ($response->successful()) {
                return $response->json('data', []);
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Test connection (interface requirement)
     */
    public function testConnection(): array
    {
        return $this->test();
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'custom_api';
    }
}

