<?php

namespace Addons\TradingManagement\Modules\MarketData\Jobs;

use Addons\TradingManagement\Modules\DataProvider\Services\DataConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fetch All Active Connections Job
 * 
 * Dispatches FetchMarketDataJob for each active connection
 * Scheduled to run every X minutes
 */
class FetchAllActiveConnectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Dispatcher doesn't need retry
    public $timeout = 60;

    /**
     * Execute the job
     */
    public function handle(DataConnectionService $connectionService)
    {
        $activeConnections = $connectionService->getActiveConnections();

        \Log::info('Dispatching FetchMarketDataJob for active connections', [
            'count' => $activeConnections->count(),
        ]);

        foreach ($activeConnections as $connection) {
            try {
                // Get symbols and timeframes from connection settings
                $symbols = $connection->getSymbolsFromSettings();
                $timeframes = $connection->getTimeframesFromSettings();

                if (empty($symbols)) {
                    \Log::warning('Connection has no symbols configured, skipping', [
                        'connection_id' => $connection->id,
                    ]);
                    continue;
                }

                // Dispatch fetch job
                FetchMarketDataJob::dispatch(
                    $connection->id,
                    $symbols,
                    $timeframes,
                    100 // Fetch last 100 candles
                );

            } catch (\Exception $e) {
                \Log::error('Failed to dispatch FetchMarketDataJob', [
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue with next connection
            }
        }
    }
}

