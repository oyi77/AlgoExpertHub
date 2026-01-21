<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DexAnalyticsNormalizationService
{
    public function normalizePositions(array $positions, string $platform): array
    {
        $normalized = [];

        foreach ($positions as $position) {
            $normalized[] = $this->normalizePosition($platform, $position);
        }

        return $normalized;
    }

    public function normalizePosition(string $platform, array $payload): array
    {
        return [
            'platform' => $platform,
            'wallet_address' => $this->firstValue($payload, ['wallet', 'wallet_address', 'address', 'user']),
            'symbol' => $this->firstValue($payload, ['symbol', 'market', 'asset']),
            'side' => $this->firstValue($payload, ['side', 'direction']),
            'size' => $this->firstValue($payload, ['size', 'position_size', 'quantity']),
            'entry_price' => $this->firstValue($payload, ['entry_price', 'average_entry_price', 'avg_entry_price']),
            'mark_price' => $this->firstValue($payload, ['mark_price', 'price']),
            'liquidation_price' => $this->firstValue($payload, ['liquidation_price', 'liq_price']),
            'unrealized_pnl' => $this->firstValue($payload, ['unrealized_pnl', 'upl', 'pnl_unrealized']),
            'leverage' => $this->firstValue($payload, ['leverage']),
            'margin' => $this->firstValue($payload, ['margin', 'collateral']),
            'snapshot_at' => $this->resolveTimestamp($payload, ['timestamp', 'snapshot_at', 'updated_at']),
            'raw_payload' => $payload,
        ];
    }

    public function normalizePnl(string $platform, array $payload): array
    {
        return [
            'platform' => $platform,
            'wallet_address' => $this->firstValue($payload, ['wallet', 'wallet_address', 'address', 'user']),
            'symbol' => $this->firstValue($payload, ['symbol', 'market', 'asset']),
            'side' => $this->firstValue($payload, ['side', 'direction']),
            'entry_price' => $this->firstValue($payload, ['entry_price', 'avg_entry_price']),
            'exit_price' => $this->firstValue($payload, ['exit_price', 'close_price']),
            'size' => $this->firstValue($payload, ['size', 'position_size', 'quantity']),
            'realized_pnl' => $this->firstValue($payload, ['realized_pnl', 'pnl', 'profit']),
            'fees' => $this->firstValue($payload, ['fees', 'fee']),
            'funding_cost' => $this->firstValue($payload, ['funding_cost', 'funding']),
            'closed_at' => $this->resolveTimestamp($payload, ['closed_at', 'timestamp', 'time']),
            'raw_payload' => $payload,
        ];
    }

    public function normalizeFunding(string $platform, array $payload): array
    {
        return [
            'platform' => $platform,
            'wallet_address' => $this->firstValue($payload, ['wallet', 'wallet_address', 'address', 'user']),
            'symbol' => $this->firstValue($payload, ['symbol', 'market', 'asset']),
            'funding_rate' => $this->firstValue($payload, ['funding_rate', 'rate']),
            'funding_payment' => $this->firstValue($payload, ['funding_payment', 'payment', 'amount']),
            'position_size' => $this->firstValue($payload, ['position_size', 'size', 'quantity']),
            'paid_at' => $this->resolveTimestamp($payload, ['paid_at', 'timestamp', 'time']),
            'raw_payload' => $payload,
        ];
    }

    public function normalizeLiquidation(string $platform, array $payload): array
    {
        return [
            'platform' => $platform,
            'wallet_address' => $this->firstValue($payload, ['wallet', 'wallet_address', 'address', 'user']),
            'symbol' => $this->firstValue($payload, ['symbol', 'market', 'asset']),
            'side' => $this->firstValue($payload, ['side', 'direction']),
            'liquidation_price' => $this->firstValue($payload, ['liquidation_price', 'liq_price']),
            'position_size' => $this->firstValue($payload, ['position_size', 'size', 'quantity']),
            'loss_amount' => $this->firstValue($payload, ['loss_amount', 'loss', 'amount']),
            'liquidated_at' => $this->resolveTimestamp($payload, ['liquidated_at', 'timestamp', 'time']),
            'raw_payload' => $payload,
        ];
    }

    protected function firstValue(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (Arr::has($payload, $key)) {
                $value = Arr::get($payload, $key);
                if (!is_null($value)) {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function resolveTimestamp(array $payload, array $keys): string
    {
        $value = $this->firstValue($payload, $keys);

        if (is_numeric($value)) {
            return now()->setTimestamp((int) $value)->toDateTimeString();
        }

        if (is_string($value) && Str::length($value) > 0) {
            return $value;
        }

        return now()->toDateTimeString();
    }
}
