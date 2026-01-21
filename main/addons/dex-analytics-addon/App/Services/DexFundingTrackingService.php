<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DexFundingTrackingService
{
    public function recordFunding(array $normalizedFunding): void
    {
        DB::table('dex_funding_logs')->insert([
            'watchlist_id' => Arr::get($normalizedFunding, 'watchlist_id'),
            'wallet_address' => Arr::get($normalizedFunding, 'wallet_address'),
            'platform' => Arr::get($normalizedFunding, 'platform'),
            'symbol' => Arr::get($normalizedFunding, 'symbol'),
            'funding_rate' => Arr::get($normalizedFunding, 'funding_rate'),
            'funding_payment' => Arr::get($normalizedFunding, 'funding_payment'),
            'position_size' => Arr::get($normalizedFunding, 'position_size'),
            'paid_at' => Arr::get($normalizedFunding, 'paid_at', now()->toDateTimeString()),
            'raw_payload' => json_encode(Arr::get($normalizedFunding, 'raw_payload', [])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recordProvenance($normalizedFunding, 'funding_record');
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
