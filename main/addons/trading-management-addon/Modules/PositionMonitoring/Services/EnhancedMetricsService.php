<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Services;

use Illuminate\Support\Facades\Log;

/**
 * EnhancedMetricsService
 * 
 * Calculates advanced performance metrics for trading analysis
 */
class EnhancedMetricsService
{
    /**
     * Calculate expectancy (average expected value per trade)
     * 
     * @param array $trades Array of trades with 'profit_loss' field
     * @return float Expectancy value
     */
    public function calculateExpectancy(array $trades): float
    {
        if (empty($trades)) {
            return 0.0;
        }
        
        $totalProfit = 0;
        $totalLoss = 0;
        $winningTrades = 0;
        $losingTrades = 0;
        
        foreach ($trades as $trade) {
            $pnl = (float) ($trade['profit_loss'] ?? $trade['pnl'] ?? 0);
            
            if ($pnl > 0) {
                $totalProfit += $pnl;
                $winningTrades++;
            } elseif ($pnl < 0) {
                $totalLoss += abs($pnl);
                $losingTrades++;
            }
        }
        
        $totalTrades = count($trades);
        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) : 0;
        $avgWin = $winningTrades > 0 ? ($totalProfit / $winningTrades) : 0;
        $avgLoss = $losingTrades > 0 ? ($totalLoss / $losingTrades) : 0;
        
        // Expectancy = (Win Rate × Average Win) - (Loss Rate × Average Loss)
        $lossRate = 1 - $winRate;
        $expectancy = ($winRate * $avgWin) - ($lossRate * $avgLoss);
        
