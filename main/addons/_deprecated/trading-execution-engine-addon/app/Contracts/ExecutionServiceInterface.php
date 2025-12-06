<?php

namespace Addons\TradingExecutionEngine\App\Contracts;

use App\Models\Signal;

interface ExecutionServiceInterface
{
    /**
     * Execute a signal on a connection.
     *
     * @param Signal $signal
     * @param int $connectionId
     * @param array $options Optional execution options
     * @return array ['success' => bool, 'execution_log_id' => int|null, 'position_id' => int|null, 'message' => string]
     */
    public function executeSignal(Signal $signal, int $connectionId, array $options = []): array;

    /**
     * Check if a signal can be executed on a connection.
     *
     * @param Signal $signal
     * @param int $connectionId
     * @return array ['can_execute' => bool, 'reason' => string|null]
     */
    public function canExecute(Signal $signal, int $connectionId): array;

    /**
     * Get available connections for a signal.
     *
     * @param Signal $signal
     * @param int|null $userId
     * @param int|null $adminId
     * @return array Array of connection IDs
     */
    public function getAvailableConnections(Signal $signal, ?int $userId = null, ?int $adminId = null): array;
}

