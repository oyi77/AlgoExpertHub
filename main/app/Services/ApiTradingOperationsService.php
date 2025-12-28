<?php

declare(strict_types=1);

namespace App\Services;

use Addons\TradingManagement\Modules\Execution\Services\ExecutionOperationsService as AddonExecutionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiTradingOperationsService
{
    protected ?AddonExecutionService $addonService;

    public function __construct()
    {
        // Only inject if addon is active
        if (\App\Support\AddonRegistry::active('trading-management-addon')) {
            $this->addonService = app(AddonExecutionService::class);
        } else {
            $this->addonService = null;
        }
    }

    /**
     * Get execution logs for a user
     */
    public function getExecutionLogs(int $userId, array $filters = [], int $perPage = 20)
    {
        $query = DB::table('execution_logs')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['connection_id']) && $filters['connection_id']) {
            $query->where('connection_id', $filters['connection_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create execution log for manual trade
     */
    public function createExecutionLog(int $userId, array $data): int
    {
        return (int) DB::table('execution_logs')->insertGetId([
            'user_id' => $userId,
            'connection_id' => $data['connection_id'],
            'signal_id' => $data['signal_id'] ?? null,
            'symbol' => $data['symbol'],
            'direction' => $data['direction'],
            'quantity' => $data['amount'],
            'entry_price' => $data['price'],
            'sl_price' => $data['stop_loss'],
            'tp_price' => $data['take_profit'],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get trading statistics for a user
     */
    public function getStatistics(int $userId): array
    {
        $stats = [
            'total_trades' => DB::table('execution_logs')->where('user_id', $userId)->count(),
            'open_trades' => DB::table('execution_logs')->where('user_id', $userId)->where('status', 'open')->count(),
            'closed_trades' => DB::table('execution_logs')->where('user_id', $userId)->where('status', 'closed')->count(),
            'win_rate' => 0,
            'total_profit' => 0,
        ];

        // Calculate win rate and profit if execution_positions table exists
        if (Schema::hasTable('execution_positions')) {
            $positions = DB::table('execution_positions')
                ->where('user_id', $userId)
                ->where('status', 'closed')
                ->get();

            $winCount = $positions->where('pnl', '>', 0)->count();
            $stats['win_rate'] = $positions->count() > 0
                ? round(($winCount / $positions->count()) * 100, 2)
                : 0;
            $stats['total_profit'] = $positions->sum('pnl');
        }

        return $stats;
    }

    /**
     * Verify connection belongs to user
     */
    public function verifyConnection(int $connectionId, int $userId): bool
    {
        return DB::table('execution_connections')
            ->where('id', $connectionId)
            ->where('user_id', $userId)
            ->exists();
    }
}

