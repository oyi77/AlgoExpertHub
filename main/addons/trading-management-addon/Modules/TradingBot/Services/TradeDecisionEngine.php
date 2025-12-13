<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

/**
 * TradeDecisionEngine
 * 
 * Makes trading decisions based on analysis
 */
class TradeDecisionEngine
{
    protected TechnicalAnalysisService $analysisService;

    public function __construct(TechnicalAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Determine if should enter trade
     * 
     * @param array $analysis Analysis result from TechnicalAnalysisService
     * @param TradingBot $bot
     * @return array ['should_enter' => bool, 'direction' => 'buy|sell', 'confidence' => float, 'reason' => string]
     */
    public function shouldEnterTrade(array $analysis, TradingBot $bot): array
    {
        // Handle Test Mode - always enter trade immediately
        if (isset($analysis['test_mode']) && $analysis['test_mode'] === true) {
            Log::info('TradeDecisionEngine: Test mode active, entering trade immediately', [
                'bot_id' => $bot->id,
                'signal' => $analysis['signal'],
            ]);
            
            return [
                'should_enter' => true,
                'direction' => $analysis['signal'] === 'buy' ? 'buy' : 'sell',
                'confidence' => $analysis['strength'] ?? 1.0,
                'reason' => 'Test mode: Immediate trade execution',
                'test_mode' => true,
            ];
        }

        if (!isset($analysis['signal']) || $analysis['signal'] === 'hold') {
            return [
                'should_enter' => false,
                'direction' => null,
                'confidence' => 0,
                'reason' => 'No trading signal',
            ];
        }

        // Check signal strength threshold (from filter strategy or bot config)
        $minStrength = 0.5; // Default 50%
        if ($bot->filterStrategy && isset($bot->filterStrategy->min_signal_strength)) {
            $minStrength = $bot->filterStrategy->min_signal_strength;
        }

        if ($analysis['strength'] < $minStrength) {
            return [
                'should_enter' => false,
                'direction' => null,
                'confidence' => $analysis['strength'],
                'reason' => 'Signal strength below threshold',
            ];
        }

        return [
            'should_enter' => true,
            'direction' => $analysis['signal'] === 'buy' ? 'buy' : 'sell',
            'confidence' => $analysis['strength'],
            'reason' => $analysis['reason'] ?? 'Technical analysis signal',
        ];
    }

    /**
     * Determine if should exit trade early
     * 
     * @param TradingBotPosition $position
     * @param array $analysis Current market analysis
     * @return bool
     */
    public function shouldExitTrade(TradingBotPosition $position, array $analysis): bool
    {
        // Exit if signal reverses
        if (isset($analysis['signal'])) {
            $currentDirection = in_array($position->direction, ['buy', 'long']) ? 'buy' : 'sell';
            
            if ($analysis['signal'] !== $currentDirection && $analysis['signal'] !== 'hold') {
                return true; // Signal reversed
            }
        }

        return false;
    }

    /**
     * Calculate position size based on trading preset
     * 
     * @param TradingBot $bot
     * @param Signal|null $signal
     * @param float|null $currentPrice
     * @return float Position size/quantity
     */
    public function calculatePositionSize(TradingBot $bot, ?Signal $signal = null, ?float $currentPrice = null): float
    {
        if (!$bot->tradingPreset) {
            return 0;
        }

        $preset = $bot->tradingPreset;
        $strategy = $preset->position_sizing_strategy ?? 'fixed';

        switch ($strategy) {
            case 'fixed':
                return $preset->position_sizing_value ?? 0.01;
            
            case 'percentage':
                // Percentage of account balance
                // Would need account balance from exchange connection
                $balance = 1000; // Placeholder
                $percentage = $preset->position_sizing_value ?? 1;
                return ($balance * $percentage / 100) / ($currentPrice ?? 1);
            
            case 'fixed_amount':
                // Fixed dollar amount
                $amount = $preset->position_sizing_value ?? 100;
                return $amount / ($currentPrice ?? 1);
            
            default:
                return 0.01;
        }
    }

    /**
     * Apply risk management (SL/TP) from preset
     * 
     * @param array $decision Trade decision
     * @param TradingBot $bot
     * @param float $entryPrice
     * @return array Updated decision with SL/TP
     */
    public function applyRiskManagement(array $decision, TradingBot $bot, float $entryPrice): array
    {
        if (!$bot->tradingPreset) {
            return $decision;
        }

        $preset = $bot->tradingPreset;

        // Apply stop loss
        if ($preset->stop_loss_type === 'percentage') {
            $slPercentage = $preset->stop_loss_value ?? 2;
            if ($decision['direction'] === 'buy') {
                $decision['stop_loss'] = $entryPrice * (1 - $slPercentage / 100);
            } else {
                $decision['stop_loss'] = $entryPrice * (1 + $slPercentage / 100);
            }
        } elseif ($preset->stop_loss_type === 'fixed') {
            $slAmount = $preset->stop_loss_value ?? 0;
            if ($decision['direction'] === 'buy') {
                $decision['stop_loss'] = $entryPrice - $slAmount;
            } else {
                $decision['stop_loss'] = $entryPrice + $slAmount;
            }
        }

        // Apply take profit
        if ($preset->take_profit_type === 'percentage') {
            $tpPercentage = $preset->take_profit_value ?? 3;
            if ($decision['direction'] === 'buy') {
                $decision['take_profit'] = $entryPrice * (1 + $tpPercentage / 100);
            } else {
                $decision['take_profit'] = $entryPrice * (1 - $tpPercentage / 100);
            }
        } elseif ($preset->take_profit_type === 'fixed') {
            $tpAmount = $preset->take_profit_value ?? 0;
            if ($decision['direction'] === 'buy') {
                $decision['take_profit'] = $entryPrice + $tpAmount;
            } else {
                $decision['take_profit'] = $entryPrice - $tpAmount;
            }
        }

        return $decision;
    }
}
