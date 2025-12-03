<?php

namespace Addons\SmartRiskManagement\App\Services;

use App\Models\Signal;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionLog;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Illuminate\Support\Facades\Log;

class SrmIntegrationService
{
    protected MarketContextService $marketContextService;
    protected SlippagePredictionService $slippagePredictionService;
    protected PerformanceScoreService $performanceScoreService;
    protected SignalQualityFilterService $signalQualityFilterService;
    protected SlBufferingService $slBufferingService;
    protected RiskOptimizationService $riskOptimizationService;
    protected SlippageCalculationService $slippageCalculationService;

    public function __construct(
        MarketContextService $marketContextService,
        SlippagePredictionService $slippagePredictionService,
        PerformanceScoreService $performanceScoreService,
        SignalQualityFilterService $signalQualityFilterService,
        SlBufferingService $slBufferingService,
        RiskOptimizationService $riskOptimizationService,
        SlippageCalculationService $slippageCalculationService
    ) {
        $this->marketContextService = $marketContextService;
        $this->slippagePredictionService = $slippagePredictionService;
        $this->performanceScoreService = $performanceScoreService;
        $this->signalQualityFilterService = $signalQualityFilterService;
        $this->slBufferingService = $slBufferingService;
        $this->riskOptimizationService = $riskOptimizationService;
        $this->slippageCalculationService = $slippageCalculationService;
    }

    /**
     * Apply SRM logic before signal execution
     * Returns adjusted options and whether to proceed
     */
    public function applySrmBeforeExecution(
        Signal $signal,
        ExecutionConnection $connection,
        array $options = []
    ): array {
        try {
            // Check if SRM is enabled
            if (!config('srm.enable_srm', true)) {
                return ['proceed' => true, 'options' => $options, 'adjustments' => []];
            }

            $adjustments = [];

            // Get signal provider info
            $signalProviderId = $this->getSignalProviderId($signal);
            $signalProviderType = $this->getSignalProviderType($signal);

            if (!$signalProviderId) {
                return ['proceed' => true, 'options' => $options, 'adjustments' => []];
            }

            // Get market context
            $symbol = $this->getSymbolFromSignal($signal);
            $context = $this->marketContextService->getMarketContext($symbol);

            // Check signal quality filter
            $currentPrice = $options['current_price'] ?? $signal->open_price;
            $qualityCheck = $this->signalQualityFilterService->shouldRejectSignal($signal, $signalProviderId, $currentPrice);

            if ($qualityCheck['should_reject']) {
                return [
                    'proceed' => false,
                    'reason' => $qualityCheck['reason'],
                    'rejection_type' => $qualityCheck['rejection_type'],
                ];
            }

            // Get performance score
            $performanceScore = $this->performanceScoreService->getPerformanceScore($signalProviderId, $signalProviderType);

            // Predict slippage
            $slippagePrediction = $this->slippagePredictionService->getPredictionWithConfidence(
                $symbol,
                array_merge($context, ['signal_provider_id' => $signalProviderId])
            );

            // Apply SL buffering
            $slBuffer = $this->slBufferingService->calculateSlBuffer(
                $slippagePrediction['predicted_value'],
                $symbol,
                $context['trading_session']
            );

            if ($signal->sl > 0) {
                $adjustedSl = $this->slBufferingService->applySlBuffer($signal->sl, $slBuffer, $signal->direction);
                $options['sl_price'] = $adjustedSl;
                $adjustments['sl_buffer'] = $slBuffer;
                $adjustments['original_sl'] = $signal->sl;
                $adjustments['adjusted_sl'] = $adjustedSl;
            }

            // Calculate optimal lot size
            $equity = $options['equity'] ?? 10000; // Default equity
            $riskTolerance = $options['risk_tolerance'] ?? 1.0; // Default 1%
            $slDistance = abs($signal->open_price - ($options['sl_price'] ?? $signal->sl ?? $signal->open_price * 0.99));

            $optimalLot = $this->riskOptimizationService->calculateOptimalLot(
                $equity,
                $riskTolerance,
                $slDistance,
                $slippagePrediction['predicted_value'],
                $performanceScore,
                [
                    'min_lot' => $options['min_lot'] ?? 0.01,
                    'max_lot' => $options['max_lot'] ?? null,
                    'max_position_size' => $options['max_position_size'] ?? null,
                    'entry_price' => $signal->open_price,
                ]
            );

            // Apply lot adjustment
            if (isset($options['base_quantity'])) {
                $baseLot = $options['base_quantity'];
                $options['quantity'] = $optimalLot;
                $adjustments['base_lot'] = $baseLot;
                $adjustments['adjusted_lot'] = $optimalLot;
                $adjustments['lot_adjustment_reason'] = $this->riskOptimizationService->getAdjustmentReason(
                    $baseLot,
                    $optimalLot,
                    $performanceScore,
                    $slippagePrediction['predicted_value']
                );
            }

            // Store adjustments for transparency
            $adjustments['performance_score'] = $performanceScore;
            $adjustments['predicted_slippage'] = $slippagePrediction['predicted_value'];
            $adjustments['slippage_confidence'] = $slippagePrediction['confidence_score'];

            return [
                'proceed' => true,
                'options' => $options,
                'adjustments' => $adjustments,
            ];
        } catch (\Exception $e) {
            Log::error("SrmIntegrationService: Failed to apply SRM logic", [
                'signal_id' => $signal->id ?? null,
                'error' => $e->getMessage(),
            ]);

            // On error, proceed without SRM (fail open)
            return ['proceed' => true, 'options' => $options, 'adjustments' => []];
        }
    }

