<?php

namespace App\Services\Trading;

use App\Models\User;
use App\Models\TradingConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RiskManagementService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('trading.risk_management', []);
    }

    /**
     * Check if circuit breaker should halt trading
     */
    public function shouldHaltTrading(User $user): bool
    {
        $dailyLoss = $this->getDailyLoss($user);
        $maxDailyLoss = $this->getMaxDailyLoss($user);

        if ($dailyLoss >= $maxDailyLoss) {
            $this->triggerCircuitBreaker($user, 'max_daily_loss', $dailyLoss);
            return true;
        }

        $consecutiveLosses = $this->getConsecutiveLosses($user);
        $maxConsecutiveLosses = $this->config['max_consecutive_losses'] ?? 5;

        if ($consecutiveLosses >= $maxConsecutiveLosses) {
            $this->triggerCircuitBreaker($user, 'consecutive_losses', $consecutiveLosses);
            return true;
        }

        $drawdown = $this->getCurrentDrawdown($user);
        $maxDrawdown = $this->config['max_drawdown_percent'] ?? 20;

        if ($drawdown >= $maxDrawdown) {
            $this->triggerCircuitBreaker($user, 'max_drawdown', $drawdown);
            return true;
        }

        return false;
    }

    /**
     * Calculate position size based on risk parameters
     */
    public function calculatePositionSize(User $user, array $signal): float
    {
        $accountBalance = $this->getAccountBalance($user);
        $riskPercent = $user->risk_profile['risk_percent'] ?? 1.0;
        $riskAmount = $accountBalance * ($riskPercent / 100);

        $stopLossDistance = abs($signal['entry_price'] - $signal['stop_loss']);
        $pipValue = $this->getPipValue($signal['symbol']);
        
        $positionSize = $riskAmount / ($stopLossDistance * $pipValue);

        // Apply position size limits
        $minSize = $this->config['min_position_size'] ?? 0.01;
        $maxSize = $this->config['max_position_size'] ?? 10.0;
        $maxAccountPercent = $this->config['max_position_account_percent'] ?? 10;
        $maxPositionValue = $accountBalance * ($maxAccountPercent / 100);
        $maxSizeByValue = $maxPositionValue / $signal['entry_price'];

        $positionSize = max($minSize, min($positionSize, $maxSize, $maxSizeByValue));

        return round($positionSize, 2);
    }

    /**
     * Adjust risk parameters dynamically based on market conditions
     */
    public function adjustRiskParameters(User $user, array $marketConditions): array
    {
        $volatility = $marketConditions['volatility'] ?? 'normal';
        $baseRisk = $user->risk_profile['risk_percent'] ?? 1.0;

        $adjustedRisk = $baseRisk;

        switch ($volatility) {
            case 'high':
                $adjustedRisk *= 0.5; // Reduce risk by 50% in high volatility
                break;
            case 'extreme':
                $adjustedRisk *= 0.25; // Reduce risk by 75% in extreme volatility
                break;
            case 'low':
                $adjustedRisk *= 1.2; // Increase risk by 20% in low volatility
                break;
        }

        // Check recent performance
        $recentWinRate = $this->getRecentWinRate($user, 20);
        if ($recentWinRate < 0.4) {
            $adjustedRisk *= 0.7; // Reduce risk if win rate is poor
        } elseif ($recentWinRate > 0.7) {
            $adjustedRisk *= 1.1; // Slightly increase if win rate is good
        }

        return [
            'original_risk' => $baseRisk,
            'adjusted_risk' => min($adjustedRisk, $baseRisk * 1.5), // Cap at 150% of base
            'volatility' => $volatility,
            'win_rate' => $recentWinRate
        ];
    }

    /**
     * Trigger circuit breaker
     */
    protected function triggerCircuitBreaker(User $user, string $reason, $value): void
    {
        $cacheKey = "circuit_breaker:user:{$user->id}";
        
        Cache::put($cacheKey, [
            'triggered_at' => now(),
            'reason' => $reason,
            'value' => $value,
            'reset_at' => now()->addHours($this->config['circuit_breaker_reset_hours'] ?? 24)
        ], now()->addHours(24));

        Log::warning("Circuit breaker triggered for user {$user->id}", [
            'reason' => $reason,
            'value' => $value
        ]);

        // Notify user and administrators
        $this->notifyCircuitBreakerTriggered($user, $reason, $value);
    }

    /**
     * Check if circuit breaker is active
     */
    public function isCircuitBreakerActive(User $user): bool
    {
        $cacheKey = "circuit_breaker:user:{$user->id}";
        $breaker = Cache::get($cacheKey);

        if (!$breaker) {
            return false;
        }

        if (Carbon::parse($breaker['reset_at'])->isPast()) {
            Cache::forget($cacheKey);
            return false;
        }

        return true;
    }

    /**
     * Reset circuit breaker manually
     */
    public function resetCircuitBreaker(User $user): void
    {
        $cacheKey = "circuit_breaker:user:{$user->id}";
        Cache::forget($cacheKey);
        
        Log::info("Circuit breaker reset for user {$user->id}");
    }

    /**
     * Get daily loss for user
     */
    protected function getDailyLoss(User $user): float
    {
        return abs(
            \DB::table('trades')
                ->where('user_id', $user->id)
                ->whereDate('closed_at', Carbon::today())
                ->where('profit_loss', '<', 0)
                ->sum('profit_loss')
        );
    }

    /**
     * Get max daily loss limit
     */
    protected function getMaxDailyLoss(User $user): float
    {
        $accountBalance = $this->getAccountBalance($user);
        $maxLossPercent = $user->risk_profile['max_daily_loss_percent'] ?? 5;
        
        return $accountBalance * ($maxLossPercent / 100);
    }

    /**
     * Get consecutive losses
     */
    protected function getConsecutiveLosses(User $user): int
    {
        $recentTrades = \DB::table('trades')
            ->where('user_id', $user->id)
            ->whereNotNull('closed_at')
            ->orderBy('closed_at', 'desc')
            ->limit(20)
            ->pluck('profit_loss');

        $consecutive = 0;
        foreach ($recentTrades as $pl) {
            if ($pl < 0) {
                $consecutive++;
            } else {
                break;
            }
        }

        return $consecutive;
    }

    /**
     * Get current drawdown percentage
     */
    protected function getCurrentDrawdown(User $user): float
    {
        $peakBalance = \DB::table('account_snapshots')
            ->where('user_id', $user->id)
            ->max('balance');

        if (!$peakBalance) {
            return 0;
        }

        $currentBalance = $this->getAccountBalance($user);
        $drawdown = (($peakBalance - $currentBalance) / $peakBalance) * 100;

        return max(0, $drawdown);
    }

    /**
     * Get account balance
     */
    protected function getAccountBalance(User $user): float
    {
        return $user->wallet_balance ?? 0;
    }

    /**
     * Get pip value for symbol
     */
    protected function getPipValue(string $symbol): float
    {
        // Simplified pip value calculation
        return 10.0; // $10 per pip for standard lot
    }

    /**
     * Get recent win rate
     */
    protected function getRecentWinRate(User $user, int $tradeCount = 20): float
    {
        $recentTrades = \DB::table('trades')
            ->where('user_id', $user->id)
            ->whereNotNull('closed_at')
            ->orderBy('closed_at', 'desc')
            ->limit($tradeCount)
            ->pluck('profit_loss');

        if ($recentTrades->isEmpty()) {
            return 0.5; // Default to 50% if no trades
        }

        $winningTrades = $recentTrades->filter(fn($pl) => $pl > 0)->count();
        
        return $winningTrades / $recentTrades->count();
    }

    /**
     * Notify circuit breaker triggered
     */
    protected function notifyCircuitBreakerTriggered(User $user, string $reason, $value): void
    {
        // Implementation would send notifications via email, SMS, etc.
        Log::info("Circuit breaker notification sent to user {$user->id}");
    }
}
