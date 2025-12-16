<?php

namespace App\Jobs;

use App\Models\InternalTrade;
use App\Services\InternalBrokerService;
use App\Services\MarketDataService;
use App\Events\PositionUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorInternalPositions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        InternalBrokerService $brokerService,
        MarketDataService $marketDataService
    ): void {
        try {
            // Get all open positions
            $openPositions = InternalTrade::open()->get();

            if ($openPositions->isEmpty()) {
                return;
            }

            // Group positions by symbol to minimize API calls
            $positionsBySymbol = $openPositions->groupBy('symbol');

            foreach ($positionsBySymbol as $symbol => $positions) {
                try {
                    // Fetch current price for this symbol
                    $currentPrice = $marketDataService->getCurrentPrice($symbol);

                    if (!$currentPrice) {
                        Log::warning('Unable to fetch price for symbol', ['symbol' => $symbol]);
                        continue;
                    }

                    // Update each position
                    foreach ($positions as $position) {
                        try {
                            $brokerService->updatePosition($position, $currentPrice);

                            // Broadcast position update
                            broadcast(new PositionUpdated($position->user_id, [
                                'id' => $position->id,
                                'symbol' => $position->symbol,
                                'current_price' => $position->current_price,
                                'pnl' => $position->pnl,
                                'status' => $position->status,
                            ]));

                        } catch (\Exception $e) {
                            Log::error('Failed to update position', [
                                'position_id' => $position->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to process positions for symbol', [
                        'symbol' => $symbol,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Position monitoring job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
