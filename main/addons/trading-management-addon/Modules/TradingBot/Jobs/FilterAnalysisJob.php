<?php

namespace Addons\TradingManagement\Modules\TradingBot\Jobs;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\RiskManagement\Jobs\RiskManagementJob;
use Addons\TradingManagement\Modules\FilterStrategy\Services\FilterStrategyEvaluator;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiDecision;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
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
        Log::info('FilterAnalysisJob: Starting filter analysis', [
            'bot_id' => $this->bot->id,
            'bot_name' => $this->bot->name,
            'has_filter_strategy' => !is_null($this->bot->filterStrategy),
            'has_ai_model' => !is_null($this->bot->aiModelProfile),
            'decision' => $this->decision,
        ]);

        try {
            // 1. Check if this is paper trading mode - skip all filter checks
            if (isset($this->decision['is_paper_trading']) && $this->decision['is_paper_trading'] === true) {
                Log::info('FilterAnalysisJob: Paper trading mode detected, bypassing all filter checks', [
                    'bot_id' => $this->bot->id,
                    'filter_strategy_id' => $this->bot->filterStrategy->id ?? null,
                    'note' => 'Paper trading mode skips filter evaluation for immediate execution',
                ]);
                // Skip to step 3 (dispatch to risk management)
            }
            // 1. Apply filter strategy if configured
            elseif ($this->bot->filterStrategy) {
                Log::info('FilterAnalysisJob: Applying filter strategy', [
                    'bot_id' => $this->bot->id,
                    'filter_strategy_id' => $this->bot->filterStrategy->id,
                    'filter_strategy_name' => $this->bot->filterStrategy->name,
                    'filter_type' => $this->bot->filterStrategy->filter_type ?? 'technical',
                ]);

                // Skip filter evaluation for test and none types
                if (in_array($this->bot->filterStrategy->filter_type ?? 'technical', ['test', 'none'])) {
                    Log::info('FilterAnalysisJob: Filter type allows bypass, skipping evaluation', [
                        'bot_id' => $this->bot->id,
                        'filter_type' => $this->bot->filterStrategy->filter_type,
                    ]);
                } else {
                    $filterResult = $this->applyFilterStrategy();
                    
                    Log::info('FilterAnalysisJob: Filter strategy result', [
                        'bot_id' => $this->bot->id,
                        'passed' => $filterResult['pass'],
                        'reason' => $filterResult['reason'],
                    ]);
                    
                    if (!$filterResult['pass']) {
                        // Check if rejection is due to missing market data (common in testing)
                        $isDataError = strpos($filterResult['reason'] ?? '', 'Table') !== false 
                            || strpos($filterResult['reason'] ?? '', 'market data') !== false
                            || strpos($filterResult['reason'] ?? '', 'No market data') !== false;
                        
                        if ($isDataError) {
                            Log::warning('FilterAnalysisJob: Filter strategy failed due to missing market data, allowing trade to proceed', [
                                'bot_id' => $this->bot->id,
                                'filter_strategy_id' => $this->bot->filterStrategy->id,
                                'reason' => $filterResult['reason'],
                            ]);
                            // Continue processing (fail open for data errors)
                        } else {
                        Log::info('FilterAnalysisJob: Trading decision rejected by filter strategy', [
                            'bot_id' => $this->bot->id,
                            'filter_strategy_id' => $this->bot->filterStrategy->id,
                            'filter_strategy_name' => $this->bot->filterStrategy->name,
                            'reason' => $filterResult['reason'],
                        ]);
                        return; // Stop processing
                        }
                    }
                }
            } else {
                Log::info('FilterAnalysisJob: No filter strategy configured, skipping filter check', [
                    'bot_id' => $this->bot->id,
                ]);
            }

            // 2. Apply AI analysis if configured
            if ($this->bot->aiModelProfile && $this->bot->aiModelProfile->enabled) {
                Log::info('FilterAnalysisJob: Applying AI analysis', [
                    'bot_id' => $this->bot->id,
                    'ai_model_profile_id' => $this->bot->aiModelProfile->id,
                    'ai_model_profile_name' => $this->bot->aiModelProfile->name,
                ]);

                $aiResult = $this->applyAiAnalysis();
                
                Log::info('FilterAnalysisJob: AI analysis result', [
                    'bot_id' => $this->bot->id,
                    'execute' => $aiResult['execute'],
                    'reason' => $aiResult['reason'],
                ]);
                
                if (!$aiResult['execute']) {
                    Log::info('FilterAnalysisJob: Trading decision rejected by AI analysis', [
                        'bot_id' => $this->bot->id,
                        'ai_model_profile_id' => $this->bot->aiModelProfile->id,
                        'reason' => $aiResult['reason'],
                    ]);
                    return; // Stop processing
                }

                // AI may adjust the decision
                if (isset($aiResult['adjusted_decision'])) {
                    $this->decision = array_merge($this->decision, $aiResult['adjusted_decision']);
                    Log::info('FilterAnalysisJob: AI adjusted decision', [
                        'bot_id' => $this->bot->id,
                        'adjusted_decision' => $aiResult['adjusted_decision'],
                    ]);
                }
            } else {
                Log::info('FilterAnalysisJob: No AI model profile configured or disabled, skipping AI check', [
                    'bot_id' => $this->bot->id,
                    'has_ai_model' => !is_null($this->bot->aiModelProfile),
                    'ai_enabled' => $this->bot->aiModelProfile->enabled ?? false,
                ]);
            }

            // 3. If passed all filters, dispatch to Risk Management Worker
            Log::info('FilterAnalysisJob: All filters passed, dispatching to risk management', [
                'bot_id' => $this->bot->id,
                'direction' => $this->decision['direction'],
            ]);

            RiskManagementJob::dispatch($this->bot, $this->decision, $this->marketData);

            Log::info('FilterAnalysisJob: Trading decision passed filters, dispatched to risk management', [
                'bot_id' => $this->bot->id,
                'direction' => $this->decision['direction'],
            ]);

        } catch (\Exception $e) {
            Log::error('FilterAnalysisJob: Filter analysis job failed', [
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
            // Check if filter type should skip evaluation
            if (in_array($this->bot->filterStrategy->filter_type ?? 'technical', ['test', 'none'])) {
                Log::info('FilterAnalysisJob: Filter type allows automatic pass', [
                    'bot_id' => $this->bot->id,
                    'filter_type' => $this->bot->filterStrategy->filter_type,
                ]);
                
                return [
                    'pass' => true,
                    'reason' => 'Filter type ' . ($this->bot->filterStrategy->filter_type ?? 'none') . ' bypasses evaluation',
                ];
            }

            $evaluator = app(FilterStrategyEvaluator::class);
            
            // Get symbol and timeframe from market data
            $symbol = $this->marketData[0]['symbol'] ?? '';
            $timeframe = $this->marketData[0]['timeframe'] ?? '5m';
            
            Log::info('FilterAnalysisJob: Extracted symbol and timeframe from market data', [
                'bot_id' => $this->bot->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'market_data_count' => count($this->marketData),
            ]);
            
            // For trading bot flow, we need to evaluate filter strategy differently
            // since we don't have a Signal model. We'll evaluate based on market data directly.
            // Check if evaluator has a method that works with market data
            if (method_exists($evaluator, 'evaluateForTradingBot')) {
                $result = $evaluator->evaluateForTradingBot(
                    $this->bot->filterStrategy,
                    $symbol,
                    $timeframe,
                    $this->marketData,
                    $this->bot->exchangeConnection
                );
            } else {
                // Fallback: Create a minimal Signal model for evaluation
                // We need to get or create CurrencyPair and TimeFrame with proper IDs
                $currencyPair = \App\Models\CurrencyPair::where('name', $symbol)->first();
                if (!$currencyPair) {
                    // Try to find any active pair as fallback
                    $currencyPair = \App\Models\CurrencyPair::where('status', 1)->first();
                }
                if (!$currencyPair) {
                    // Last resort: create with minimal data (won't be saved)
                    $currencyPair = new \App\Models\CurrencyPair();
                    $currencyPair->id = 1; // Temporary ID
                    $currencyPair->name = $symbol;
                }
                
                // Normalize timeframe (5m -> M5, 1h -> H1, etc.)
                $normalizedTimeframe = strtoupper($timeframe);
                if (strlen($normalizedTimeframe) === 2 && is_numeric($normalizedTimeframe[0])) {
                    // 5m -> M5, 15m -> M15
                    $normalizedTimeframe = 'M' . $normalizedTimeframe[0];
                } elseif (strlen($normalizedTimeframe) === 3 && is_numeric(substr($normalizedTimeframe, 0, 2))) {
                    // 15m -> M15
                    $normalizedTimeframe = 'M' . substr($normalizedTimeframe, 0, 2);
                }
                
                $timeFrame = \App\Models\TimeFrame::where('name', $timeframe)
                    ->orWhere('name', $normalizedTimeframe)
                    ->first();
                if (!$timeFrame) {
                    // Try to find any active timeframe as fallback
                    $timeFrame = \App\Models\TimeFrame::where('status', 1)->first();
                }
                if (!$timeFrame) {
                    // Last resort: create with minimal data
                    $timeFrame = new \App\Models\TimeFrame();
                    $timeFrame->id = 1; // Temporary ID
                    $timeFrame->name = $timeframe;
                }
                
                // Create a minimal Signal model with proper relationships
                $signal = new \App\Models\Signal();
                $signal->currency_pair_id = $currencyPair->id;
                $signal->time_frame_id = $timeFrame->id;
                $signal->direction = $this->decision['direction'];
                $signal->open_price = $this->decision['entry_price'] ?? $this->marketData[0]['close'] ?? 0;
                // Set relationships so $signal->pair and $signal->time work
                $signal->setRelation('pair', $currencyPair);
                $signal->setRelation('time', $timeFrame);

            $result = $evaluator->evaluate($this->bot->filterStrategy, $signal, $this->bot->exchangeConnection);
            }

            return [
                'pass' => $result['pass'] ?? false,
                'reason' => $result['reason'] ?? 'Filter evaluation failed',
            ];
        } catch (\Exception $e) {
            Log::error('Filter strategy evaluation failed', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        // Check if AI connection is configured
        if (!$this->bot->ai_connection_id) {
            // No AI connection configured - create fail-open decision record
            $aiDecision = AiDecision::create([
                'signal_id' => null,
                'symbol' => $this->marketData[0]['symbol'] ?? '',
                'timeframe' => $this->marketData[0]['timeframe'] ?? '',
                'action' => 'HOLD',
                'wrong' => false,
                'confidence' => 0,
                'reasoning' => 'AI unavailable - no connection configured (fail-open)',
                'prompt_used' => null,
                'analysis_data' => json_encode([]),
                'model_used' => null,
                'ai_connection_id' => null,
                'created_at' => now(),
            ]);

            // Update decision with ai_decision_id
            $this->decision = array_merge($this->decision, ['ai_decision_id' => $aiDecision->id]);

            return ['execute' => true, 'reason' => 'No AI connection configured (fail-open)'];
        }

        try {
            // Build prompt for AI analysis
            $prompt = "Analyze this trading opportunity:\n" .
                "Symbol: " . ($this->marketData[0]['symbol'] ?? '') . "\n" .
                "Timeframe: " . ($this->marketData[0]['timeframe'] ?? '') . "\n" .
                "Direction: " . ($this->decision['direction'] ?? '') . "\n" .
                "Entry Price: " . ($this->marketData[0]['close'] ?? '') . "\n" .
                "\n" .
                "Should this trade be executed? Provide reasoning and confidence (0-100).";

            // Execute AI call via AiConnectionService
            $aiResult = app(AiConnectionService::class)->execute(
                connectionId: $this->bot->ai_connection_id,
                prompt: $prompt,
                options: [
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ],
                feature: 'trading-bot-ai-analysis'
            );

            // Parse AI response
            $aiResponse = $this->parseAiResponse($aiResult['response'] ?? '');

            // Create AiDecision record
            $aiDecision = AiDecision::create([
                'signal_id' => null,
                'symbol' => $this->marketData[0]['symbol'] ?? '',
                'timeframe' => $this->marketData[0]['timeframe'] ?? '',
                'action' => $aiResponse['action'] ?? 'HOLD',
                'wrong' => false,
                'confidence' => $aiResponse['confidence'] ?? 0,
                'reasoning' => $aiResponse['reasoning'] ?? 'AI unavailable - fail-open',
                'prompt_used' => hash('sha256', $prompt),
                'analysis_data' => json_encode($aiResponse['analysis'] ?? []),
                'model_used' => $aiResponse['model'] ?? null,
                'ai_connection_id' => $this->bot->ai_connection_id,
                'created_at' => now(),
            ]);

            // Update decision with ai_decision_id
            $this->decision = array_merge($this->decision, ['ai_decision_id' => $aiDecision->id]);

            // Determine if trade should execute based on AI decision
            $shouldExecute = in_array($aiResponse['action'] ?? 'HOLD', ['BUY', 'SELL']);

            return [
                'execute' => $shouldExecute,
                'reason' => $aiResponse['reasoning'] ?? 'AI analysis completed',
                'adjusted_decision' => $shouldExecute ? ['direction' => $this->decision['direction']] : null,
            ];
        } catch (\Exception $e) {
            Log::error('AI analysis failed', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Create fail-open AiDecision record
            $aiDecision = AiDecision::create([
                'signal_id' => null,
                'symbol' => $this->marketData[0]['symbol'] ?? '',
                'timeframe' => $this->marketData[0]['timeframe'] ?? '',
                'action' => 'HOLD',
                'wrong' => false,
                'confidence' => 0,
                'reasoning' => 'AI unavailable - fail-open',
                'prompt_used' => hash('sha256', 'AI error - ' . $e->getMessage()),
                'analysis_data' => json_encode(['error' => $e->getMessage()]),
                'model_used' => null,
                'ai_connection_id' => $this->bot->ai_connection_id,
                'created_at' => now(),
            ]);

            // Update decision with ai_decision_id
            $this->decision = array_merge($this->decision, ['ai_decision_id' => $aiDecision->id]);

            // On error, allow to proceed (fail open)
            return ['execute' => true, 'reason' => 'AI analysis error, allowing trade (fail-open)'];
        }
    }

    /**
     * Parse AI response to extract action, confidence, and reasoning
     */
    protected function parseAiResponse(string $response): array
    {
        $result = [
            'action' => 'HOLD',
            'confidence' => 0,
            'reasoning' => '',
            'analysis' => [],
            'model' => null,
        ];

        // Try to extract action (BUY/SELL/HOLD/NEUTRAL)
        $action = 'HOLD';
        if (preg_match('/(BUY|SELL|HOLD|NEUTRAL)/i', $response, $matches)) {
            $action = strtoupper($matches[1]);
        }
        $result['action'] = $action;

        // Try to extract confidence (0-100)
        if (preg_match('/confidence[:\s]*(\d+)/i', $response, $matches)) {
            $result['confidence'] = (int) min(100, max(0, $matches[1]));
        } elseif (preg_match('/(\d+)%\s*confidence/i', $response, $matches)) {
            $result['confidence'] = (int) min(100, max(0, $matches[1]));
        }

        // Full response as reasoning
        $result['reasoning'] = $response;

        // Store full analysis
        $result['analysis'] = [
            'raw_response' => $response,
        ];

        return $result;
    }
}
