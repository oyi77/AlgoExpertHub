<?php

namespace Addons\TradingManagement\Modules\Backtesting\Jobs;

use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Addons\TradingManagement\Modules\Backtesting\Services\BacktestEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunBacktestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $backtestId;

    public $tries = 1;
    public $timeout = 3600; // 1 hour for large backtests

    public function __construct(int $backtestId)
    {
        $this->backtestId = $backtestId;
    }

    public function handle(BacktestEngine $engine)
    {
        $backtest = Backtest::find($this->backtestId);

        if (!$backtest) {
            \Log::error('Backtest not found', ['id' => $this->backtestId]);
            return;
        }

        \Log::info('Starting backtest', [
            'backtest_id' => $backtest->id,
            'name' => $backtest->name,
            'symbol' => $backtest->symbol,
            'period' => $backtest->start_date->format('Y-m-d') . ' to ' . $backtest->end_date->format('Y-m-d'),
        ]);

        try {
            $result = $engine->run($backtest);

            \Log::info('Backtest completed', [
                'backtest_id' => $backtest->id,
                'total_trades' => $result->total_trades,
                'win_rate' => $result->win_rate,
                'net_profit' => $result->net_profit,
            ]);

        } catch (\Exception $e) {
            \Log::error('Backtest failed', [
                'backtest_id' => $backtest->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        \Log::error('RunBacktestJob failed', [
            'backtest_id' => $this->backtestId,
            'error' => $exception->getMessage(),
        ]);
    }
}

