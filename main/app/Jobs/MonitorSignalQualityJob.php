<?php

namespace App\Jobs;

use App\Models\Signal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitorSignalQualityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function handle(): void
    {
        Log::info('Monitoring signal quality...');

        $recentSignals = Signal::where('created_at', '>=', Carbon::now()->subDays(30))
            ->with('trades')
            ->get();

        foreach ($recentSignals as $signal) {
            $performance = $this->calculatePerformance($signal);

            // Flag underperforming signals
            if ($performance['win_rate'] < 0.4 || $performance['profit_factor'] < 1.0) {
                $this->flagSignal($signal, $performance);
            }

            // Update signal metrics
            $signal->update([
                'win_rate' => $performance['win_rate'],
                'profit_factor' => $performance['profit_factor'],
                'total_pips' => $performance['total_pips']
            ]);
        }

        Log::info("Monitored {$recentSignals->count()} signals");
    }

    protected function calculatePerformance(Signal $signal): array
    {
        $trades = $signal->trades()->whereNotNull('closed_at')->get();

        if ($trades->isEmpty()) {
            return [
                'win_rate' => 0,
                'profit_factor' => 0,
                'total_pips' => 0
            ];
        }

        $winningTrades = $trades->filter(fn($t) => $t->profit_loss > 0);
        $losingTrades = $trades->filter(fn($t) => $t->profit_loss < 0);

        $totalProfit = $winningTrades->sum('profit_loss');
        $totalLoss = abs($losingTrades->sum('profit_loss'));

        return [
            'win_rate' => $trades->count() > 0 ? $winningTrades->count() / $trades->count() : 0,
            'profit_factor' => $totalLoss > 0 ? $totalProfit / $totalLoss : 0,
            'total_pips' => $trades->sum('pips')
        ];
    }

    protected function flagSignal(Signal $signal, array $performance): void
    {
        Log::warning("Signal {$signal->id} flagged for poor performance", $performance);

        // Notify administrators
        $admins = \App\Models\Admin::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SignalQualityAlertNotification($signal, $performance));
        }
    }
}
