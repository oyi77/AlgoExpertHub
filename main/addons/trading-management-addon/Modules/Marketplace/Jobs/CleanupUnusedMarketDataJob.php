<?php

namespace Addons\TradingManagement\Modules\Marketplace\Jobs;

use Addons\TradingManagement\Modules\Marketplace\Models\MarketDataSubscription;
use Addons\TradingManagement\Modules\MarketData\Models\MarketData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupUnusedMarketDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 600;

    public function handle()
    {
        try {
            Log::info('CleanupUnusedMarketDataJob: Starting');

            // Delete unused subscriptions
            $unusedSubs = MarketDataSubscription::unused(30)->delete();

            // Get symbols that have no active subscriptions
            $activeSymbols = MarketDataSubscription::active()
                ->distinct()
                ->pluck('symbol')
                ->toArray();

            // Delete old data for symbols with no subscribers
            $cutoffDays = 30;
            $cutoffTimestamp = now()->subDays($cutoffDays)->timestamp;

            $deletedCandles = MarketData::where('timestamp', '<', $cutoffTimestamp)
                ->whereNotIn('symbol', $activeSymbols)
                ->delete();

            Log::info('CleanupUnusedMarketDataJob: Completed', [
                'deleted_subscriptions' => $unusedSubs,
                'deleted_candles' => $deletedCandles,
            ]);

        } catch (\Exception $e) {
            Log::error('CleanupUnusedMarketDataJob: Failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}


