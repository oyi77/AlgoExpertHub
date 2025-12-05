<?php

namespace Addons\TradingManagement\Modules\Marketplace\Jobs;

use Addons\TradingManagement\Modules\Marketplace\Models\TraderProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{DB, Log};

class UpdateTraderStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;

    public function handle()
    {
        try {
            Log::info('UpdateTraderStatsJob: Starting');

            $traders = TraderProfile::all();
            $updated = 0;

            foreach ($traders as $trader) {
                $this->updateTraderStats($trader);
                $updated++;
            }

            Log::info('UpdateTraderStatsJob: Completed', ['updated' => $updated]);

        } catch (\Exception $e) {
            Log::error('UpdateTraderStatsJob: Failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function updateTraderStats(TraderProfile $trader)
    {
        $executions = DB::table('copy_trading_executions')
            ->where('trader_id', $trader->user_id)
            ->get();

        $totalTrades = $executions->count();
        $winningTrades = $executions->where('status', 'success')->count();
        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

        $totalFollowers = DB::table('copy_trading_subscriptions')
            ->where('trader_id', $trader->user_id)
            ->where('is_active', true)
            ->count();

        $trader->update([
            'trades_count' => $totalTrades,
            'win_rate' => round($winRate, 2),
            'total_followers' => $totalFollowers,
        ]);
    }
}


