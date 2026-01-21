<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DexLiquidationTrackingService
{
    public function recordLiquidation(array $normalizedLiquidation): void
    {
        DB::table('dex_liquidation_events')->insert([
            'watchlist_id' => Arr::get($normalizedLiquidation, 'watchlist_id'),
            'wallet_address' => Arr::get($normalizedLiquidation, 'wallet_address'),
            'platform' => Arr::get($normalizedLiquidation, 'platform'),
            'symbol' => Arr::get($normalizedLiquidation, 'symbol'),
            'side' => Arr::get($normalizedLiquidation, 'side'),
            'liquidation_price' => Arr::get($normalizedLiquidation, 'liquidation_price'),
            'position_size' => Arr::get($normalizedLiquidation, 'position_size'),
            'loss_amount' => Arr::get($normalizedLiquidation, 'loss_amount'),
            'liquidated_at' => Arr::get($normalizedLiquidation, 'liquidated_at', now()->toDateTimeString()),
            'raw_payload' => json_encode(Arr::get($normalizedLiquidation, 'raw_payload', [])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recordProvenance($normalizedLiquidation, 'liquidation_record');
    }

    protected function recordProvenance(array $payload, string $operation): void
    {
        DB::table('dex_provenance_logs')->insert([
            'watchlist_id' => Arr::get($payload, 'watchlist_id'),
            'wallet_address' => Arr::get($payload, 'wallet_address'),
            'platform' => Arr::get($payload, 'platform'),
            'source' => Arr::get($payload, 'source', 'api'),
            'operation' => $operation,
            'payload_hash' => hash('sha256', json_encode(Arr::get($payload, 'raw_payload', []))),
            'recorded_at' => now(),
            'metadata' => json_encode(['service' => static::class]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
