<?php

namespace Addons\TradingManagement\Modules\DataProvider\Jobs;

use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory;
use Addons\TradingManagement\Shared\Events\DataReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fetch Market Data Job
 * 
 * Fetches OHLCV data from active data connections
 * Dispatched every 5 minutes (configured in scheduler)
 */
class FetchMarketDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected DataConnection $connection;
    protected string $symbol;
    protected string $timeframe;
    protected ?int $limit;

    /**
     * Create a new job instance.
     */
    public function __construct(DataConnection $connection, string $symbol, string $timeframe, ?int $limit = 100)
    {
        $this->connection = $connection;
        $this->symbol = $symbol;
        $this->timeframe = $timeframe;
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(AdapterFactory $adapterFactory): void
    {
        try {
            // Get adapter for connection
            $adapter = $adapterFactory->make($this->connection);

            // Connect
            if (!$adapter->connect($this->connection->getDecryptedCredentials())) {
                throw new \Exception('Failed to connect to data provider');
            }

            // Fetch candles
            $candles = $adapter->fetchCandles($this->symbol, $this->timeframe, $this->limit);

            // Disconnect
            $adapter->disconnect();

            // Dispatch DataReceived event for each candle
            foreach ($candles as $candle) {
                event(new DataReceived(
                    $this->connection,
                    $this->symbol,
                    $this->timeframe,
                    $candle
                ));
            }

            // Update connection status
            $this->connection->update([
                'last_used_at' => now(),
                'status' => 'active',
                'last_error' => null,
            ]);

            Log::info('Market data fetched successfully', [
                'connection_id' => $this->connection->id,
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'candles_count' => count($candles),
            ]);
        } catch (\Exception $e) {
            // Update connection with error
            $this->connection->update([
                'status' => 'error',
                'last_error' => $e->getMessage(),
            ]);

            Log::error('Failed to fetch market data', [
                'connection_id' => $this->connection->id,
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FetchMarketDataJob failed permanently', [
            'connection_id' => $this->connection->id,
            'symbol' => $this->symbol,
            'timeframe' => $this->timeframe,
            'error' => $exception->getMessage(),
        ]);
    }
}

