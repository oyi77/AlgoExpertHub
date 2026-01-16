<?php

declare(strict_types=1);

namespace App\Services\Trading;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TwelveDataService
{
    protected string $baseUrl;
    protected int $cacheTtl;
    protected int $maxRetries;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('twelve_data.base_url', 'https://api.twelvedata.com/v1');
        $this->cacheTtl = config('twelve_data.cache_ttl', 900); // 15 minutes
        $this->maxRetries = 3;
        $this->apiKey = env('TWELVE_DATA_API_KEY');
    }

    /**
     * Get exchange rate for a forex pair
     */
    public function getExchangeRate(string $from, string $to): array
    {
        $symbol = strtoupper($from . '/' . $to);
        $cacheKey = "twelve_data_fx_{$symbol}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($symbol) {
            try {
                $response = Http::timeout(10)
                    ->retry($this->maxRetries)
                    ->get($this->baseUrl . '/fx_rate', [
                        'symbol' => $symbol,
                        'apikey' => $this->apiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['rate'])) {
                        return [
                            'price' => (float) $data['rate'],
                            'source' => 'api',
                            'timestamp' => now()->toISOString(),
                        ];
                    }

                    Log::warning('Twelve Data API unexpected response format', [
                        'symbol' => $symbol,
                        'response' => $data,
                    ]);
                } else {
                    Log::warning('Twelve Data API failed', [
                        'symbol' => $symbol,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Twelve Data API error', [
                    'error' => $e->getMessage(),
                    'symbol' => $symbol,
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return $this->getSimulatedFxRate($from, $to);
        });
    }

    /**
     * Get quote for stock, index, or commodity
     */
    public function getQuote(string $symbol): array
    {
        $symbol = strtoupper($symbol);
        $cacheKey = "twelve_data_quote_{$symbol}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($symbol) {
            try {
                $response = Http::timeout(10)
                    ->retry($this->maxRetries)
                    ->get($this->baseUrl . '/quote', [
                        'symbol' => $symbol,
                        'apikey' => $this->apiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['close'])) {
                        return [
                            'symbol' => $symbol,
                            'price' => (float) $data['close'],
                            'change' => isset($data['change']) ? (float) $data['change'] : 0.0,
                            'percent_change' => isset($data['percent_change']) ? (float) $data['percent_change'] : 0.0,
                            'volume' => isset($data['volume']) ? (int) $data['volume'] : 0,
                            'source' => 'api',
                            'timestamp' => now()->toISOString(),
                        ];
                    }

                    Log::warning('Twelve Data API unexpected response format', [
                        'symbol' => $symbol,
                        'response' => $data,
                    ]);
                } else {
                    Log::warning('Twelve Data API failed', [
                        'symbol' => $symbol,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Twelve Data API error', [
                    'error' => $e->getMessage(),
                    'symbol' => $symbol,
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return $this->getSimulatedQuote($symbol);
        });
    }

    /**
     * Generate simulated forex rate as fallback
     */
    protected function getSimulatedFxRate(string $from, string $to): array
    {
        $rates = [
            'EUR/USD' => 1.0850,
            'GBP/USD' => 1.2750,
            'USD/JPY' => 148.50,
            'AUD/USD' => 0.6650,
            'USD/CAD' => 1.3450,
            'USD/CHF' => 0.8950,
            'NZD/USD' => 0.6120,
            'EUR/JPY' => 161.20,
            'GBP/JPY' => 189.50,
            'AUD/JPY' => 98.80,
        ];

        $symbol = strtoupper($from . '/' . $to);
        $rate = $rates[$symbol] ?? 1.0;
        $change = $this->generateRealisticChange(0.005, 0.02);

        return [
            'price' => round($rate * (1 + $change / 100), 4),
            'change_24h' => $change,
            'change_1h' => $change * 0.05,
            'change_7d' => $change * 1.2,
            'volume' => rand(10000000, 500000000),
            'source' => 'simulated',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Generate simulated quote as fallback
     */
    protected function getSimulatedQuote(string $symbol): array
    {
        $basePrice = rand(100, 5000) / 100;
        $change = $this->generateRealisticChange(-1.5, 1.5);

        return [
            'symbol' => $symbol,
            'price' => round($basePrice * (1 + $change / 100), 2),
            'change' => round($basePrice * $change / 100, 2),
            'percent_change' => round($change, 2),
            'volume' => rand(100000, 10000000),
            'source' => 'simulated',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Generate realistic price changes using normal distribution
     */
    protected function generateRealisticChange(float $min = -2.0, float $max = 2.0): float
    {
        $mean = 0;
        $stdDev = ($max - $min) / 6;

        do {
            $u1 = mt_rand() / mt_getrandmax();
            $u2 = mt_rand() / mt_getrandmax();
            $z = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
            $change = $mean + $stdDev * $z;
        } while ($change < $min || $change > $max);

        return round($change, 2);
    }

    /**
     * Check if API key is configured
     */
    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }
}
