<?php

namespace Addons\TradingManagement\Modules\CopyTrading\Jobs;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\CopyTrading\Services\TradeCopyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CopyTradeJob
 * 
 * Job to execute copied trades for all active subscribers
 */
class CopyTradeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ExecutionPosition $traderPosition;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(ExecutionPosition $traderPosition)
    {
        $this->traderPosition = $traderPosition;
    }

    public function handle(TradeCopyService $copyService): void
    {
        try {
            // Copy trade to all active subscribers
            $copyService->copyToSubscribers($this->traderPosition);
        } catch (\Exception $e) {
            Log::error('Copy trade job failed', [
                'trader_position_id' => $this->traderPosition->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
