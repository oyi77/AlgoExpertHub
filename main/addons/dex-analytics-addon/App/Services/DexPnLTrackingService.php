<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DexPnLTrackingService
{
    public function recordPnL(array $normalizedPnl): void
    {
        DB::table('dex_pnl_records')->insert([
            'watchlist_id' => Arr::get($normalizedPnl, 'watchlist_id'),
            'wallet_address' => Arr::get($normalizedPnl, 'wallet_address'),
            'platform' => Arr::get($normalizedPnl, 'platform'),
            'symbol' => Arr::get($normalizedPnl, 'symbol'),
            'side' => Arr::get($normalizedPnl, 'side'),
            'entry_price' => Arr::get($normalizedPnl, 'entry_price'),
            'exit_price' => Arr::get($normalizedPnl, 'exit_price'),
            'size' => Arr::get($normalizedPnl, 'size'),
            'realized_pnl' => Arr::get($normalizedPnl, 'realized_pnl'),
            'fees' => Arr::get($normalizedPnl, 'fees'),
            'funding_cost' => Arr::get($normalizedPnl, 'funding_cost'),
            'closed_at' => Arr::get($normalizedPnl, 'closed_at', now()->toDateTimeString()),
            'raw_payload' => json_encode(Arr::get($normalizedPnl, 'raw_payload', [])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recordProvenance($normalizedPnl, 'pnl_record');
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