    /**
     * Store market context and predictions after execution log creation
     */
    public function storeMarketContext(ExecutionLog $executionLog, Signal $signal, array $context): void
    {
        try {
            if (!config('srm.enable_srm', true)) {
                return;
            }

            // Update execution log with market context
            $executionLog->update([
                'market_atr' => $context['atr'] ?? null,
                'trading_session' => $context['trading_session'] ?? null,
                'day_of_week' => $context['day_of_week'] ?? null,
                'volatility_index' => $context['volatility_index'] ?? null,
                'signal_provider_id' => $this->getSignalProviderId($signal),
                'signal_provider_type' => $this->getSignalProviderType($signal),
            ]);

            // Store slippage prediction
            $signalProviderId = $this->getSignalProviderId($signal);
            if ($signalProviderId) {
                $prediction = $this->slippagePredictionService->getPredictionWithConfidence(
                    $executionLog->symbol,
                    array_merge($context, ['signal_provider_id' => $signalProviderId])
                );

                $this->slippagePredictionService->storePrediction(
                    $executionLog->id,
                    $signal->id,
                    $executionLog->connection_id,
                    $executionLog->symbol,
                    $context,
                    $prediction['predicted_value'],
                    $prediction['confidence_score']
                );
            }
        } catch (\Exception $e) {
            Log::error("SrmIntegrationService: Failed to store market context", [
                'execution_log_id' => $executionLog->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store SRM adjustments in position
     */
    public function storeSrmAdjustments(ExecutionPosition $position, array $adjustments): void
    {
        try {
            if (empty($adjustments)) {
                return;
            }

            $position->update([
                'predicted_slippage' => $adjustments['predicted_slippage'] ?? null,
                'performance_score_at_entry' => $adjustments['performance_score'] ?? null,
                'srm_adjusted_lot' => $adjustments['adjusted_lot'] ?? $position->quantity,
                'srm_sl_buffer' => $adjustments['sl_buffer'] ?? null,
                'srm_adjustment_reason' => json_encode($adjustments),
            ]);
        } catch (\Exception $e) {
            Log::error("SrmIntegrationService: Failed to store SRM adjustments", [
                'position_id' => $position->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate and store slippage after execution
     */
    public function calculateAndStoreSlippage(ExecutionLog $executionLog, Signal $signal, float $executedPrice): void
    {
        try {
            if (!config('srm.enable_srm', true)) {
                return;
            }

            $slippage = $this->slippageCalculationService->calculateSlippage(
                $executionLog,
                $signal->open_price,
                $executedPrice
            );

            $this->slippageCalculationService->storeSlippage($executionLog, $slippage);

            // Update execution log latency
            if ($signal->published_date) {
                $latency = $executionLog->executed_at->diffInMilliseconds($signal->published_date);
                $executionLog->update(['latency_ms' => $latency]);
            }
        } catch (\Exception $e) {
            Log::error("SrmIntegrationService: Failed to calculate slippage", [
                'execution_log_id' => $executionLog->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get signal provider ID from signal
     */
    protected function getSignalProviderId(Signal $signal): ?string
    {
        // Check if signal has channel_source_id (from Multi-Channel Addon)
        if ($signal->channel_source_id) {
            return (string) $signal->channel_source_id;
        }

        // Could also check if signal was created by a user
        // For now, return null if no channel source
        return null;
    }

    /**
     * Get signal provider type
     */
    protected function getSignalProviderType(Signal $signal): string
    {
        if ($signal->channel_source_id) {
            return 'channel_source';
        }

        return 'user';
    }

    /**
     * Get symbol from signal
     */
    protected function getSymbolFromSignal(Signal $signal): string
    {
        if ($signal->pair && $signal->pair->name) {
            return $signal->pair->name;
        }

        return 'EURUSD'; // Default fallback
    }

    /**
     * Pre-execution hook: Filter and adjust before execution
     * Compatible with SignalExecutionService integration
     */
    public function preExecuteSignal(
        Signal $signal,
        ExecutionConnection $connection,
        array $options = []
    ): array {
        $result = $this->applySrmBeforeExecution($signal, $connection, $options);
        
        return [
            'should_skip' => !$result['proceed'],
            'reason' => $result['reason'] ?? null,
            'options_adjustments' => $result['options'] ?? $options,
        ];
    }

    /**
     * Post-execution hook: Store data after execution
     */
    public function postExecuteSignal(
        Signal $signal,
        ExecutionConnection $connection,
        ExecutionLog $executionLog,
        ExecutionPosition $position
    ): void {
        try {
            // Get market context
            $symbol = $this->getSymbolFromSignal($signal);
            $context = $this->marketContextService->getMarketContext($symbol);
            
            // Store market context
            $this->storeMarketContext($executionLog, $signal, $context);
            
            // Calculate and store slippage if we have executed price
            if ($executionLog->entry_price) {
                $this->calculateAndStoreSlippage($executionLog, $signal, $executionLog->entry_price);
            }
        } catch (\Exception $e) {
            Log::warning("SrmIntegrationService: Post-execution hook failed", [
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate actual slippage from execution
     */
    public function calculateActualSlippage(float $expectedPrice, float $executedPrice): float
    {
        return abs($executedPrice - $expectedPrice);
    }

    /**
     * Get market volatility for a signal
     */
    public function getMarketVolatility(Signal $signal, ExecutionConnection $connection): ?float
    {
        try {
            $symbol = $this->getSymbolFromSignal($signal);
            $context = $this->marketContextService->getMarketContext($symbol);
            return $context['volatility_index'] ?? null;
        } catch (\Exception $e) {
            Log::warning("SrmIntegrationService: Failed to get market volatility", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get current trading session
     */
    public function getTradingSession(): string
    {
        return $this->marketContextService->getCurrentTradingSession();
    }
}

