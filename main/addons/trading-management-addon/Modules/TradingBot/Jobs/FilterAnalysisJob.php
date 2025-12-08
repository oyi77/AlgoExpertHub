<?php

namespace Addons\TradingManagement\Modules\TradingBot\Jobs;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\RiskManagement\Jobs\RiskManagementJob;
use Addons\TradingManagement\Modules\FilterStrategy\Services\FilterStrategyEvaluator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * FilterAnalysisJob
 * 
 * Applies filter strategies and AI analysis to trading decisions
 */
class FilterAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected TradingBot $bot;
    protected array $decision;
    protected array $marketData;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(TradingBot $bot, array $decision, array $marketData)
    {
        $this->bot = $bot;
        $this->decision = $decision;
        $this->marketData = $marketData;
    }

    public function handle()
    {
        try {
            // 1. Apply filter strategy if configured
            if ($this->bot->filterStrategy) {
                $filterResult = $this->applyFilterStrategy();
                
                if (!$filterResult['pass']) {
                    Log::info('Trading decision rejected by filter strategy', [
                        'bot_id' => $this->bot->id,
                        'reason' => $filterResult['reason'],
                    ]);
                    return; // Stop processing
                }
            }

            // 2. Apply AI analysis if configured
            if ($this->bot->aiModelProfile && $this->bot->aiModelProfile->enabled) {
                $aiResult = $this->applyAiAnalysis();
                
                if (!$aiResult['execute']) {
                    Log::info('Trading decision rejected by AI analysis', [
                        'bot_id' => $this->bot->id,
                        'reason' => $aiResult['reason'],
                    ]);
                    return; // Stop processing
                }

                // AI may adjust the decision
                if (isset($aiResult['adjusted_decision'])) {
                    $this->decision = array_merge($this->decision, $aiResult['adjusted_decision']);
                }
            }

            // 3. If passed all filters, dispatch to Risk Management Worker
            RiskManagementJob::dispatch($this->bot, $this->decision, $this->marketData);

            Log::info('Trading decision passed filters, dispatched to risk management', [
                'bot_id' => $this->bot->id,
                'direction' => $this->decision['direction'],
            ]);

        } catch (\Exception $e) {
            Log::error('Filter analysis job failed', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Apply filter strategy
     */
    protected function applyFilterStrategy(): array
    {
        try {
            $evaluator = app(FilterStrategyEvaluator::class);
            
            // Create a mock signal from decision for filter evaluation
            $signal = (object) [
                'pair' => (object) ['name' => $this->marketData[0]['symbol'] ?? ''],
                'time' => (object) ['name' => $this->marketData[0]['timeframe'] ?? ''],
                'direction' => $this->decision['direction'],
                'open_price' => $this->marketData[0]['close'] ?? 0,
            ];

            $result = $evaluator->evaluate($this->bot->filterStrategy, $signal, $this->bot->exchangeConnection);

            return [
                'pass' => $result['pass'] ?? false,
                'reason' => $result['reason'] ?? 'Filter evaluation failed',
            ];
        } catch (\Exception $e) {
            Log::error('Filter strategy evaluation failed', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
            ]);
            
            // On error, allow to proceed (fail open)
            return ['pass' => true, 'reason' => 'Filter evaluation error, allowing trade'];
        }
    }

    /**
     * Apply AI analysis
     */
    protected function applyAiAnalysis(): array
    {
        try {
            // Check if AI trading addon is available
            if (!class_exists(\Addons\AiTradingAddon\App\Services\MarketAnalysisAiService::class)) {
                return ['execute' => true, 'reason' => 'AI addon not available'];
            }

            $aiAnalysisService = app(\Addons\AiTradingAddon\App\Services\MarketAnalysisAiService::class);
            $decisionEngine = app(\Addons\AiTradingAddon\App\Services\AiDecisionEngine::class);

            $signalData = [
                'pair' => $this->marketData[0]['symbol'] ?? null,
                'timeframe' => $this->marketData[0]['timeframe'] ?? null,
                'direction' => $this->decision['direction'] ?? null,
                'open_price' => $this->marketData[0]['close'] ?? null,
            ];

            $aiResult = $aiAnalysisService->analyzeSignal($signalData, $this->bot->aiModelProfile);
            $decision = $decisionEngine->makeDecision($aiResult, $this->bot->tradingPreset);

            return [
                'execute' => $decision['execute'] ?? false,
                'reason' => $decision['reason'] ?? 'AI analysis completed',
                'adjusted_decision' => $decision['adjusted_decision'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('AI analysis failed', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
            ]);
            
            // On error, allow to proceed (fail open)
            return ['execute' => true, 'reason' => 'AI analysis error, allowing trade'];
        }
    }
}
