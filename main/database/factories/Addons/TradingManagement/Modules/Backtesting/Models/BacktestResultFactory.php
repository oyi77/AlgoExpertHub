<?php

namespace Database\Factories\Addons\TradingManagement\Modules\Backtesting\Models;

use Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult;
use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Illuminate\Database\Eloquent\Factories\Factory;

class BacktestResultFactory extends Factory
{
    protected $model = BacktestResult::class;

    public function definition()
    {
        $totalTrades = $this->faker->numberBetween(50, 200);
        $winningTrades = $this->faker->numberBetween(25, $totalTrades);
        $losingTrades = $totalTrades - $winningTrades;
        $winRate = ($winningTrades / $totalTrades) * 100;
        
        $totalProfit = $this->faker->randomFloat(2, 5000, 50000);
        $totalLoss = $this->faker->randomFloat(2, 2000, 30000);
        $netProfit = $totalProfit - $totalLoss;
        $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : 0;
        
        $initialBalance = 10000;
        $finalBalance = $initialBalance + $netProfit;
        $returnPercent = ($netProfit / $initialBalance) * 100;
        
        $avgWin = $winningTrades > 0 ? $totalProfit / $winningTrades : 0;
        $avgLoss = $losingTrades > 0 ? $totalLoss / $losingTrades : 0;
        
        return [
            'backtest_id' => Backtest::factory(),
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTrades,
            'losing_trades' => $losingTrades,
            'win_rate' => round($winRate, 2),
            'total_profit' => $totalProfit,
            'total_loss' => $totalLoss,
            'net_profit' => $netProfit,
            'final_balance' => $finalBalance,
            'return_percent' => round($returnPercent, 2),
            'profit_factor' => round($profitFactor, 2),
            'sharpe_ratio' => $this->faker->randomFloat(2, 0.5, 3.0),
            'max_drawdown' => $this->faker->randomFloat(2, 500, 5000),
            'max_drawdown_percent' => $this->faker->randomFloat(2, 5, 30),
            'avg_win' => round($avgWin, 2),
            'avg_loss' => round($avgLoss, 2),
            'largest_win' => $this->faker->randomFloat(2, 500, 2000),
            'largest_loss' => $this->faker->randomFloat(2, 200, 1000),
            'consecutive_wins' => $this->faker->numberBetween(3, 15),
            'consecutive_losses' => $this->faker->numberBetween(2, 8),
            'equity_curve' => $this->generateEquityCurve($initialBalance, $finalBalance, $totalTrades),
            'trade_details' => $this->generateTradeDetails($totalTrades, $winningTrades),
        ];
    }

    protected function generateEquityCurve($initialBalance, $finalBalance, $totalTrades)
    {
        $curve = [$initialBalance];
        $step = ($finalBalance - $initialBalance) / $totalTrades;
        
        for ($i = 1; $i <= $totalTrades; $i++) {
            $curve[] = $initialBalance + ($step * $i) + $this->faker->randomFloat(2, -500, 500);
        }
        
        return $curve;
    }

    protected function generateTradeDetails($totalTrades, $winningTrades)
    {
        $trades = [];
        $entryTime = now()->subMonths(3);
        
        for ($i = 0; $i < min($totalTrades, 50); $i++) {
            $isWin = $i < $winningTrades;
            $entryPrice = $this->faker->randomFloat(2, 20000, 50000);
            $exitPrice = $isWin 
                ? $entryPrice * $this->faker->randomFloat(4, 1.01, 1.05)
                : $entryPrice * $this->faker->randomFloat(4, 0.95, 0.99);
            
            $pnl = $isWin 
                ? $this->faker->randomFloat(2, 50, 500)
                : -$this->faker->randomFloat(2, 20, 200);
            
            $trades[] = [
                'entry_time' => $entryTime->toDateTimeString(),
                'exit_time' => $entryTime->addHours($this->faker->numberBetween(1, 24))->toDateTimeString(),
                'direction' => $this->faker->randomElement(['BUY', 'SELL']),
                'entry_price' => $entryPrice,
                'exit_price' => $exitPrice,
                'pnl' => $pnl,
                'duration' => $this->faker->numberBetween(1, 24) . 'h',
            ];
            
            $entryTime->addDays(1);
        }
        
        return $trades;
    }

    public function profitable()
    {
        return $this->state(function (array $attributes) {
            return [
                'net_profit' => $this->faker->randomFloat(2, 1000, 10000),
                'return_percent' => $this->faker->randomFloat(2, 10, 100),
                'win_rate' => $this->faker->randomFloat(2, 55, 75),
            ];
        });
    }

    public function unprofitable()
    {
        return $this->state(function (array $attributes) {
            return [
                'net_profit' => -$this->faker->randomFloat(2, 500, 5000),
                'return_percent' => -$this->faker->randomFloat(2, 5, 50),
                'win_rate' => $this->faker->randomFloat(2, 30, 45),
            ];
        });
    }
}
