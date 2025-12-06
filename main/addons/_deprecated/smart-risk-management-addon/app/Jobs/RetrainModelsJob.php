<?php

namespace Addons\SmartRiskManagement\App\Jobs;

use Addons\SmartRiskManagement\App\Services\ModelTrainingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetrainModelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 3600; // 1 hour

    /**
     * Execute the job.
     */
    public function handle(ModelTrainingService $trainingService): void
    {
        try {
            Log::info("RetrainModelsJob: Starting model retraining");
            
            // Collect training data
            $slippageData = $this->collectSlippageTrainingData();
            $performanceData = $this->collectPerformanceTrainingData();
            
            // Train models
            $slippageResult = $trainingService->trainSlippagePredictionModel($slippageData);
            $performanceResult = $trainingService->trainPerformanceScoreModel($performanceData);
            
            // Validate models
            $slippageValid = $trainingService->validateModel('slippage_prediction', $slippageResult['version']);
            $performanceValid = $trainingService->validateModel('performance_score', $performanceResult['version']);
            
            // Deploy if accuracy improved
            if ($slippageValid['valid'] && $slippageValid['accuracy'] > 70) {
                $trainingService->deployModel('slippage_prediction', $slippageResult['version']);
                Log::info("RetrainModelsJob: Deployed new slippage prediction model", [
                    'version' => $slippageResult['version'],
                    'accuracy' => $slippageValid['accuracy'],
                ]);
            }
            
            if ($performanceValid['valid'] && $performanceValid['accuracy'] > 75) {
                $trainingService->deployModel('performance_score', $performanceResult['version']);
                Log::info("RetrainModelsJob: Deployed new performance score model", [
                    'version' => $performanceResult['version'],
                    'accuracy' => $performanceValid['accuracy'],
                ]);
            }
            
            Log::info("RetrainModelsJob: Model retraining completed");
        } catch (\Exception $e) {
            Log::error("RetrainModelsJob: Job failed", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Collect training data for slippage prediction
     */
    protected function collectSlippageTrainingData(): array
    {
        try {
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
                return [];
            }
            
            $logs = \Addons\TradingExecutionEngine\App\Models\ExecutionLog::whereNotNull('slippage')
                ->whereNotNull('market_atr')
                ->whereNotNull('trading_session')
                ->where('status', 'executed')
                ->limit(1000)
                ->get();
            
            $data = [];
            foreach ($logs as $log) {
                $data[] = [
                    'symbol' => $log->symbol,
                    'trading_session' => $log->trading_session,
                    'atr' => $log->market_atr,
                    'day_of_week' => $log->day_of_week,
                    'slippage' => abs($log->slippage),
                ];
            }
            
            return $data;
        } catch (\Exception $e) {
            Log::error("RetrainModelsJob: Failed to collect slippage training data", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Collect training data for performance score
     */
    protected function collectPerformanceTrainingData(): array
    {
        try {
            // Use existing metrics as training data
            $metrics = \Addons\SmartRiskManagement\App\Models\SignalProviderMetrics::where('total_signals', '>', 10)
                ->limit(500)
                ->get();
            
            $data = [];
            foreach ($metrics as $metric) {
                $data[] = [
                    'win_rate' => $metric->win_rate,
                    'max_drawdown' => $metric->max_drawdown,
                    'reward_to_risk' => $metric->reward_to_risk_ratio,
                    'sl_compliance' => $metric->sl_compliance_rate,
                    'performance_score' => $metric->performance_score,
                ];
            }
            
            return $data;
        } catch (\Exception $e) {
            Log::error("RetrainModelsJob: Failed to collect performance training data", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("RetrainModelsJob: Job failed permanently", [
            'error' => $exception->getMessage(),
        ]);
    }
}

