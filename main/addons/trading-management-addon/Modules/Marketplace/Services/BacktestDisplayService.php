<?php

namespace Addons\TradingManagement\Modules\Marketplace\Services;

use Addons\TradingManagement\Modules\Marketplace\Models\TemplateBacktest;

class BacktestDisplayService
{
    public function formatForDisplay(TemplateBacktest $backtest): array
    {
        return [
            'summary' => $this->getSummary($backtest),
            'performance_metrics' => $this->getPerformanceMetrics($backtest),
            'risk_metrics' => $this->getRiskMetrics($backtest),
            'trade_statistics' => $this->getTradeStatistics($backtest),
            'period_info' => $this->getPeriodInfo($backtest),
            'equity_curve' => $backtest->getEquityCurveData(),
            'monthly_breakdown' => $this->getMonthlyBreakdown($backtest),
            'symbol_performance' => $this->getSymbolPerformance($backtest),
        ];
    }

    protected function getSummary(TemplateBacktest $backtest): array
    {
        return [
            'win_rate' => $backtest->win_rate,
            'net_profit' => $backtest->net_profit_percent,
            'profit_factor' => $backtest->profit_factor,
            'max_drawdown' => $backtest->max_drawdown,
            'total_trades' => $backtest->total_trades,
            'period' => $this->formatPeriod($backtest),
        ];
    }

    protected function getPerformanceMetrics(TemplateBacktest $backtest): array
    {
        $roi = (($backtest->capital_final - $backtest->capital_initial) / $backtest->capital_initial) * 100;
        
        return [
            'initial_capital' => number_format($backtest->capital_initial, 2),
            'final_capital' => number_format($backtest->capital_final, 2),
            'net_profit' => number_format($backtest->capital_final - $backtest->capital_initial, 2),
            'roi' => round($roi, 2),
            'profit_percent' => $backtest->net_profit_percent,
        ];
    }

    protected function getRiskMetrics(TemplateBacktest $backtest): array
    {
        return [
            'max_drawdown' => $backtest->max_drawdown,
            'profit_factor' => $backtest->profit_factor,
            'risk_reward_ratio' => $this->calculateRiskRewardRatio($backtest),
            'win_rate' => $backtest->win_rate,
        ];
    }

    protected function getTradeStatistics(TemplateBacktest $backtest): array
    {
        return [
            'total_trades' => $backtest->total_trades,
            'winning_trades' => $backtest->winning_trades,
            'losing_trades' => $backtest->losing_trades,
            'win_rate' => $backtest->win_rate,
            'avg_win' => $backtest->avg_win_percent,
            'avg_loss' => $backtest->avg_loss_percent,
            'largest_win' => $this->getLargestWin($backtest),
            'largest_loss' => $this->getLargestLoss($backtest),
        ];
    }

    protected function getPeriodInfo(TemplateBacktest $backtest): array
    {
        return [
            'start_date' => $backtest->backtest_period_start?->format('Y-m-d'),
            'end_date' => $backtest->backtest_period_end?->format('Y-m-d'),
            'duration_days' => $backtest->backtest_period_start && $backtest->backtest_period_end
                ? $backtest->backtest_period_start->diffInDays($backtest->backtest_period_end)
                : 0,
            'symbols' => $backtest->symbols_tested,
            'timeframes' => $backtest->timeframes_tested,
        ];
    }

    protected function getMonthlyBreakdown(TemplateBacktest $backtest): array
    {
        if (empty($backtest->detailed_results)) {
            return [];
        }

        $monthly = [];
        foreach ($backtest->detailed_results as $trade) {
            if (!isset($trade['exit_time'])) continue;
            
            $month = date('Y-m', strtotime($trade['exit_time']));
            
            if (!isset($monthly[$month])) {
                $monthly[$month] = [
                    'month' => $month,
                    'trades' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'profit' => 0,
                ];
            }
            
            $monthly[$month]['trades']++;
            $monthly[$month]['profit'] += $trade['profit'] ?? 0;
            
            if (($trade['profit'] ?? 0) > 0) {
                $monthly[$month]['wins']++;
            } else {
                $monthly[$month]['losses']++;
            }
        }

        return array_values($monthly);
    }

    protected function getSymbolPerformance(TemplateBacktest $backtest): array
    {
        if (empty($backtest->detailed_results)) {
            return [];
        }

        $symbols = [];
        foreach ($backtest->detailed_results as $trade) {
            $symbol = $trade['symbol'] ?? 'UNKNOWN';
            
            if (!isset($symbols[$symbol])) {
                $symbols[$symbol] = [
                    'symbol' => $symbol,
                    'trades' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'profit' => 0,
                    'win_rate' => 0,
                ];
            }
            
            $symbols[$symbol]['trades']++;
            $symbols[$symbol]['profit'] += $trade['profit'] ?? 0;
            
            if (($trade['profit'] ?? 0) > 0) {
                $symbols[$symbol]['wins']++;
            } else {
                $symbols[$symbol]['losses']++;
            }
        }

        foreach ($symbols as &$data) {
            $data['win_rate'] = $data['trades'] > 0 
                ? round(($data['wins'] / $data['trades']) * 100, 2) 
                : 0;
        }

        return array_values($symbols);
    }

    protected function calculateRiskRewardRatio(TemplateBacktest $backtest): ?float
    {
        if (!$backtest->avg_win_percent || !$backtest->avg_loss_percent) {
            return null;
        }

        return round(abs($backtest->avg_win_percent / $backtest->avg_loss_percent), 2);
    }

    protected function getLargestWin(TemplateBacktest $backtest): ?float
    {
        if (empty($backtest->detailed_results)) {
            return null;
        }

        $profits = array_column($backtest->detailed_results, 'profit');
        return !empty($profits) ? max($profits) : null;
    }

    protected function getLargestLoss(TemplateBacktest $backtest): ?float
    {
        if (empty($backtest->detailed_results)) {
            return null;
        }

        $profits = array_column($backtest->detailed_results, 'profit');
        return !empty($profits) ? min($profits) : null;
    }

    protected function formatPeriod(TemplateBacktest $backtest): string
    {
        if (!$backtest->backtest_period_start || !$backtest->backtest_period_end) {
            return 'Unknown period';
        }

        return sprintf(
            '%s to %s',
            $backtest->backtest_period_start->format('M d, Y'),
            $backtest->backtest_period_end->format('M d, Y')
        );
    }
}


