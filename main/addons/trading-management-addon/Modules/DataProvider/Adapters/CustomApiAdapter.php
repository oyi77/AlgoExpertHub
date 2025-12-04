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
}

