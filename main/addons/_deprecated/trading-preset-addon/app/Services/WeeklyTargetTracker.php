<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use Addons\TradingExecutionEngine\App\Services\ConnectionService;
use Illuminate\Support\Facades\Log;

/**
 * Service for tracking weekly targets per connection/subscription
 */
class WeeklyTargetTracker
{
    protected ?ConnectionService $connectionService;

    public function __construct()
    {
        if (class_exists(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)) {
            $this->connectionService = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class);
        } else {
            $this->connectionService = null;
        }
    }

    /**
     * Get weekly P/L for connection
     */
    public function getWeeklyPnl(ExecutionConnection $connection, ?int $resetDay): float
    {
        if (!$resetDay) {
            return 0;
        }

        // Calculate start of week based on reset day
        $now = now();
        $currentDay = $now->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
        
        // Convert reset day (1=Monday, 7=Sunday) to dayOfWeek format
        $resetDayOfWeek = $resetDay === 7 ? 0 : $resetDay;
        
        // Calculate days since reset day
        $daysSinceReset = ($currentDay - $resetDayOfWeek + 7) % 7;
        
        $weekStart = $now->copy()->subDays($daysSinceReset)->startOfDay();

        // Get closed positions P/L for this week
        $weeklyPnl = ExecutionPosition::closed()
            ->where('connection_id', $connection->id)
            ->where('closed_at', '>=', $weekStart)
            ->sum('pnl');

        return (float) $weeklyPnl;
    }

    /**
     * Check if weekly target is reached
     */
    public function isTargetReached(ExecutionConnection $connection, PresetConfigurationDTO $config): bool
    {
        if (!$config->weekly_target_enabled || !$config->auto_stop_on_weekly_target) {
            return false;
        }

        if (!$this->connectionService) {
            return false;
        }

        $adapter = $this->connectionService->getAdapter($connection);
        if (!$adapter) {
            return false;
        }

        $balance = $adapter->getBalance();
        $equity = $balance['balance'] ?? 0;
        
        if ($equity <= 0) {
            return false;
        }

        $weeklyPnl = $this->getWeeklyPnl($connection, $config->weekly_reset_day);
        $targetAmount = ($equity * $config->weekly_target_profit_pct) / 100;

        return $weeklyPnl >= $targetAmount;
    }

    /**
     * Get weekly target progress
     */
    public function getProgress(ExecutionConnection $connection, PresetConfigurationDTO $config): array
    {
        if (!$config->weekly_target_enabled) {
            return [
                'enabled' => false,
                'current_pnl' => 0,
                'target_amount' => 0,
                'progress_pct' => 0,
                'reached' => false,
            ];
        }

        if (!$this->connectionService) {
            return [
                'enabled' => true,
                'current_pnl' => 0,
                'target_amount' => 0,
                'progress_pct' => 0,
                'reached' => false,
            ];
        }

        $adapter = $this->connectionService->getAdapter($connection);
        if (!$adapter) {
            return [
                'enabled' => true,
                'current_pnl' => 0,
                'target_amount' => 0,
                'progress_pct' => 0,
                'reached' => false,
            ];
        }

        $balance = $adapter->getBalance();
        $equity = $balance['balance'] ?? 0;
        
        $weeklyPnl = $this->getWeeklyPnl($connection, $config->weekly_reset_day);
        $targetAmount = ($equity * $config->weekly_target_profit_pct) / 100;
        $progressPct = $targetAmount > 0 ? ($weeklyPnl / $targetAmount) * 100 : 0;

        return [
            'enabled' => true,
            'current_pnl' => $weeklyPnl,
            'target_amount' => $targetAmount,
            'target_pct' => $config->weekly_target_profit_pct,
            'progress_pct' => min(100, $progressPct),
            'reached' => $weeklyPnl >= $targetAmount,
        ];
    }

    /**
     * Reset weekly tracking (called on reset day)
     */
    public function resetWeeklyTracking(ExecutionConnection $connection, int $resetDay): void
    {
        // Weekly tracking is calculated dynamically, no reset needed
        // But we can log the reset event
        Log::info("Weekly target reset", [
            'connection_id' => $connection->id,
            'reset_day' => $resetDay,
        ]);
    }
}

