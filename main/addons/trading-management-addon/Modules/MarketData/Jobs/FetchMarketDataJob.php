<?php

namespace Addons\TradingManagement\Modules\MarketData\Jobs;

use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\DataProvider\Services\DataConnectionService;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use Addons\TradingManagement\Shared\Events\DataReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fetch Market Data Job
 * 
 * Fetches market data from a data connection and stores it
 * Dispatched by scheduler or manually
 */
class FetchMarketDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $dataConnectionId;
    public array $symbols;
    public array $timeframes;
    public int $limit;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [30, 60, 120]; // Exponential backoff

    /**
     * Create a new job instance
     */
    public function __construct(int $dataConnectionId, array $symbols = [], array $timeframes = [], int $limit = 100)
    {
        $this->dataConnectionId = $dataConnectionId;
        $this->symbols = $symbols;
        $this->timeframes = $timeframes;
        $this->limit = $limit;
    }

    /**
     * Execute the job
     */
    public function handle(DataConnectionService $connectionService, MarketDataService $marketDataService)
    {
        $connection = DataConnection::find($this->dataConnectionId);

        if (!$connection) {
            \Log::error('Data connection not found', ['id' => $this->dataConnectionId]);
            return;
        }

        if (!$connection->isActive()) {
            \Log::info('Skipping inactive connection', ['id' => $connection->id]);
            return;
        }

        try {
            // Get adapter
            $adapter = $connectionService->getAdapter($connection);

            // Get symbols and timeframes from settings if not provided
            $symbols = !empty($this->symbols) 
                ? $this->symbols 
                : $connection->getSymbolsFromSettings();
            
            $timeframes = !empty($this->timeframes)
                ? $this->timeframes
                : $connection->getTimeframesFromSettings();

            if (empty($symbols)) {
                \Log::warning('No symbols configured for connection', ['id' => $connection->id]);
                return;
            }

            $totalFetched = 0;

            // Fetch data for each symbol/timeframe combination
            foreach ($symbols as $symbol) {
                foreach ($timeframes as $timeframe) {
                    try {
                        // Fetch OHLCV data
                        $ohlcvData = $adapter->fetchOHLCV($symbol, $timeframe, $this->limit);

                        if (empty($ohlcvData)) {
                            continue;
                        }

                        // Store in database
                        $inserted = $marketDataService->store($connection, $symbol, $timeframe, $ohlcvData);
                        $totalFetched += $inserted;

                        // Dispatch DataReceived event
                        event(new DataReceived(
                            $connection->id,
                            $symbol,
                            $timeframe,
                            $ohlcvData,
                            $connection->type
                        ));

                        \Log::info('Fetched market data', [
                            'connection_id' => $connection->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'candles' => count($ohlcvData),
                            'inserted' => $inserted,
                        ]);

                    } catch (\Exception $e) {
                        \Log::error('Failed to fetch market data for symbol/timeframe', [
                            'connection_id' => $connection->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue with next symbol/timeframe
                    }
                }
            }

            // Update connection timestamps
            $connection->updateLastConnected();
            $connection->updateLastUsed();

            // Log success
            $connection->logAction('fetch_data', 'success', "Fetched {$totalFetched} new candles", [
                'symbols' => $symbols,
                'timeframes' => $timeframes,
                'candles_inserted' => $totalFetched,
            ]);

        } catch (\Exception $e) {
            // Mark connection as error
            $connection->markAsError($e->getMessage());
            $connection->logAction('fetch_data', 'failed', $e->getMessage());

            \Log::error('FetchMarketDataJob failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('FetchMarketDataJob permanently failed', [
            'connection_id' => $this->dataConnectionId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Mark connection as error
        $connection = DataConnection::find($this->dataConnectionId);
        if ($connection) {
            $connection->markAsError('Job failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage());
        }
    }
}

