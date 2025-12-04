<?php

namespace Addons\TradingManagement\Modules\MarketData\Jobs;

use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\DataProvider\Services\DataConnectionService;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Backfill Historical Data Job
 * 
 * Fetches historical market data for a connection
 * Used when setting up new connections or filling gaps
 */
class BackfillHistoricalDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $dataConnectionId;
    public string $symbol;
    public string $timeframe;
    public int $startTimestamp;
    public int $endTimestamp;
    public int $chunkSize;

    public $tries = 3;
    public $timeout = 600; // 10 minutes for large backfills
    public $backoff = [60, 120, 300];

    /**
     * Create a new job instance
     */
    public function __construct(
        int $dataConnectionId,
        string $symbol,
        string $timeframe,
        int $startTimestamp,
        int $endTimestamp,
        int $chunkSize = 1000
    ) {
        $this->dataConnectionId = $dataConnectionId;
        $this->symbol = $symbol;
        $this->timeframe = $timeframe;
        $this->startTimestamp = $startTimestamp;
        $this->endTimestamp = $endTimestamp;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Execute the job
     */
    public function handle(DataConnectionService $connectionService, MarketDataService $marketDataService)
    {
        $connection = DataConnection::find($this->dataConnectionId);

        if (!$connection) {
            \Log::error('Data connection not found for backfill', ['id' => $this->dataConnectionId]);
            return;
        }

        try {
            $adapter = $connectionService->getAdapter($connection);

            $currentTimestamp = $this->startTimestamp;
            $totalFetched = 0;

            \Log::info('Starting backfill', [
                'connection_id' => $connection->id,
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'start' => date('Y-m-d H:i:s', $this->startTimestamp),
                'end' => date('Y-m-d H:i:s', $this->endTimestamp),
            ]);

            // Fetch data in chunks to avoid timeouts and respect rate limits
            while ($currentTimestamp < $this->endTimestamp) {
                try {
                    // Fetch chunk
                    $ohlcvData = $adapter->fetchOHLCV(
                        $this->symbol,
                        $this->timeframe,
                        $this->chunkSize,
                        $currentTimestamp
                    );

                    if (empty($ohlcvData)) {
                        \Log::warning('No data returned for chunk', [
                            'symbol' => $this->symbol,
                            'timeframe' => $this->timeframe,
                            'timestamp' => $currentTimestamp,
                        ]);
                        break; // No more data available
                    }

                    // Store chunk
                    $inserted = $marketDataService->store($connection, $this->symbol, $this->timeframe, $ohlcvData);
                    $totalFetched += $inserted;

                    // Update current timestamp to last fetched candle + 1
                    $lastCandle = end($ohlcvData);
                    $currentTimestamp = ($lastCandle['timestamp'] ?? $lastCandle[0]) + 1;

                    \Log::info('Backfill chunk complete', [
                        'symbol' => $this->symbol,
                        'timeframe' => $this->timeframe,
                        'candles' => count($ohlcvData),
                        'inserted' => $inserted,
                        'total_fetched' => $totalFetched,
                        'progress' => round((($currentTimestamp - $this->startTimestamp) / ($this->endTimestamp - $this->startTimestamp)) * 100, 2) . '%',
                    ]);

                    // Respect rate limits: Sleep between chunks
                    sleep(1);

                } catch (\Exception $e) {
                    \Log::error('Backfill chunk failed', [
                        'symbol' => $this->symbol,
                        'timeframe' => $this->timeframe,
                        'timestamp' => $currentTimestamp,
                        'error' => $e->getMessage(),
                    ]);

                    // If too many errors, stop backfill
                    if (str_contains($e->getMessage(), 'rate limit')) {
                        \Log::warning('Rate limit hit, stopping backfill', [
                            'total_fetched' => $totalFetched,
                        ]);
                        throw $e; // Trigger retry later
                    }

                    // Continue with next chunk
                    $currentTimestamp += $this->chunkSize;
                }
            }

            // Log completion
            $connection->logAction('fetch_data', 'success', "Backfilled {$totalFetched} candles for {$this->symbol} {$this->timeframe}", [
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'total_candles' => $totalFetched,
                'start_date' => date('Y-m-d', $this->startTimestamp),
                'end_date' => date('Y-m-d', $this->endTimestamp),
            ]);

            \Log::info('Backfill completed', [
                'connection_id' => $connection->id,
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'total_candles' => $totalFetched,
            ]);

        } catch (\Exception $e) {
            $connection->logAction('fetch_data', 'failed', 'Backfill failed: ' . $e->getMessage());

            \Log::error('BackfillHistoricalDataJob failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('BackfillHistoricalDataJob permanently failed', [
            'connection_id' => $this->dataConnectionId,
            'symbol' => $this->symbol,
            'timeframe' => $this->timeframe,
            'error' => $exception->getMessage(),
        ]);
    }
}

