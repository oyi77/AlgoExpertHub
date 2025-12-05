<?php

namespace Addons\TradingManagement\Modules\MarketData\Listeners;

use Addons\TradingManagement\Shared\Events\DataReceived;
use Addons\TradingManagement\Shared\Events\DataStored;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Data Received Listener
 * 
 * Listens to DataReceived event from Data Provider module
 * Stores market data and dispatches DataStored event
 */
class DataReceivedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $timeout = 60;

    protected MarketDataService $marketDataService;

    /**
     * Create the event listener.
     */
    public function __construct(MarketDataService $marketDataService)
    {
        $this->marketDataService = $marketDataService;
    }

    /**
     * Handle the event.
     */
    public function handle(DataReceived $event): void
    {
        try {
            // Store market data
            $marketData = $this->marketDataService->store(
                $event->symbol,
                $event->timeframe,
                $event->candle['open'],
                $event->candle['high'],
                $event->candle['low'],
                $event->candle['close'],
                $event->candle['volume'],
                $event->candle['timestamp']
            );

            // Dispatch DataStored event
            event(new DataStored(
                $event->connection,
                $event->symbol,
                $event->timeframe,
                $marketData
            ));

            Log::debug('Market data stored', [
                'connection_id' => $event->connection->id,
                'symbol' => $event->symbol,
                'timeframe' => $event->timeframe,
                'timestamp' => $event->candle['timestamp'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store market data', [
                'connection_id' => $event->connection->id,
                'symbol' => $event->symbol,
                'timeframe' => $event->timeframe,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(DataReceived $event, \Throwable $exception): void
    {
        Log::error('DataReceivedListener failed permanently', [
            'connection_id' => $event->connection->id,
            'symbol' => $event->symbol,
            'timeframe' => $event->timeframe,
            'error' => $exception->getMessage(),
        ]);
    }
}

