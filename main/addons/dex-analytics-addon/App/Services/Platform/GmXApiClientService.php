<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services\Platform;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GmXApiClientService
{
    public function getPositions(string $wallet): array
    {
        return $this->get('positions', ['wallet' => $wallet]);
    }

    public function getPositionHistory(string $wallet): array
    {
        return $this->get('position_history', ['wallet' => $wallet]);
    }

    public function getFundingHistory(string $wallet): array
    {
        return $this->get('funding_history', ['wallet' => $wallet]);
    }

    public function getLiquidations(string $wallet): array
    {
        return $this->get('liquidations', ['wallet' => $wallet]);
    }

    public function getCurrentPrices(): array
    {
        return $this->get('prices');
    }

    protected function get(string $endpointKey, array $params = []): array
    {
        $endpoint = $this->endpoint($endpointKey);
        if (!$endpoint) {
            return [
                'success' => false,
                'message' => 'Missing GMX endpoint configuration: ' . $endpointKey,
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
                    'message' => 'GMX request failed: ' . $response->status(),
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
        return config('dex-analytics.platforms.gmx', []);
    }
}
