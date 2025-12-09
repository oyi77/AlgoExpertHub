<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BacktestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo backtests for trading operations dashboard
     */
    public function run()
    {
        // Check if tables exist
        if (!Schema::hasTable('backtests')) {
            $this->command->warn('Backtests table not found. Skipping seeder.');
            return;
        }

        if (!Schema::hasTable('backtest_results')) {
            $this->command->warn('Backtest results table not found. Skipping seeder.');
            return;
        }

        // Check if models exist
        if (!class_exists(\Addons\TradingManagement\Modules\Backtesting\Models\Backtest::class)) {
            $this->command->warn('Backtest model not found. Skipping seeder.');
            return;
        }

        if (!class_exists(\Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult::class)) {
            $this->command->warn('BacktestResult model not found. Skipping seeder.');
            return;
        }

        $backtestClass = \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::class;
        $resultClass = \Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult::class;

        // Get required data
        $admin = Admin::first();
        $users = User::where('email', '!=', 'admin@admin.com')->take(5)->get();
        
        // Get filter strategies
        $filterStrategyClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)) {
            $filterStrategyClass = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class;
        } elseif (class_exists(\Addons\FilterStrategyAddon\App\Models\FilterStrategy::class)) {
            $filterStrategyClass = \Addons\FilterStrategyAddon\App\Models\FilterStrategy::class;
        }
        $filterStrategies = $filterStrategyClass ? $filterStrategyClass::limit(5)->get() : collect();

        // Get AI model profiles
        $aiModelProfileClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class)) {
            $aiModelProfileClass = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class;
        } elseif (class_exists(\Addons\AiTradingAddon\App\Models\AiModelProfile::class)) {
            $aiModelProfileClass = \Addons\AiTradingAddon\App\Models\AiModelProfile::class;
        }
        $aiModelProfiles = $aiModelProfileClass ? $aiModelProfileClass::limit(3)->get() : collect();

        // Get trading presets
        $presetClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
            $presetClass = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class;
        } elseif (class_exists(\Addons\TradingPresetAddon\App\Models\TradingPreset::class)) {
            $presetClass = \Addons\TradingPresetAddon\App\Models\TradingPreset::class;
        }
        $presets = $presetClass ? $presetClass::limit(5)->get() : collect();

        if (!$admin && $users->isEmpty()) {
            $this->command->warn('No admin or users found. Skipping backtest seeding.');
            return;
        }

        $symbols = ['BTC/USDT', 'ETH/USDT', 'EUR/USD', 'GBP/USD', 'XAU/USD'];
        $timeframes = ['1H', '4H', '1D', '1W'];
        $backtests = [];

        // Create 12 completed backtests
        for ($i = 0; $i < 12; $i++) {
            $isAdminOwned = rand(0, 1);
            $symbol = $symbols[array_rand($symbols)];
            $timeframe = $timeframes[array_rand($timeframes)];
            $filterStrategy = $filterStrategies->isNotEmpty() ? $filterStrategies->random() : null;
            $aiModelProfile = $aiModelProfiles->isNotEmpty() ? $aiModelProfiles->random() : null;
            $preset = $presets->isNotEmpty() ? $presets->random() : null;

            $startDate = now()->subMonths(rand(6, 12));
            $endDate = now()->subDays(rand(0, 30));
            $initialBalance = rand(5000, 50000);

            $createdAt = now()->subDays(rand(1, 30));
            $completedAt = $createdAt->copy()->addHours(rand(1, 6));

            $backtest = $backtestClass::create([
                'user_id' => $isAdminOwned ? null : ($users->isNotEmpty() ? $users->random()->id : null),
                'admin_id' => $isAdminOwned ? $admin->id : null,
                'name' => "Backtest: {$symbol} {$timeframe} - " . ($i + 1),
                'description' => "Automated backtest for {$symbol} on {$timeframe} timeframe",
                'filter_strategy_id' => $filterStrategy?->id,
                'ai_model_profile_id' => $aiModelProfile?->id,
                'preset_id' => $preset?->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'initial_balance' => $initialBalance,
                'status' => 'completed',
                'progress_percent' => 100,
                'started_at' => $createdAt,
                'completed_at' => $completedAt,
                'error_message' => null,
                'created_at' => $createdAt,
                'updated_at' => $completedAt,
            ]);

            // Create result
            $totalTrades = rand(50, 500);
            $winningTrades = (int)($totalTrades * rand(55, 80) / 100);
            $losingTrades = $totalTrades - $winningTrades;
            $winRate = ($winningTrades / $totalTrades) * 100;

            $avgWin = rand(100, 1000);
            $avgLoss = rand(50, 500);
            $totalProfit = $winningTrades * $avgWin;
            $totalLoss = $losingTrades * $avgLoss;
            $netProfit = $totalProfit - $totalLoss;
            $finalBalance = $initialBalance + $netProfit;
            $returnPercent = ($netProfit / $initialBalance) * 100;
            $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : 0;
            $maxDrawdown = rand(500, 5000);
            $maxDrawdownPercent = ($maxDrawdown / $initialBalance) * 100;
            $sharpeRatio = rand(100, 300) / 100; // 1.0 to 3.0

            $resultClass::create([
                'backtest_id' => $backtest->id,
                'total_trades' => $totalTrades,
                'winning_trades' => $winningTrades,
                'losing_trades' => $losingTrades,
                'win_rate' => $winRate,
                'total_profit' => $totalProfit,
                'total_loss' => $totalLoss,
                'net_profit' => $netProfit,
                'final_balance' => $finalBalance,
                'return_percent' => $returnPercent,
                'profit_factor' => $profitFactor,
                'sharpe_ratio' => $sharpeRatio,
                'max_drawdown' => $maxDrawdown,
                'max_drawdown_percent' => $maxDrawdownPercent,
                'avg_win' => $avgWin,
                'avg_loss' => $avgLoss,
                'largest_win' => rand($avgWin, $avgWin * 3),
                'largest_loss' => rand($avgLoss, $avgLoss * 2),
                'consecutive_wins' => rand(3, 15),
                'consecutive_losses' => rand(2, 8),
                'equity_curve' => $this->generateEquityCurve($initialBalance, $netProfit, $totalTrades),
                'trade_details' => $this->generateTradeDetails($totalTrades, $winningTrades),
            ]);

            $backtests[] = $backtest;
        }

        // Create 3 running backtests
        for ($i = 0; $i < 3; $i++) {
            $isAdminOwned = rand(0, 1);
            $symbol = $symbols[array_rand($symbols)];
            $timeframe = $timeframes[array_rand($timeframes)];
            $filterStrategy = $filterStrategies->isNotEmpty() ? $filterStrategies->random() : null;
            $aiModelProfile = $aiModelProfiles->isNotEmpty() ? $aiModelProfiles->random() : null;
            $preset = $presets->isNotEmpty() ? $presets->random() : null;

            $startDate = now()->subMonths(rand(3, 6));
            $endDate = now()->subDays(rand(0, 7));
            $initialBalance = rand(5000, 50000);

            $startedAt = now()->subHours(rand(1, 24));
            $progress = rand(20, 90);

            $backtest = $backtestClass::create([
                'user_id' => $isAdminOwned ? null : ($users->isNotEmpty() ? $users->random()->id : null),
                'admin_id' => $isAdminOwned ? $admin->id : null,
                'name' => "Running Backtest: {$symbol} {$timeframe} - " . ($i + 1),
                'description' => "Currently running backtest for {$symbol} on {$timeframe} timeframe",
                'filter_strategy_id' => $filterStrategy?->id,
                'ai_model_profile_id' => $aiModelProfile?->id,
                'preset_id' => $preset?->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'initial_balance' => $initialBalance,
                'status' => 'running',
                'progress_percent' => $progress,
                'started_at' => $startedAt,
                'completed_at' => null,
                'error_message' => null,
                'created_at' => $startedAt,
                'updated_at' => now(),
            ]);

            $backtests[] = $backtest;
        }

        // Create 2 failed backtests
        for ($i = 0; $i < 2; $i++) {
            $isAdminOwned = rand(0, 1);
            $symbol = $symbols[array_rand($symbols)];
            $timeframe = $timeframes[array_rand($timeframes)];
            // Preset is required, so use a random one or first available
            $preset = $presets->isNotEmpty() ? $presets->random() : ($presetClass ? $presetClass::first() : null);

            if (!$preset) {
                $this->command->warn('No trading presets found. Skipping failed backtest creation.');
                continue;
            }

            $startDate = now()->subMonths(rand(3, 6));
            $endDate = now()->subDays(rand(0, 7));
            $initialBalance = rand(5000, 50000);

            $startedAt = now()->subDays(rand(1, 7));
            $failedAt = $startedAt->copy()->addHours(rand(1, 3));
            $errorMessages = [
                'Insufficient historical data',
                'Invalid strategy configuration',
                'Market data fetch timeout',
                'Memory limit exceeded',
            ];

            $backtest = $backtestClass::create([
                'user_id' => $isAdminOwned ? null : ($users->isNotEmpty() ? $users->random()->id : null),
                'admin_id' => $isAdminOwned ? $admin->id : null,
                'name' => "Failed Backtest: {$symbol} {$timeframe} - " . ($i + 1),
                'description' => "Failed backtest for {$symbol} on {$timeframe} timeframe",
                'filter_strategy_id' => null,
                'ai_model_profile_id' => null,
                'preset_id' => $preset->id, // Required field, cannot be null
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'initial_balance' => $initialBalance,
                'status' => 'failed',
                'progress_percent' => rand(10, 50),
                'started_at' => $startedAt,
                'completed_at' => $failedAt,
                'error_message' => $errorMessages[array_rand($errorMessages)],
                'created_at' => $startedAt,
                'updated_at' => $failedAt,
            ]);

            $backtests[] = $backtest;
        }

        $this->command->info('Created ' . count($backtests) . ' backtests successfully!');
    }

    /**
     * Generate equity curve data
     */
    private function generateEquityCurve(float $initialBalance, float $netProfit, int $totalTrades): array
    {
        $curve = [];
        $currentBalance = $initialBalance;
        $tradesPerPoint = max(1, (int)($totalTrades / 20)); // 20 data points

        for ($i = 0; $i < 20; $i++) {
            $tradesInPeriod = min($tradesPerPoint, $totalTrades - ($i * $tradesPerPoint));
            if ($tradesInPeriod <= 0) break;

            $profitInPeriod = ($netProfit / $totalTrades) * $tradesInPeriod;
            $currentBalance += $profitInPeriod;

            $curve[] = [
                'date' => now()->subDays(20 - $i)->format('Y-m-d'),
                'balance' => round($currentBalance, 2),
            ];
        }

        return $curve;
    }

    /**
     * Generate trade details
     */
    private function generateTradeDetails(int $totalTrades, int $winningTrades): array
    {
        $details = [];
        $losingTrades = $totalTrades - $winningTrades;

        // Sample winning trades
        for ($i = 0; $i < min(10, $winningTrades); $i++) {
            $details[] = [
                'date' => now()->subDays(rand(1, 30))->format('Y-m-d H:i'),
                'symbol' => ['BTC/USDT', 'ETH/USDT', 'EUR/USD'][array_rand(['BTC/USDT', 'ETH/USDT', 'EUR/USD'])],
                'direction' => ['buy', 'sell'][array_rand(['buy', 'sell'])],
                'entry' => rand(100, 50000) / 100,
                'exit' => rand(100, 50000) / 100,
                'pnl' => rand(50, 500),
                'result' => 'win',
            ];
        }

        // Sample losing trades
        for ($i = 0; $i < min(5, $losingTrades); $i++) {
            $details[] = [
                'date' => now()->subDays(rand(1, 30))->format('Y-m-d H:i'),
                'symbol' => ['BTC/USDT', 'ETH/USDT', 'EUR/USD'][array_rand(['BTC/USDT', 'ETH/USDT', 'EUR/USD'])],
                'direction' => ['buy', 'sell'][array_rand(['buy', 'sell'])],
                'entry' => rand(100, 50000) / 100,
                'exit' => rand(100, 50000) / 100,
                'pnl' => -rand(20, 200),
                'result' => 'loss',
            ];
        }

        return $details;
    }
}
