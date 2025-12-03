<?php

namespace Addons\SmartRiskManagement\App\Services;

use Addons\SmartRiskManagement\App\Models\SrmPrediction;
use Addons\SmartRiskManagement\App\Models\SrmModelVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlippagePredictionService
{
    protected MarketContextService $marketContextService;

    public function __construct(MarketContextService $marketContextService)
    {
        $this->marketContextService = $marketContextService;
    }

    /**
     * Predict slippage for a signal
     * 
     * Phase 2: Simple average-based prediction
     * Phase 3: Upgrade to ML model
     * 
     * @param string $symbol Trading symbol
     * @param string $tradingSession Trading session
     * @param float $atr ATR value
     * @param string|null $signalProviderId Signal provider ID
     * @return float Predicted slippage in pips
     */
    public function predictSlippage(
        string $symbol,
        string $tradingSession,
        float $atr,
        ?string $signalProviderId = null
    ): float {
        try {
            // Phase 2: Simple average-based prediction
            // Calculate average historical slippage for similar conditions
            
            $cacheKey = "srm_slippage_pred_{$symbol}_{$tradingSession}_" . ($signalProviderId ?? 'default');
            
            return Cache::remember($cacheKey, 300, function () use ($symbol, $tradingSession, $atr, $signalProviderId) {
                return $this->calculateAverageSlippage($symbol, $tradingSession, $signalProviderId);
            });
        } catch (\Exception $e) {
            Log::error("SlippagePredictionService: Failed to predict slippage", [
                'symbol' => $symbol,
                'trading_session' => $tradingSession,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback: return default slippage based on ATR
            return min($atr * 0.5, 3.0); // Max 3 pips
        }
    }

    /**
     * Calculate average slippage from historical data
     */
    protected function calculateAverageSlippage(
        string $symbol,
        string $tradingSession,
        ?string $signalProviderId = null
    ): float {
        try {
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
                return $this->getDefaultSlippage($symbol);
            }
            
            $query = \Addons\TradingExecutionEngine\App\Models\ExecutionLog::where('symbol', $symbol)
                ->where('trading_session', $tradingSession)
                ->whereNotNull('slippage')
                ->where('status', 'executed');
            
            if ($signalProviderId) {
                $query->where('signal_provider_id', $signalProviderId);
            }
            
            // Get last 100 executions
            $logs = $query->orderBy('executed_at', 'desc')
                ->limit(100)
                ->get();
            
            if ($logs->isEmpty()) {
                return $this->getDefaultSlippage($symbol);
            }
            
            // Calculate weighted average (more recent = higher weight)
            $totalWeight = 0;
            $weightedSum = 0;
            
            foreach ($logs as $index => $log) {
                $weight = 100 - $index; // More recent = higher weight
                $slippage = abs($log->slippage ?? 0);
                $weightedSum += $slippage * $weight;
                $totalWeight += $weight;
            }
            
            $avgSlippage = $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
            
            return round(max(0, $avgSlippage), 4);
        } catch (\Exception $e) {
            Log::warning("SlippagePredictionService: Failed to calculate average slippage", [
                'error' => $e->getMessage(),
            ]);
            return $this->getDefaultSlippage($symbol);
        }
    }

    /**
     * Get default slippage for a symbol
     */
    protected function getDefaultSlippage(string $symbol): float
    {
        // Default slippage values in pips
        $defaults = [
            'EURUSD' => 0.5,
            'GBPUSD' => 0.8,
            'USDJPY' => 0.4,
            'AUDUSD' => 0.6,
            'USDCAD' => 0.5,
            'XAUUSD' => 2.0, // Gold
            'BTCUSD' => 5.0, // Bitcoin
        ];

        foreach ($defaults as $pair => $slippage) {
            if (stripos($symbol, $pair) !== false) {
                return $slippage;
            }
        }

        return 1.0; // Default
    }

    /**
     * Get prediction with confidence score
     * 
     * @param string $symbol Trading symbol
     * @param array $context Market context
     * @return array ['predicted_value' => float, 'confidence_score' => float]
     */
    public function getPredictionWithConfidence(string $symbol, array $context): array
    {
        $tradingSession = $context['trading_session'] ?? $this->marketContextService->getTradingSession();
        $atr = $context['atr'] ?? $this->marketContextService->getATR($symbol);
        $signalProviderId = $context['signal_provider_id'] ?? null;
        
        $predictedSlippage = $this->predictSlippage($symbol, $tradingSession, $atr, $signalProviderId);
        
        // Confidence based on data availability
        $confidence = $this->calculateConfidence($symbol, $tradingSession, $signalProviderId);
        
        return [
            'predicted_value' => $predictedSlippage,
            'confidence_score' => $confidence,
        ];
    }

    /**
     * Calculate confidence score based on data availability
     */
    protected function calculateConfidence(
        string $symbol,
        string $tradingSession,
        ?string $signalProviderId = null
    ): float {
        try {
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
                return 50.0; // Low confidence if no data
            }
            
            $query = \Addons\TradingExecutionEngine\App\Models\ExecutionLog::where('symbol', $symbol)
                ->where('trading_session', $tradingSession)
                ->whereNotNull('slippage')
                ->where('status', 'executed');
            
            if ($signalProviderId) {
                $query->where('signal_provider_id', $signalProviderId);
            }
            
            $count = $query->count();
            
            // Confidence increases with more data points
            // 0-10 samples: 30-60% confidence
            // 10-50 samples: 60-80% confidence
            // 50+ samples: 80-95% confidence
            
            if ($count >= 50) {
                return min(95, 80 + ($count - 50) * 0.1);
            } elseif ($count >= 10) {
                return 60 + (($count - 10) / 40) * 20;
            } else {
                return 30 + ($count / 10) * 30;
            }
        } catch (\Exception $e) {
            return 50.0;
        }
    }

    /**
     * Store prediction
     */
    public function storePrediction(
        int $executionLogId,
        int $signalId,
        ?int $connectionId,
        string $symbol,
        array $context,
        float $predictedValue,
        float $confidenceScore
    ): SrmPrediction {
        return SrmPrediction::create([
            'execution_log_id' => $executionLogId,
            'signal_id' => $signalId,
            'connection_id' => $connectionId,
            'prediction_type' => 'slippage',
            'symbol' => $symbol,
            'trading_session' => $context['trading_session'] ?? null,
            'day_of_week' => $context['day_of_week'] ?? null,
            'market_atr' => $context['atr'] ?? null,
            'volatility_index' => $context['volatility_index'] ?? null,
            'signal_provider_id' => $context['signal_provider_id'] ?? null,
            'predicted_value' => $predictedValue,
            'confidence_score' => $confidenceScore,
            'model_version' => 'v1.0', // Phase 2: simple model
            'model_type' => 'weighted_average',
        ]);
    }

    /**
     * Update model accuracy
     */
    public function updateModelAccuracy(): void
    {
        try {
            // Get predictions with actual values
            $predictions = SrmPrediction::where('prediction_type', 'slippage')
                ->whereNotNull('actual_value')
                ->whereNull('accuracy')
                ->get();
            
            foreach ($predictions as $prediction) {
                if ($prediction->predicted_value != 0) {
                    $error = abs($prediction->predicted_value - $prediction->actual_value);
                    $accuracy = max(0, 100 - ($error / abs($prediction->predicted_value)) * 100);
                    $prediction->accuracy = round($accuracy, 2);
                    $prediction->save();
                }
            }
        } catch (\Exception $e) {
            Log::error("SlippagePredictionService: Failed to update model accuracy", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

