<?php

namespace Addons\TradingManagement\Modules\Execution\Services;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use Addons\TradingManagement\Shared\Contracts\ExchangeAdapterInterface;
use App\Models\Signal;
use Exception;
use Illuminate\Support\Facades\Log;

class ExecutionService
{
    protected ExchangeConnectionService $connectionService;

    public function __construct(ExchangeConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Execute a trade based on signal and calculated risk
     * 
     * @param Signal $signal Signal to execute
     * @param ExchangeConnection $connection Connection to execute on
     * @param array $riskCalculation Result from RiskCalculator ['lot_size', 'sl', 'tp', ...]
     * @param array $options Additional options (comment, magic, etc.)
     * @return ExecutionLog
     */
    public function executeOrder(Signal $signal, ExchangeConnection $connection, array $riskCalculation, array $options = []): ExecutionLog
    {
        $log = ExecutionLog::create([
            'connection_id' => $connection->id,
            'signal_id' => $signal->id,
            'symbol' => $signal->pair->name ?? $signal->symbol,
            'direction' => $signal->direction,
            'quantity' => $riskCalculation['lot_size'],
            'sl_price' => $riskCalculation['sl'] ?? null,
            'tp_price' => $riskCalculation['tp'] ?? null,
            'execution_type' => 'MARKET',
            'status' => 'PENDING',
        ]);

        try {
            // 1. Get Adapter
            /** @var ExchangeAdapterInterface $adapter */
            $adapter = $this->connectionService->getAdapter($connection);

            if (!$adapter || !($adapter instanceof ExchangeAdapterInterface)) {
                throw new Exception("Connection does not support execution (Adapter invalid)");
            }

            // 2. Prepare params
            $side = strtolower($signal->direction);
            $amount = (float) $riskCalculation['lot_size'];
            $params = array_merge($options, [
                'stopLoss' => $riskCalculation['sl'] ?? null,
                'takeProfit' => $riskCalculation['tp'] ?? null,
            ]);

            // 3. Execute
            // Assuming MARKET order for now. Can be enhanced for LIMIT.
            $result = $adapter->createMarketOrder(
                $signal->pair->name ?? $signal->symbol,
                $side,
                $amount,
                $params
            );

            // 4. Update Log Success
            $log->update([
                'status' => 'FILLED', // or PARTIAL
                'order_id' => $result['id'] ?? null,
                'entry_price' => $result['price'] ?? $result['average'] ?? 0,
                'response_data' => $result,
                'executed_at' => now(),
            ]);

            return $log;

        } catch (\Exception $e) {
            // 5. Update Log Failure
            Log::error("ExecutionService: Order failed", ['error' => $e->getMessage(), 'log_id' => $log->id]);
            
            $log->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            // Rethrow or return failed log? Returning log allows caller to handle.
            return $log;
        }
    }
}
