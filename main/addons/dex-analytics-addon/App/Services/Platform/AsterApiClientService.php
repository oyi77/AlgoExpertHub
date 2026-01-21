<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services\Platform;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class AsterApiClientService
{
    public function getPositions(string $wallet): array
    {
        return $this->get('positions', ['wallet' => $wallet]);
    }

    public function getPnLHistory(string $wallet): array
    {
        return $this->get('pnl', ['wallet' => $wallet]);
    }

    public function getFundingHistory(string $wallet): array
    {
        return $this->get('funding', ['wallet' => $wallet]);
    }

    public function getLiquidations(string $wallet): array
    {
        return $this->get('liquidations', ['wallet' => $wallet]);
    }

    protected function get(string $endpointKey, array $params = []): array
    {
        $endpoint = $this->endpoint($endpointKey);
        if (!$endpoint) {
            return [
                'success' => false,
                'message' => 'Missing Aster endpoint configuration: ' . $endpointKey,
            ];
        }

        $config = $this->config();

        try {
            $response = Http::timeout($config['timeout_seconds'])
                ->retry(2, 200)
                ->withHeaders($this->headers())
                ->get($endpoint, $params);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Aster request failed: ' . $response->status(),
                ];
            }

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function endpoint(string $key): ?string
    {
        return Arr::get($this->config(), 'endpoints.' . $key);
    }

    protected function headers(): array
    {
        $apiKey = Arr::get($this->config(), 'api_key');

        return $apiKey ? ['Authorization' => 'Bearer ' . $apiKey] : [];
    }

    protected function config(): array
    {
        return config('dex-analytics.platforms.aster', []);
    }
}
