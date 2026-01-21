<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Jobs;

use Addons\DexAnalyticsAddon\App\Models\DexTraderWatchlist;
use Addons\DexAnalyticsAddon\App\Services\Platform\GmxApiClientService;
use Addons\DexAnalyticsAddon\App\Services\Platform\HyperliquidApiClientService;
use Addons\DexAnalyticsAddon\App\Services\Platform\AsterApiClientService;
use Addons\DexAnalyticsAddon\App\Services\Platform\LighterApiClientService;
use Addons\DexAnalyticsAddon\App\Services\Platform\DydxV4ApiClientService;
use Addons\DexAnalyticsAddon\App\Services\DexAnalyticsNormalizationService;
use Addons\DexAnalyticsAddon\App\Services\DexPositionSnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollDexPositionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    protected array $platformServices = [];

    public function __construct()
    {
    }

    public function handle(
        GmxApiClientService $gmxService,
        HyperliquidApiClientService $hyperliquidService,
        AsterApiClientService $asterService,
        LighterApiClientService $lighterService,
        DydxV4ApiClientService $dydxService,
        DexAnalyticsNormalizationService $normalizationService,
        DexPositionSnapshotService $snapshotService
    ): void {
        try {
            $this->platformServices = [
                'gmx' => $gmxService,
                'hyperliquid' => $hyperliquidService,
                'aster' => $asterService,
                'lighter' => $lighterService,
                'dydx_v4' => $dydxService,
            ];

            $traders = DexTraderWatchlist::where('is_active', true)->get();

            if ($traders->isEmpty()) {
                Log::info('DEX Analytics: No active traders in watchlist');
                return;
            }

            Log::info('DEX Analytics: Polling positions', [
                'trader_count' => $traders->count(),
            ]);

            foreach ($traders as $trader) {
                try {
                    $this->pollTraderPositions($trader, $normalizationService, $snapshotService);
                } catch (\Exception $e) {
                    Log::error('DEX Analytics: Failed to poll trader positions', [
                        'trader_id' => $trader->id,
                        'wallet' => $trader->wallet_address,
                        'platform' => $trader->platform,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('DEX Analytics: Position polling completed');

        } catch (\Exception $e) {
            Log::error('DEX Analytics: Position polling job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function pollTraderPositions(
        DexTraderWatchlist $trader,
        DexAnalyticsNormalizationService $normalizationService,
        DexPositionSnapshotService $snapshotService
    ): void {
        $platform = $trader->platform;

        if (!isset($this->platformServices[$platform])) {
            Log::warning('DEX Analytics: Unknown platform', [
                'platform' => $platform,
                'trader_id' => $trader->id,
            ]);
            return;
        }

        $service = $this->platformServices[$platform];

        $rawPositions = $service->fetchPositions($trader->wallet_address);

        if (empty($rawPositions)) {
            Log::debug('DEX Analytics: No positions found', [
                'wallet' => $trader->wallet_address,
                'platform' => $platform,
            ]);
            return;
        }

        $normalizedPositions = $normalizationService->normalizePositions($rawPositions, $platform);

        foreach ($normalizedPositions as $position) {
            try {
                $snapshotService->storeSnapshot(
                    $trader->wallet_address,
                    $platform,
                    $position
                );
            } catch (\Exception $e) {
                Log::error('DEX Analytics: Failed to store position snapshot', [
                    'wallet' => $trader->wallet_address,
                    'platform' => $platform,
                    'symbol' => $position['symbol'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $trader->update([
            'last_polled_at' => now(),
            'position_count' => count($normalizedPositions),
        ]);

        Log::info('DEX Analytics: Polled trader positions', [
            'wallet' => $trader->wallet_address,
            'platform' => $platform,
            'position_count' => count($normalizedPositions),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DEX Analytics: Position polling job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
