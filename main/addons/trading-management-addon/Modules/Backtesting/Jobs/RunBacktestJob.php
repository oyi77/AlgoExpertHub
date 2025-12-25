<?php

namespace Addons\TradingManagement\Modules\Backtesting\Jobs;

use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Addons\TradingManagement\Modules\Backtesting\Services\BacktestEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunBacktestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * The backtest instance.
     *
     * @var \Addons\TradingManagement\Modules\Backtesting\Models\Backtest
     */
    protected $backtest;

    /**
     * Create a new job instance.
     *
     * @param  \Addons\TradingManagement\Modules\Backtesting\Models\Backtest  $backtest
     * @return void
     */
    public function __construct(Backtest $backtest)
    {
        $this->backtest = $backtest;
    }

    /**
     * Execute the job.
     *
     * @param  \Addons\TradingManagement\Modules\Backtesting\Services\BacktestEngine  $engine
     * @return void
     */
    public function handle(BacktestEngine $engine)
    {
        try {
            Log::info('Starting backtest execution', [
                'backtest_id' => $this->backtest->id,
                'symbol' => $this->backtest->symbol,
                'timeframe' => $this->backtest->timeframe,
            ]);

            // Mark backtest as running
            $this->backtest->markAsRunning();

            // Run the backtest
            $result = $engine->run($this->backtest);

            // Mark as completed
            $this->backtest->markAsCompleted();

            Log::info('Backtest completed successfully', [
                'backtest_id' => $this->backtest->id,
                'total_trades' => $result->total_trades,
                'net_profit' => $result->net_profit,
                'win_rate' => $result->win_rate,
            ]);

            // TODO: Send notification to user
            // event(new BacktestCompleted($this->backtest));

        } catch (\Exception $e) {
            Log::error('Backtest execution failed', [
                'backtest_id' => $this->backtest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark as failed with error message
            $this->backtest->markAsFailed($e->getMessage());

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Backtest job failed permanently', [
            'backtest_id' => $this->backtest->id,
            'error' => $exception->getMessage(),
        ]);

        // Ensure backtest is marked as failed
        if (!$this->backtest->status === 'failed') {
            $this->backtest->markAsFailed($exception->getMessage());
        }
    }
}
