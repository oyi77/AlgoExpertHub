<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DexPositionSnapshotService
{
    public function storeSnapshot(string $walletAddress, string $platform, array $normalizedPosition): void
    {
        $normalizedPosition['wallet_address'] = $walletAddress;
        $normalizedPosition['platform'] = $platform;

        $this->capturePosition($normalizedPosition);
    }

    public function capturePosition(array $normalizedPosition): void
    {
        DB::table('dex_position_snapshots')->insert([
            'watchlist_id' => Arr::get($normalizedPosition, 'watchlist_id'),
            'wallet_address' => Arr::get($normalizedPosition, 'wallet_address'),
            'platform' => Arr::get($normalizedPosition, 'platform'),
            'symbol' => Arr::get($normalizedPosition, 'symbol'),
            'side' => Arr::get($normalizedPosition, 'side'),
            'size' => Arr::get($normalizedPosition, 'size'),
            'entry_price' => Arr::get($normalizedPosition, 'entry_price'),
            'mark_price' => Arr::get($normalizedPosition, 'mark_price'),
            'liquidation_price' => Arr::get($normalizedPosition, 'liquidation_price'),
            'unrealized_pnl' => Arr::get($normalizedPosition, 'unrealized_pnl'),
            'leverage' => Arr::get($normalizedPosition, 'leverage'),
            'margin' => Arr::get($normalizedPosition, 'margin'),
            'snapshot_at' => Arr::get($normalizedPosition, 'snapshot_at', now()->toDateTimeString()),
            'raw_payload' => json_encode(Arr::get($normalizedPosition, 'raw_payload', [])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recordProvenance($normalizedPosition, 'position_snapshot');
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
