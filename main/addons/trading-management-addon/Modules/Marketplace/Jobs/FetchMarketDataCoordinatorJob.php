<?php

namespace Addons\TradingManagement\Modules\Marketplace\Jobs;

use Addons\TradingManagement\Modules\Marketplace\Models\MarketDataSubscription;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Cache, Log};

class FetchMarketDataCoordinatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function handle(MarketDataService $marketDataService)
    {
        try {
            Log::info('FetchMarketDataCoordinatorJob: Starting');

            $activeSymbols = MarketDataSubscription::getActiveSymbols();
            $totalFetched = 0;
            $served = [];

            foreach ($activeSymbols as $symbol) {
                $timeframes = MarketDataSubscription::getActiveTimeframesForSymbol($symbol);
                
                foreach ($timeframes as $timeframe) {
                    $subscribers = MarketDataSubscription::active()
                        ->where('symbol', $symbol)
                        ->where('timeframe', $timeframe)
                        ->count();

                    if ($subscribers === 0) continue;

                    // Fetch ONCE for all subscribers
                    $fetched = $this->fetchAndCache($symbol, $timeframe, $marketDataService);
                    
                    if ($fetched) {
                        $totalFetched++;
                        $served[] = [
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'subscribers' => $subscribers,
                        ];

                        // Update subscription access
                        MarketDataSubscription::active()
                            ->where('symbol', $symbol)
                            ->where('timeframe', $timeframe)
                            ->each(function($sub) {
                                $sub->recordAccess();
                            });
                    }
                }
            }

            Log::info('FetchMarketDataCoordinatorJob: Completed', [
                'symbols_fetched' => $totalFetched,
                'served' => $served,
            ]);

        } catch (\Exception $e) {
            Log::error('FetchMarketDataCoordinatorJob: Failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function fetchAndCache(string $symbol, string $timeframe, MarketDataService $service): bool
    {
        try {
            // Get first available data connection
            $connection = DataConnection::where('is_active', true)->first();
            
            if (!$connection) {
                return false;
            }

            // Fetch latest candle
            $adapter = app()->make("Addons\\TradingManagement\\Modules\\DataProvider\\Services\\ConnectionService")
                ->getAdapter($connection);

            if (!$adapter) {
                return false;
            }

            $ohlcv = $adapter->fetchOHLCV($symbol, $timeframe, 1);
            
            if (!empty($ohlcv)) {
                $service->store($connection, $symbol, $timeframe, $ohlcv);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('FetchMarketDataCoordinatorJob: Fetch failed', [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}


