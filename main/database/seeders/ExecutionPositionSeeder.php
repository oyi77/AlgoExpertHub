<?php

namespace Database\Seeders;

use App\Models\Signal;
use Illuminate\Database\Seeder;

class ExecutionPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo execution positions (open and closed) for trading operations dashboard
     */
    public function run()
    {
        // Check which model class exists
        $modelClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class)) {
            $modelClass = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class;
        } elseif (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class)) {
            $modelClass = \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class;
        }

        if (!$modelClass) {
            $this->command->warn('ExecutionPosition model not found. Skipping seeder.');
            return;
        }

        // Get execution connections
        $connectionClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
            $connectionClass = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class;
        } elseif (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            $connectionClass = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class;
        }

        if (!$connectionClass) {
            $this->command->warn('ExecutionConnection model not found. Skipping seeder.');
            return;
        }

        $connections = $connectionClass::where('is_active', 1)->get();
        if ($connections->isEmpty()) {
            $this->command->warn('No active execution connections found. Skipping position seeding.');
            return;
        }

        // Get execution logs
        $logClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class)) {
            $logClass = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class;
        } elseif (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
            $logClass = \Addons\TradingExecutionEngine\App\Models\ExecutionLog::class;
        }

        $executionLogs = $logClass ? $logClass::where('status', 'SUCCESS')->orWhere('status', 'executed')->get() : collect();
        
        // Get signals
        $signals = Signal::where('is_published', 1)->get();
        $symbols = ['BTC/USDT', 'ETH/USDT', 'EUR/USD', 'GBP/USD', 'XAU/USD', 'BNB/USDT'];
        $directions = ['buy', 'sell'];
        $closedReasons = ['tp', 'sl', 'manual'];

        $positions = [];

        // Create 12 open positions
        for ($i = 0; $i < 12; $i++) {
            $connection = $connections->random();
            $signal = $signals->isNotEmpty() ? $signals->random() : null;
            $executionLog = $executionLogs->isNotEmpty() ? $executionLogs->random() : null;
            $symbol = $symbols[array_rand($symbols)];
            $direction = $directions[array_rand($directions)];

            // Generate realistic prices
            $basePrice = match(true) {
                str_contains($symbol, 'BTC') => rand(30000, 60000),
                str_contains($symbol, 'ETH') => rand(2000, 4000),
                str_contains($symbol, 'XAU') => rand(1800, 2200),
                default => rand(100, 200) / 100, // Forex pairs
            };

            $entryPrice = $basePrice;
            $quantity = rand(1, 100) / 10; // 0.1 to 10.0
            $slPrice = $direction === 'buy' 
                ? $entryPrice * (1 - rand(10, 50) / 1000) // 1-5% below
                : $entryPrice * (1 + rand(10, 50) / 1000); // 1-5% above
            $tpPrice = $direction === 'buy'
                ? $entryPrice * (1 + rand(20, 100) / 1000) // 2-10% above
                : $entryPrice * (1 - rand(20, 100) / 1000); // 2-10% below

            // Current price (for open positions, it's between entry and TP/SL)
            $currentPrice = $direction === 'buy'
                ? $entryPrice + ($tpPrice - $entryPrice) * rand(20, 80) / 100 // 20-80% towards TP
                : $entryPrice - ($entryPrice - $tpPrice) * rand(20, 80) / 100;

            // Calculate PnL
            $priceDiff = $direction === 'buy'
                ? $currentPrice - $entryPrice
                : $entryPrice - $currentPrice;
            $pnl = $priceDiff * $quantity;
            $pnlPercentage = ($priceDiff / $entryPrice) * 100;

            $createdAt = now()->subDays(rand(0, 7))->subHours(rand(0, 23));

            $positionData = [
                'signal_id' => $signal?->id,
                'execution_connection_id' => $connection->id,
                'execution_log_id' => $executionLog?->id,
                'order_id' => 'ORD' . strtoupper(uniqid()),
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
                'entry_price' => $entryPrice,
                'current_price' => $currentPrice,
                'sl_price' => $slPrice,
                'tp_price' => $tpPrice,
                'status' => 'open',
                'pnl' => $pnl,
                'pnl_percentage' => $pnlPercentage,
                'last_price_update_at' => now()->subMinutes(rand(1, 60)),
                'created_at' => $createdAt,
                'updated_at' => now(),
            ];

            // Handle different model field names
            if ($modelClass === \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class) {
                $positionData['connection_id'] = $positionData['execution_connection_id'];
                unset($positionData['execution_connection_id']);
            }

            $position = $modelClass::create($positionData);
            $positions[] = $position;
        }

        // Create 25 closed positions
        for ($i = 0; $i < 25; $i++) {
            $connection = $connections->random();
            $signal = $signals->isNotEmpty() ? $signals->random() : null;
            $executionLog = $executionLogs->isNotEmpty() ? $executionLogs->random() : null;
            $symbol = $symbols[array_rand($symbols)];
            $direction = $directions[array_rand($directions)];
            $closedReason = $closedReasons[array_rand($closedReasons)];

            // Generate realistic prices
            $basePrice = match(true) {
                str_contains($symbol, 'BTC') => rand(30000, 60000),
                str_contains($symbol, 'ETH') => rand(2000, 4000),
                str_contains($symbol, 'XAU') => rand(1800, 2200),
                default => rand(100, 200) / 100, // Forex pairs
            };

            $entryPrice = $basePrice;
            $quantity = rand(1, 100) / 10; // 0.1 to 10.0
            $slPrice = $direction === 'buy' 
                ? $entryPrice * (1 - rand(10, 50) / 1000) // 1-5% below
                : $entryPrice * (1 + rand(10, 50) / 1000); // 1-5% above
            $tpPrice = $direction === 'buy'
                ? $entryPrice * (1 + rand(20, 100) / 1000) // 2-10% above
                : $entryPrice * (1 - rand(20, 100) / 1000); // 2-10% below

            // Exit price based on closed reason
            $exitPrice = match($closedReason) {
                'tp' => $tpPrice, // Hit take profit
                'sl' => $slPrice, // Hit stop loss
                'manual' => $direction === 'buy'
                    ? $entryPrice + ($tpPrice - $entryPrice) * rand(30, 90) / 100 // Manual close between entry and TP
                    : $entryPrice - ($entryPrice - $tpPrice) * rand(30, 90) / 100,
            };

            // Calculate PnL
            $priceDiff = $direction === 'buy'
                ? $exitPrice - $entryPrice
                : $entryPrice - $exitPrice;
            $pnl = $priceDiff * $quantity;
            $pnlPercentage = ($priceDiff / $entryPrice) * 100;

            $createdAt = now()->subDays(rand(1, 30))->subHours(rand(0, 23));
            $closedAt = $createdAt->copy()->addHours(rand(1, 72)); // Closed 1-72 hours after opening

            $positionData = [
                'signal_id' => $signal?->id,
                'execution_connection_id' => $connection->id,
                'execution_log_id' => $executionLog?->id,
                'order_id' => 'ORD' . strtoupper(uniqid()),
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
                'entry_price' => $entryPrice,
                'current_price' => $exitPrice,
                'sl_price' => $slPrice,
                'tp_price' => $tpPrice,
                'status' => 'closed',
                'pnl' => $pnl,
                'pnl_percentage' => $pnlPercentage,
                'closed_at' => $closedAt,
                'closed_reason' => $closedReason,
                'last_price_update_at' => $closedAt,
                'created_at' => $createdAt,
                'updated_at' => $closedAt,
            ];

            // Handle different model field names
            if ($modelClass === \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class) {
                $positionData['connection_id'] = $positionData['execution_connection_id'];
                unset($positionData['execution_connection_id']);
            }

            $position = $modelClass::create($positionData);
            $positions[] = $position;
        }

        $this->command->info('Created ' . count($positions) . ' execution positions successfully!');
    }
}
