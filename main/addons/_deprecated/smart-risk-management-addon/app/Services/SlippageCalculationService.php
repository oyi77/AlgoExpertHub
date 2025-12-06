<?php

namespace Addons\SmartRiskManagement\App\Services;

use Addons\SmartRiskManagement\App\Models\SrmPrediction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SlippageCalculationService
{
    /**
     * Calculate slippage in pips from execution log
     * 
     * @param object $log ExecutionLog object
     * @param float $signalEntryPrice Signal entry price from provider
     * @param float $executedPrice Actual executed price
     * @return float Slippage in pips
     */
    public function calculateSlippage($log, float $signalEntryPrice, float $executedPrice): float
    {
        if (!$signalEntryPrice || !$executedPrice || $signalEntryPrice == 0) {
            return 0.0;
        }

        $symbol = $log->symbol ?? '';
        $direction = $log->direction ?? 'buy';
        
        // Calculate price difference
        $priceDiff = abs($executedPrice - $signalEntryPrice);
        
        // Convert to pips based on symbol type
        $pipValue = $this->getPipValue($symbol);
        
        // For BUY: positive slippage if executed higher, negative if lower
        // For SELL: positive slippage if executed lower, negative if higher
        $slippage = $priceDiff / $pipValue;
        
        if ($direction === 'buy') {
            // BUY: executed higher = positive slippage (bad), executed lower = negative slippage (good)
            $slippage = ($executedPrice > $signalEntryPrice) ? $slippage : -$slippage;
        } else {
            // SELL: executed lower = positive slippage (bad), executed higher = negative slippage (good)
            $slippage = ($executedPrice < $signalEntryPrice) ? $slippage : -$slippage;
        }
        
        return round($slippage, 4);
    }

    /**
     * Get pip value for a symbol
     * 
     * @param string $symbol Trading symbol
     * @return float Pip value
     */
    protected function getPipValue(string $symbol): float
    {
        // JPY pairs: 1 pip = 0.01
        $jpyPairs = ['USDJPY', 'EURJPY', 'GBPJPY', 'AUDJPY', 'CADJPY', 'CHFJPY', 'NZDJPY'];
        
        foreach ($jpyPairs as $pair) {
            if (stripos($symbol, $pair) !== false) {
                return 0.01;
            }
        }
        
        // Most forex pairs: 1 pip = 0.0001
        // Crypto: Use percentage or fixed decimal (0.01 for most)
        // Stocks: Use cents (0.01)
        
        // Check if crypto
        $cryptoPairs = ['BTC', 'ETH', 'LTC', 'XRP', 'DOGE', 'BNB', 'ADA', 'SOL'];
        foreach ($cryptoPairs as $crypto) {
            if (stripos($symbol, $crypto) !== false) {
                return 0.01; // Simplified for crypto
            }
        }
        
        // Default: 0.0001 for most forex pairs
        return 0.0001;
    }

    /**
     * Store slippage data in execution log
     * 
     * @param object $log ExecutionLog object
     * @param float $slippage Slippage in pips
     * @return void
     */
    public function storeSlippage($log, float $slippage): void
    {
        try {
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
                return;
            }
            
            $executionLog = \Addons\TradingExecutionEngine\App\Models\ExecutionLog::find($log->id);
            if (!$executionLog) {
                return;
            }
            
            // Update slippage field if it exists
            if (Schema::hasColumn('execution_logs', 'slippage')) {
                $executionLog->slippage = $slippage;
                $executionLog->save();
            }
            
            // Store prediction with actual value for accuracy tracking
            if ($log->signal_id) {
                $prediction = SrmPrediction::where('signal_id', $log->signal_id)
                    ->where('prediction_type', 'slippage')
                    ->whereNull('actual_value')
                    ->latest()
                    ->first();
                
                if ($prediction) {
                    $prediction->actual_value = $slippage;
                    
                    // Calculate accuracy (percentage error)
                    if ($prediction->predicted_value != 0) {
                        $error = abs($prediction->predicted_value - $slippage);
                        $accuracy = max(0, 100 - ($error / abs($prediction->predicted_value)) * 100);
                        $prediction->accuracy = round($accuracy, 2);
                    }
                    
                    $prediction->save();
                }
            }
        } catch (\Exception $e) {
            Log::error("SlippageCalculationService: Failed to store slippage", [
                'log_id' => $log->id ?? null,
                'slippage' => $slippage,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

