<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingExecutionEngine\App\Services\ConnectionService;
use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

/**
 * Service for executing trades with preset configurations
 */
class PresetExecutionService
{
    protected PresetResolverService $presetResolver;
    protected PresetApplicatorService $presetApplicator;
    protected ?ConnectionService $connectionService;

    public function __construct(
        PresetResolverService $presetResolver,
        PresetApplicatorService $presetApplicator
    ) {
        $this->presetResolver = $presetResolver;
        $this->presetApplicator = $presetApplicator;
        
        // Lazy load ConnectionService if available
        if (class_exists(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)) {
            $this->connectionService = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class);
        } else {
            $this->connectionService = null;
        }
    }

    /**
     * Get preset configuration for execution context
     */
    public function getPresetConfig(ExecutionConnection $connection, ?Signal $signal = null): ?PresetConfigurationDTO
    {
        $user = $connection->user;
        
        $preset = $this->presetResolver->resolveForSignal($connection, $user, $signal);
        
        if (!$preset) {
            return null;
        }

        $connectionSettings = $connection->settings ?? [];
        return $this->presetApplicator->applyAsDTO($preset, $connectionSettings);
    }

    /**
     * Check if trading is allowed based on schedule
     */
    public function checkTradingSchedule(PresetConfigurationDTO $config): array
    {
        if (!$config->only_trade_in_session) {
            return ['allowed' => true, 'reason' => null];
        }

        // Get current time in preset timezone
        $timezone = $this->getTimezone($config->trading_timezone);
        $now = now()->setTimezone($timezone);
        $currentTime = $now->format('H:i');
        $currentDay = $now->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.

        // Check trading days (bitmask)
        $dayMask = 1 << ($currentDay === 0 ? 6 : $currentDay - 1); // Convert to bitmask (1=Mon, 2=Tue, etc.)
        if (!($config->trading_days_mask & $dayMask)) {
            return [
                'allowed' => false,
                'reason' => 'Trading not allowed on this day',
            ];
        }

        // Check trading hours
        if ($config->trading_hours_start && $config->trading_hours_end) {
            $start = $config->trading_hours_start;
            $end = $config->trading_hours_end;

            // Handle overnight sessions (e.g., 22:00 - 06:00)
            if ($end < $start) {
                // Overnight session
                if ($currentTime >= $start || $currentTime <= $end) {
                    return ['allowed' => true, 'reason' => null];
                }
            } else {
                // Normal session
                if ($currentTime >= $start && $currentTime <= $end) {
                    return ['allowed' => true, 'reason' => null];
                }
            }

            return [
                'allowed' => false,
                'reason' => "Trading hours: {$start} - {$end}",
            ];
        }

        // Check session profile
        if ($config->session_profile !== 'CUSTOM') {
            return $this->checkSessionProfile($config->session_profile, $now);
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Check weekly target
     */
    public function checkWeeklyTarget(ExecutionConnection $connection, PresetConfigurationDTO $config): array
    {
        if (!$config->weekly_target_enabled || !$config->auto_stop_on_weekly_target) {
            return ['allowed' => true, 'reason' => null];
        }

        // Get weekly P/L for this connection
        $weeklyPnl = $this->getWeeklyPnl($connection, $config->weekly_reset_day);

        // Calculate target amount
        if (!$this->connectionService) {
            return ['allowed' => true, 'reason' => null]; // Can't check, allow
        }

        $adapter = $this->connectionService->getAdapter($connection);
        if (!$adapter) {
            return ['allowed' => true, 'reason' => null]; // Can't check, allow
        }

        $balance = $adapter->getBalance();
        $equity = $balance['balance'] ?? 0;
        $targetAmount = ($equity * $config->weekly_target_profit_pct) / 100;

        if ($weeklyPnl >= $targetAmount) {
            return [
                'allowed' => false,
                'reason' => "Weekly target reached: {$config->weekly_target_profit_pct}%",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Check max positions
     */
    public function checkMaxPositions(ExecutionConnection $connection, PresetConfigurationDTO $config, ?string $symbol = null): array
    {
        $openPositions = ExecutionPosition::open()
            ->where('connection_id', $connection->id)
            ->get();

        // Check total max positions
        if ($openPositions->count() >= $config->max_positions) {
            return [
                'allowed' => false,
                'reason' => "Maximum positions reached: {$config->max_positions}",
            ];
        }

        // Check max positions per symbol
        if ($symbol && $config->max_positions_per_symbol > 0) {
            $symbolPositions = $openPositions->where('symbol', $symbol)->count();
            if ($symbolPositions >= $config->max_positions_per_symbol) {
                return [
                    'allowed' => false,
                    'reason' => "Maximum positions per symbol reached: {$config->max_positions_per_symbol}",
                ];
            }
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Calculate position size using preset
     */
    public function calculatePositionSize(
        PresetConfigurationDTO $config,
        float $equity,
        float $entryPrice,
        ?float $slPrice = null
    ): float {
        return $this->presetApplicator->calculatePositionSize($config, $equity, $entryPrice, $slPrice);
    }

    /**
     * Calculate SL price using preset
     */
    public function calculateSlPrice(
        PresetConfigurationDTO $config,
        float $entryPrice,
        string $direction,
        ?float $structureSlPrice = null
    ): ?float {
        return $this->presetApplicator->calculateSlPrice($config, $entryPrice, $direction, $structureSlPrice);
    }

    /**
     * Calculate TP prices using preset
     */
    public function calculateTpPrices(
        PresetConfigurationDTO $config,
        float $entryPrice,
        float $slPrice,
        string $direction
    ): array {
        return $this->presetApplicator->calculateTpPrices($config, $entryPrice, $slPrice, $direction);
    }

    /**
     * Get timezone
     */
    protected function getTimezone(string $timezone): string
    {
        if ($timezone === 'SERVER') {
            return config('app.timezone', 'UTC');
        }

        if ($timezone === 'UTC') {
            return 'UTC';
        }

        return $timezone;
    }

    /**
     * Check session profile
     */
    protected function checkSessionProfile(string $profile, $now): array
    {
        $hour = (int) $now->format('H');
        $minute = (int) $now->format('i');
        $time = $hour * 60 + $minute; // Minutes since midnight

        switch ($profile) {
            case 'ASIA':
                // Asia session: 00:00 - 09:00 UTC
                $asiaStart = 0;
                $asiaEnd = 9 * 60;
                if ($time >= $asiaStart && $time <= $asiaEnd) {
                    return ['allowed' => true, 'reason' => null];
                }
                return ['allowed' => false, 'reason' => 'Asia session: 00:00 - 09:00 UTC'];

            case 'LONDON':
                // London session: 08:00 - 17:00 UTC
                $londonStart = 8 * 60;
                $londonEnd = 17 * 60;
                if ($time >= $londonStart && $time <= $londonEnd) {
                    return ['allowed' => true, 'reason' => null];
                }
                return ['allowed' => false, 'reason' => 'London session: 08:00 - 17:00 UTC'];

            case 'NY':
                // NY session: 13:00 - 22:00 UTC
                $nyStart = 13 * 60;
                $nyEnd = 22 * 60;
                if ($time >= $nyStart && $time <= $nyEnd) {
                    return ['allowed' => true, 'reason' => null];
                }
                return ['allowed' => false, 'reason' => 'NY session: 13:00 - 22:00 UTC'];

            default:
                return ['allowed' => true, 'reason' => null];
        }
    }

    /**
     * Get weekly P/L for connection
     */
    protected function getWeeklyPnl(ExecutionConnection $connection, ?int $resetDay): float
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
}

