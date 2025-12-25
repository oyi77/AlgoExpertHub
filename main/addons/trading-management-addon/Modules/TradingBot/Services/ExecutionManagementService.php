<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * ExecutionManagementService
 * 
 * Manage and monitor bot executions
 */
class ExecutionManagementService
{
    /**
     * Get execution history for a bot
     * 
     * @param TradingBot $bot
     * @param array $filters ['date_from' => string, 'date_to' => string, 'status' => string, 'symbol' => string, 'per_page' => int]
     * @return LengthAwarePaginator
     */
    public function getExecutionHistory(TradingBot $bot, array $filters = []): LengthAwarePaginator
    {
        $query = ExecutionLog::query()
            ->where('connection_id', $bot->exchange_connection_id)
            ->with(['signal', 'executionConnection']);

        // Apply date filters
        if (isset($filters['date_from'])) {
            $query->where('executed_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('executed_at', '<=', $filters['date_to']);
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply symbol filter
        if (isset($filters['symbol'])) {
            $query->where('symbol', $filters['symbol']);
        }

        // Order by executed_at descending
        $query->orderBy('executed_at', 'desc');

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Get execution details
     * 
     * @param int $executionId
     * @return array
     */
    public function getExecutionDetails(int $executionId): array
    {
        $execution = ExecutionLog::with(['signal', 'executionConnection', 'position'])
            ->findOrFail($executionId);

        return [
            'id' => $execution->id,
            'signal_id' => $execution->signal_id,
            'signal_title' => $execution->signal?->title,
            'connection_id' => $execution->connection_id,
            'connection_name' => $execution->executionConnection?->name,
            'order_id' => $execution->order_id,
            'symbol' => $execution->symbol,
            'direction' => $execution->direction,
            'quantity' => $execution->quantity,
            'entry_price' => $execution->entry_price,
            'sl_price' => $execution->sl_price,
            'tp_price' => $execution->tp_price,
            'status' => $execution->status,
            'executed_at' => $execution->executed_at?->toIso8601String(),
            'error_message' => $execution->error_message,
            'response_data' => $execution->response_data,
            'position' => $execution->position ? [
                'id' => $execution->position->id,
                'current_price' => $execution->position->current_price,
                'pnl' => $execution->position->pnl,
                'pnl_percentage' => $execution->position->pnl_percentage,
                'status' => $execution->position->status,
            ] : null,
        ];
    }

    /**
     * Cancel pending order
     * 
     * @param TradingBot $bot
     * @param string $orderId
     * @return bool
     */
    public function cancelPendingOrder(TradingBot $bot, string $orderId): bool
    {
        try {
            $execution = ExecutionLog::where('connection_id', $bot->exchange_connection_id)
                ->where('order_id', $orderId)
                ->where('status', 'pending')
                ->first();

            if (!$execution) {
                throw new \Exception('Pending order not found');
            }

            // Get adapter and cancel order
            $connectionService = app(\Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService::class);
            $adapter = $connectionService->getAdapter($bot->exchangeConnection);

            if (!$adapter || !method_exists($adapter, 'cancelOrder')) {
                throw new \Exception('Adapter does not support order cancellation');
            }

            $result = $adapter->cancelOrder($orderId, $execution->symbol);

            if ($result) {
                $execution->update([
                    'status' => 'cancelled',
                    'error_message' => 'Cancelled by user',
                ]);

                Log::info('Order cancelled', [
                    'bot_id' => $bot->id,
                    'order_id' => $orderId,
                    'execution_id' => $execution->id,
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to cancel order', [
                'bot_id' => $bot->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retry failed execution
     * 
     * @param int $executionId
     * @return bool
     */
    public function retryFailedExecution(int $executionId): bool
    {
        try {
            $execution = ExecutionLog::with(['signal', 'executionConnection'])->findOrFail($executionId);

            if ($execution->status !== 'failed') {
                throw new \Exception('Execution is not in failed status');
            }

            // Dispatch execution job again
            $signal = $execution->signal;
            if (!$signal) {
                throw new \Exception('Signal not found for execution');
            }

            $connection = $execution->executionConnection;
            if (!$connection) {
                throw new \Exception('Connection not found for execution');
            }

            // Dispatch ExecutionJob
            \Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob::dispatch($signal, $connection);

            Log::info('Failed execution retried', [
                'execution_id' => $executionId,
                'signal_id' => $signal->id,
                'connection_id' => $connection->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to retry execution', [
                'execution_id' => $executionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get execution statistics for a bot
     * 
     * @param TradingBot $bot
     * @return array
     */
    public function getExecutionStatistics(TradingBot $bot): array
    {
        try {
            $stats = ExecutionLog::where('connection_id', $bot->exchange_connection_id)
                ->selectRaw('
                    COUNT(*) as total_executions,
                    SUM(CASE WHEN status = "executed" THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                    AVG(CASE WHEN executed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, executed_at) ELSE NULL END) as avg_execution_time_seconds
                ')
                ->first();

            // Get executions by status
            $byStatus = ExecutionLog::where('connection_id', $bot->exchange_connection_id)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Get executions by symbol
            $bySymbol = ExecutionLog::where('connection_id', $bot->exchange_connection_id)
                ->selectRaw('symbol, COUNT(*) as count')
                ->groupBy('symbol')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'symbol')
                ->toArray();

            // Get recent execution trend (last 7 days)
            $trend = ExecutionLog::where('connection_id', $bot->exchange_connection_id)
                ->where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->pluck('count', 'date')
                ->toArray();

            return [
                'total_executions' => $stats->total_executions ?? 0,
                'successful' => $stats->successful ?? 0,
                'failed' => $stats->failed ?? 0,
                'pending' => $stats->pending ?? 0,
                'cancelled' => $stats->cancelled ?? 0,
                'success_rate' => ($stats->total_executions ?? 0) > 0 
                    ? round((($stats->successful ?? 0) / ($stats->total_executions ?? 1)) * 100, 2) 
                    : 0,
                'avg_execution_time_seconds' => round($stats->avg_execution_time_seconds ?? 0, 2),
                'by_status' => $byStatus,
                'by_symbol' => $bySymbol,
                'trend_7d' => $trend,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get execution statistics', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'total_executions' => 0,
                'successful' => 0,
                'failed' => 0,
                'pending' => 0,
                'cancelled' => 0,
                'success_rate' => 0,
                'avg_execution_time_seconds' => 0,
                'by_status' => [],
                'by_symbol' => [],
                'trend_7d' => [],
            ];
        }
    }
}

