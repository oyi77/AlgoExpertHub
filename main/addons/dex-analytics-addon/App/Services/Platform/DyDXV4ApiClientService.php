<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services\Platform;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class DyDXV4ApiClientService
{
    public function getPositions(string $wallet, int $subaccount = 0): array
    {
        return $this->get('positions', ['wallet' => $wallet, 'subaccount' => $subaccount]);
    }

    public function getFills(string $wallet, int $subaccount = 0): array
    {
        return $this->get('fills', ['wallet' => $wallet, 'subaccount' => $subaccount]);
    }

    public function getPnLHistory(string $wallet, int $subaccount = 0): array
    {
        return $this->get('pnl', ['wallet' => $wallet, 'subaccount' => $subaccount]);
    }

    public function getFundingHistory(string $wallet, int $subaccount = 0): array
    {
        return $this->get('funding', ['wallet' => $wallet, 'subaccount' => $subaccount]);
    }

    public function getMarkets(): array
    {
        return $this->get('markets');
    }

    protected function get(string $endpointKey, array $params = []): array
    {
        $endpoint = $this->endpoint($endpointKey);
        if (!$endpoint) {
            return [
                'success' => false,
                'message' => 'Missing dYdX endpoint configuration: ' . $endpointKey,
            ];
        }

        $config = $this->config();

        try {
            $response = Http::timeout($config['timeout_seconds'])
                ->retry(2, 200)
                ->get($endpoint, $params);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'dYdX request failed: ' . $response->status(),
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

    protected function config(): array
    {
        return config('dex-analytics.platforms.dydx_v4', []);
    }
}
