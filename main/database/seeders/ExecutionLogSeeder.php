<?php

namespace Database\Seeders;

use App\Models\Signal;
use Illuminate\Database\Seeder;

class ExecutionLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo execution logs for trading operations dashboard
     */
    public function run()
    {
        // Check which model class exists
        $modelClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class)) {
            $modelClass = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class;
        } elseif (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
            $modelClass = \Addons\TradingExecutionEngine\App\Models\ExecutionLog::class;
        }

        if (!$modelClass) {
            $this->command->warn('ExecutionLog model not found. Skipping seeder.');
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
            $this->command->warn('No active execution connections found. Skipping execution log seeding.');
            return;
        }

        // Get signals
        $signals = Signal::where('is_published', 1)->get();
        $symbols = ['BTC/USDT', 'ETH/USDT', 'EUR/USD', 'GBP/USD', 'XAU/USD', 'BNB/USDT'];
        $sides = ['BUY', 'SELL'];
        $statuses = ['SUCCESS', 'SUCCESS', 'SUCCESS', 'FAILED', 'PENDING']; // 60% success, 20% failed, 20% pending

        $logs = [];
        $totalLogs = 30;

        for ($i = 0; $i < $totalLogs; $i++) {
            $connection = $connections->random();
            $signal = $signals->isNotEmpty() ? $signals->random() : null;
            $symbol = $symbols[array_rand($symbols)];
            $side = $sides[array_rand($sides)];
            $status = $statuses[array_rand($statuses)];

            // Generate realistic prices
            $basePrice = match(true) {
                str_contains($symbol, 'BTC') => rand(30000, 60000),
                str_contains($symbol, 'ETH') => rand(2000, 4000),
                str_contains($symbol, 'XAU') => rand(1800, 2200),
                default => rand(100, 200) / 100, // Forex pairs
            };

            $entryPrice = $basePrice;
            $lotSize = rand(1, 100) / 10; // 0.1 to 10.0
            $stopLoss = $side === 'BUY' 
                ? $entryPrice * (1 - rand(10, 50) / 1000) // 1-5% below
                : $entryPrice * (1 + rand(10, 50) / 1000); // 1-5% above
            $takeProfit = $side === 'BUY'
                ? $entryPrice * (1 + rand(20, 100) / 1000) // 2-10% above
                : $entryPrice * (1 - rand(20, 100) / 1000); // 2-10% below

            // Create date (mix of recent and older)
            $daysAgo = $i < 10 ? rand(0, 7) : rand(8, 30); // First 10 are recent
            $createdAt = now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            $logData = [
                'execution_connection_id' => $connection->id,
                'signal_id' => $signal?->id,
                'order_id' => 'ORD' . strtoupper(uniqid()),
                'symbol' => $symbol,
                'side' => $side,
                'lot_size' => $lotSize,
                'entry_price' => $entryPrice,
                'stop_loss' => $stopLoss,
                'take_profit' => $takeProfit,
                'status' => $status,
                'error_message' => $status === 'FAILED' ? 'Insufficient balance' : null,
                'order_data' => [
                    'exchange' => $connection->exchange_name,
                    'order_type' => 'market',
                    'filled' => $status === 'SUCCESS',
                ],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            // Handle different model field names
            if ($modelClass === \Addons\TradingExecutionEngine\App\Models\ExecutionLog::class) {
                // Old model uses connection_id, direction, quantity, sl_price, tp_price
                $logData['connection_id'] = $logData['execution_connection_id'];
                $logData['direction'] = $logData['side'];
                $logData['quantity'] = $logData['lot_size'];
                $logData['sl_price'] = $logData['stop_loss'];
                $logData['tp_price'] = $logData['take_profit'];
                $logData['execution_type'] = 'market';
                $logData['executed_at'] = $status === 'SUCCESS' ? $createdAt : null;
                $logData['response_data'] = $logData['order_data'];
                unset($logData['execution_connection_id'], $logData['side'], $logData['lot_size'], 
                      $logData['stop_loss'], $logData['take_profit'], $logData['order_data']);
            } else {
                // New model (TradingManagement) - database uses connection_id, not execution_connection_id
                // Convert execution_connection_id to connection_id for database
                $logData['connection_id'] = $logData['execution_connection_id'];
                // Also convert field names to match database schema
                $logData['direction'] = strtolower($logData['side']); // 'BUY' -> 'buy', 'SELL' -> 'sell'
                $logData['quantity'] = $logData['lot_size'];
                $logData['sl_price'] = $logData['stop_loss'];
                $logData['tp_price'] = $logData['take_profit'];
                $logData['execution_type'] = 'market';
                $logData['executed_at'] = $status === 'SUCCESS' ? $createdAt : null;
                $logData['response_data'] = $logData['order_data'];
                // Map status values
                $statusMap = [
                    'SUCCESS' => 'executed',
                    'FAILED' => 'failed',
                    'PENDING' => 'pending',
                ];
                $logData['status'] = $statusMap[$status] ?? 'pending';
                unset($logData['execution_connection_id'], $logData['side'], $logData['lot_size'], 
                      $logData['stop_loss'], $logData['take_profit'], $logData['order_data']);
            }

            $log = $modelClass::create($logData);
            $logs[] = $log;
        }

        $this->command->info('Created ' . count($logs) . ' execution logs successfully!');
    }
}
