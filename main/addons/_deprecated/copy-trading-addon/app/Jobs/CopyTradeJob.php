<?php

namespace Addons\CopyTrading\App\Jobs;

use Addons\CopyTrading\App\Services\TradeCopyService;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CopyTradeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $positionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $positionId)
    {
        $this->positionId = $positionId;
    }

    /**
     * Execute the job.
     */
    public function handle(TradeCopyService $tradeCopyService): void
    {
        try {
            $position = ExecutionPosition::find($this->positionId);
            
            if (!$position) {
                Log::warning("CopyTradeJob: Position not found", [
                    'position_id' => $this->positionId,
                ]);
                return;
            }

            // Only copy if position is still open
            if (!$position->isOpen()) {
                return;
            }

            $tradeCopyService->copyPosition($position);
        } catch (\Exception $e) {
            Log::error("CopyTradeJob error", [
                'position_id' => $this->positionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