        return round($expectancy, 2);
    }

    /**
     * Calculate Sortino Ratio (risk-adjusted return using downside deviation)
     * 
     * @param array $returns Array of periodic returns (as percentages or decimals)
     * @param float $riskFreeRate Risk-free rate (annual, as percentage, default 0)
     * @return float Sortino ratio
     */
    public function calculateSortinoRatio(array $returns, float $riskFreeRate = 0.0): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        // Convert risk-free rate to periodic if needed (assuming returns are periodic)
        $periodicRiskFreeRate = $riskFreeRate / 100; // Convert percentage to decimal
        
        // Calculate average return
        $avgReturn = array_sum($returns) / count($returns);
        
        // Calculate downside deviation (only negative returns)
        $downsideReturns = [];
        foreach ($returns as $return) {
            if ($return < $periodicRiskFreeRate) {
                $downsideReturns[] = pow($return - $periodicRiskFreeRate, 2);
            }
        }
        
        if (empty($downsideReturns)) {
            // No downside returns, ratio is undefined or infinite
            return $avgReturn > $periodicRiskFreeRate ? 999.0 : 0.0;
        }
        
        $downsideDeviation = sqrt(array_sum($downsideReturns) / count($downsideReturns));
        
        if ($downsideDeviation == 0) {
            return 0.0;
        }
        
        // Sortino Ratio = (Average Return - Risk-Free Rate) / Downside Deviation
        $sortinoRatio = ($avgReturn - $periodicRiskFreeRate) / $downsideDeviation;
        
        return round($sortinoRatio, 4);
    }

    /**
     * Calculate Maximum Adverse Excursion (MAE)
     * 
     * @param array $trades Array of trades with entry/exit prices and direction
     * @return float MAE value
     */
    public function calculateMAE(array $trades): float
    {
        if (empty($trades)) {
            return 0.0;
        }
        
        $maxAdverseExcursion = 0;
        
        foreach ($trades as $trade) {
            $entryPrice = (float) ($trade['entry_price'] ?? 0);
            $exitPrice = (float) ($trade['exit_price'] ?? $trade['close_price'] ?? 0);
            $direction = strtolower($trade['direction'] ?? 'buy');
            $pnl = (float) ($trade['profit_loss'] ?? $trade['pnl'] ?? 0);
            
            if ($entryPrice <= 0) {
                continue;
            }
            
            // For losing trades, MAE is the loss
            // For winning trades, MAE is the maximum drawdown during the trade
            // Simplified: use the worst case (if trade was a loss, use that)
            if ($pnl < 0) {
                $adverseExcursion = abs($pnl);
                $maxAdverseExcursion = max($maxAdverseExcursion, $adverseExcursion);
            }
        }
        
        return round($maxAdverseExcursion, 2);
    }

    /**
     * Calculate Maximum Favorable Excursion (MFE)
     * 
     * @param array $trades Array of trades
     * @return float MFE value
     */
    public function calculateMFE(array $trades): float
    {
        if (empty($trades)) {
            return 0.0;
        }
        
        $maxFavorableExcursion = 0;
        
        foreach ($trades as $trade) {
            $entryPrice = (float) ($trade['entry_price'] ?? 0);
            $exitPrice = (float) ($trade['exit_price'] ?? $trade['close_price'] ?? 0);
            $direction = strtolower($trade['direction'] ?? 'buy');
            $pnl = (float) ($trade['profit_loss'] ?? $trade['pnl'] ?? 0);
            
            if ($entryPrice <= 0) {
                continue;
            }
            
            // For winning trades, MFE is the profit
            // For losing trades, MFE is the maximum profit during the trade before it turned negative
            // Simplified: use the best case (if trade was a win, use that)
            if ($pnl > 0) {
                $favorableExcursion = $pnl;
                $maxFavorableExcursion = max($maxFavorableExcursion, $favorableExcursion);
            }
        }
        
        return round($maxFavorableExcursion, 2);
    }

    /**
     * Calculate Recovery Factor
     * 
     * @param float $netProfit Net profit
     * @param float $maxDrawdown Maximum drawdown (positive value)
     * @return float Recovery factor
     */
    public function calculateRecoveryFactor(float $netProfit, float $maxDrawdown): float
    {
        if ($maxDrawdown <= 0) {
            return $netProfit > 0 ? 999.0 : 0.0;
        }
        
        // Recovery Factor = Net Profit / Maximum Drawdown
        $recoveryFactor = $netProfit / $maxDrawdown;
        
        return round($recoveryFactor, 4);
    }

    /**
     * Calculate Calmar Ratio
     * 
     * @param float $annualReturn Annual return (as percentage)
     * @param float $maxDrawdown Maximum drawdown (as percentage)
     * @return float Calmar ratio
     */
    public function calculateCalmarRatio(float $annualReturn, float $maxDrawdown): float
    {
        if ($maxDrawdown <= 0) {
            return $annualReturn > 0 ? 999.0 : 0.0;
        }
        
        // Calmar Ratio = Annual Return / Maximum Drawdown
        $calmarRatio = $annualReturn / $maxDrawdown;
        
        return round($calmarRatio, 4);
    }

    /**
     * Calculate all enhanced metrics for a set of trades
     * 
     * @param array $trades Array of trades
     * @param float $netProfit Net profit
     * @param float $maxDrawdown Maximum drawdown
     * @param float|null $annualReturn Annual return (optional)
     * @param array|null $returns Periodic returns array (optional, for Sortino)
     * @return array All calculated metrics
     */
    public function calculateAllMetrics(
        array $trades,
        float $netProfit,
        float $maxDrawdown,
        ?float $annualReturn = null,
        ?array $returns = null
    ): array {
        return [
            'expectancy' => $this->calculateExpectancy($trades),
            'sortino_ratio' => $returns ? $this->calculateSortinoRatio($returns) : null,
            'mae' => $this->calculateMAE($trades),
            'mfe' => $this->calculateMFE($trades),
            'recovery_factor' => $this->calculateRecoveryFactor($netProfit, $maxDrawdown),
            'calmar_ratio' => $annualReturn !== null ? $this->calculateCalmarRatio($annualReturn, $maxDrawdown) : null,
        ];
    }
}

