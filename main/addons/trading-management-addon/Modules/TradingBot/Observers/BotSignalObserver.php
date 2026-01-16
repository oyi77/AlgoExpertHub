<?php

namespace Addons\TradingManagement\Modules\TradingBot\Observers;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\BotExecutionService;
use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiDecision;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * BotSignalObserver
 * 
 * Handles signal execution through trading bots
 * Works alongside SignalObserver (connection-based execution)
 */
class BotSignalObserver
{
    protected BotExecutionService $botExecutionService;
    protected AiConnectionService $aiConnectionService;

    public function __construct(
        BotExecutionService $botExecutionService,
        AiConnectionService $aiConnectionService
    ) {
        $this->botExecutionService = $botExecutionService;
        $this->aiConnectionService = $aiConnectionService;
    }

    /**
     * Handle the Signal "updated" event.
     */
    public function updated(Signal $signal): void
    {
        // Check if signal was just published
        if ($signal->is_published && $signal->wasChanged('is_published')) {
            $this->handleSignalPublished($signal);
        }
    }

    /**
     * Handle signal published - execute through active bots
     */
    protected function handleSignalPublished(Signal $signal): void
    {
        try {
            // Get all active bots
            $bots = $this->botExecutionService->getActiveBotsForSignal($signal);

            foreach ($bots as $bot) {
                // Evaluate bot's filter strategy
                $filterResult = $this->botExecutionService->evaluateBotFilter($signal, $bot);
                
                if (!$filterResult['pass']) {
                    Log::info('Bot filter rejected signal', [
                        'bot_id' => $bot->id,
                        'bot_name' => $bot->name,
                        'signal_id' => $signal->id,
                        'reason' => $filterResult['reason'],
                    ]);
                    continue;
                }

                // Initialize ai decision ID (will be set if AI analysis runs)
                $aiDecisionId = null;

                if ($bot->aiModelProfile && $bot->aiModelProfile->enabled) {
                    try {
                        // Build AI prompt for signal analysis
                        $prompt = $this->buildAiPrompt($signal);

                        // Get AI connection from model profile
                        if ($bot->aiModelProfile->ai_connection_id) {
                            $aiResponse = $this->aiConnectionService->execute(
                                $bot->aiModelProfile->ai_connection_id,
                                $prompt,
                                [
                                    'temperature' => 0.2,
                                    'max_tokens' => 500,
                                ],
                                'bot_signal_analysis'
                            );

                            if ($aiResponse['success'] && !empty($aiResponse['response'])) {
                                // Parse AI response to extract action and confidence
                                $parsedAiResponse = $this->parseAiResponse($aiResponse['response']);

                                // Create AiDecision record
                                $aiDecision = AiDecision::create([
                                    'signal_id' => $signal->id,
                                    'symbol' => $signal->pair->name,
                                    'timeframe' => $signal->time->name,
                                    'action' => strtoupper($parsedAiResponse['action'] ?? 'HOLD'),
                                    'confidence' => (int) ($parsedAiResponse['confidence'] ?? 0),
                                    'reasoning' => $parsedAiResponse['reasoning'] ?? 'No reasoning provided',
                                    'prompt_used' => hash('sha256', $prompt),
                                    'analysis_data' => $parsedAiResponse,
                                    'ai_connection_id' => $bot->aiModelProfile->ai_connection_id,
                                    'model_used' => $parsedAiResponse['model'] ?? null,
                                ]);

                                $aiDecisionId = $aiDecision->id;

                                Log::info('AI decision recorded', [
                                    'bot_id' => $bot->id,
                                    'signal_id' => $signal->id,
                                    'ai_decision_id' => $aiDecisionId,
                                    'action' => $aiDecision->action,
                                    'confidence' => $aiDecision->confidence,
                                ]);

                                // Fail-open: AI can veto (HOLD) but not block execution pipeline
                                // Only continue to next signal if AI explicitly says HOLD with high confidence
                                if ($aiDecision->action === 'HOLD' && $aiDecision->confidence > 70) {
                                    Log::info('AI decision rejected signal (high confidence HOLD)', [
                                        'bot_id' => $bot->id,
                                        'signal_id' => $signal->id,
                                        'ai_decision_id' => $aiDecisionId,
                                        'reason' => 'AI said HOLD with ' . $aiDecision->confidence . '% confidence',
                                    ]);
                                    continue;
                                }
                            }
                        } else {
                            // No AI connection configured - create fail-open decision
                            $aiDecision = AiDecision::create([
                                'signal_id' => $signal->id,
                                'symbol' => $signal->pair->name,
                                'timeframe' => $signal->time->name,
                                'action' => 'HOLD',
                                'confidence' => 0,
                                'reasoning' => 'AI unavailable - no connection configured - fail-open',
                                'prompt_used' => hash('sha256', $prompt),
                                'analysis_data' => [],
                                'ai_connection_id' => null,
                                'model_used' => null,
                            ]);

                            $aiDecisionId = $aiDecision->id;

                            Log::warning('AI decision created - no connection configured (fail-open)', [
                                'bot_id' => $bot->id,
                                'signal_id' => $signal->id,
                                'ai_decision_id' => $aiDecisionId,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        // Fail-open: Create AiDecision with failure info and continue execution
                        try {
                            $prompt = $this->buildAiPrompt($signal);
                            $aiDecision = AiDecision::create([
                                'signal_id' => $signal->id,
                                'symbol' => $signal->pair->name,
                                'timeframe' => $signal->time->name,
                                'action' => 'HOLD',
                                'confidence' => 0,
                                'reasoning' => 'AI unavailable - fail-open: ' . $e->getMessage(),
                                'prompt_used' => hash('sha256', $prompt ?? ''),
                                'analysis_data' => [],
                                'ai_connection_id' => $bot->aiModelProfile->ai_connection_id ?? null,
                                'model_used' => null,
                            ]);

                            $aiDecisionId = $aiDecision->id;

                            Log::warning('AI decision created on error (fail-open)', [
                                'bot_id' => $bot->id,
                                'signal_id' => $signal->id,
                                'ai_decision_id' => $aiDecisionId,
                                'error' => $e->getMessage(),
                            ]);
                        } catch (\Exception $dbError) {
                            // If even creating the decision fails, log and continue
                            Log::error('Failed to create fail-open AI decision', [
                                'bot_id' => $bot->id,
                                'signal_id' => $signal->id,
                                'original_error' => $e->getMessage(),
                                'db_error' => $dbError->getMessage(),
                            ]);
                        }
                    }
                }

                // Use database transaction with lock to prevent race condition
                // Check if already executed this signal (with row lock)
                $shouldExecute = DB::transaction(function () use ($bot, $signal) {
                    $existingPosition = DB::table('trading_bot_positions')
                        ->where('bot_id', $bot->id)
                        ->where('signal_id', $signal->id)
                        ->where('status', 'open')
                        ->lockForUpdate()
                        ->first();

                    if ($existingPosition) {
                        Log::info('Bot already executed this signal', [
                            'bot_id' => $bot->id,
                            'signal_id' => $signal->id,
                        ]);
                        return false; // Already executed
                    }

                    // Create pending position record to claim this signal
                    DB::table('trading_bot_positions')->insert([
                        'bot_id' => $bot->id,
                        'signal_id' => $signal->id,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return true; // Can execute
                });

                if (!$shouldExecute) {
                    continue; // Skip this signal
                }

                // Determine direction
                $direction = in_array($signal->direction, ['buy', 'long']) ? 'buy' : 'sell';

                // Calculate position size from trading preset
                $preset = $bot->tradingPreset;
                $quantity = $this->calculatePositionSize($preset, $signal);

                // Validate signal data before execution
                if (!$signal->relationLoaded('pair')) {
                    $signal->load('pair');
                }

                if (!$signal->pair || !$signal->pair->name) {
                    Log::error('Invalid signal: missing pair data', [
                        'signal_id' => $signal->id,
                        'bot_id' => $bot->id,
                    ]);
                    continue;
                }

                if (!$signal->open_price || $signal->open_price <= 0) {
                    Log::error('Invalid signal: invalid open_price', [
                        'signal_id' => $signal->id,
                        'open_price' => $signal->open_price,
                        'bot_id' => $bot->id,
                    ]);
                    continue;
                }

                if (!$bot->exchange_connection_id) {
                    Log::error('Bot has no exchange connection', [
                        'bot_id' => $bot->id,
                        'signal_id' => $signal->id,
                    ]);
                    continue;
                }

                // Prepare execution data for new ExecutionJob
                $executionData = [
                    'connection_id' => $bot->exchange_connection_id,
                    'bot_id' => $bot->id,
                    'user_id' => $bot->user_id,  // REQUIRED for paper trading
                    'signal_id' => $signal->id,
                    'ai_decision_id' => $aiDecisionId,  // AI decision reference
                    'symbol' => $signal->pair->name,
                    'timeframe' => $signal->time->name,  // REQUIRED for MarketStatusChecker
                    'direction' => $direction,
                    'quantity' => $quantity,
                    'entry_price' => $signal->open_price,
                    'stop_loss' => $signal->sl ?? null,
                    'take_profit' => $signal->tp ?? null,
                    'is_paper_trading' => $bot->is_paper_trading,  // REQUIRED
                    'created_at' => now()->toISOString(),
                ];

                // Dispatch ExecutionJob (will update position from pending to open)
                ExecutionJob::dispatch($executionData);

                Log::info('Trading bot signal execution dispatched', [
                    'bot_id' => $bot->id,
                    'signal_id' => $signal->id,
                    'direction' => $direction,
                    'quantity' => $quantity,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Bot signal observer error", [
                'error' => $e->getMessage(),
                'signal_id' => $signal->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Calculate position size from trading preset
     * 
     * @param mixed $preset Trading preset or null
     * @param Signal $signal
     * @return float
     */
    protected function calculatePositionSize($preset, Signal $signal): float
    {
        if (!$preset) {
            return 0.01; // Default minimum
        }

        // Get position sizing strategy from preset
        $strategy = $preset->position_sizing_strategy ?? 'fixed';
        $value = $preset->position_sizing_value ?? 0.01;

        switch ($strategy) {
            case 'fixed':
                return (float) $value;
            
            case 'percentage':
                // Would need account balance from exchange
                // For now, use fixed fallback
                return 0.01;
            
            case 'fixed_amount':
                // Fixed dollar amount
                $entryPrice = $signal->open_price ?? 1;
                return $entryPrice > 0 ? ($value / $entryPrice) : 0.01;
            
                default:
                    return 0.01;
        }
    }

    /**
     * Build AI prompt for signal analysis
     *
     * @param Signal $signal
     * @return string
     */
    protected function buildAiPrompt(Signal $signal): string
    {
        $direction = $signal->direction ?? 'unknown';
        $openPrice = $signal->open_price ?? 'N/A';
        $stopLoss = $signal->sl ?? 'N/A';
        $takeProfit = $signal->tp ?? 'N/A';

        $prompt = <<<EOT
You are an expert trading analyst. Analyze the following trading signal and provide your recommendation.

Signal Details:
- Symbol: {$signal->pair->name}
- Timeframe: {$signal->time->name}
- Direction: {$direction}
- Entry Price: {$openPrice}
- Stop Loss: {$stopLoss}
- Take Profit: {$takeProfit}

Analyze this signal and determine the trading action.
Consider factors like:
- Risk/reward ratio
- Market conditions
- Price action context

Provide your decision as valid JSON in this format:
{
    "action": "BUY|SELL|HOLD|NEUTRAL",
    "confidence": 85,
    "reasoning": "Your analysis explanation here",
    "model": "gpt-4"
}

Action should be one of: BUY, SELL, HOLD, NEUTRAL
Confidence should be a number between 0 and 100
Reasoning should be brief (1-2 sentences)
EOT;

        return $prompt;
    }

    /**
     * Parse AI response to extract structured data
     *
     * @param string $response
     * @return array
     */
    protected function parseAiResponse(string $response): array
    {
        try {
            // Remove markdown code blocks if present
            $jsonStr = preg_replace('/```json\s*/', '', $response);
            $jsonStr = preg_replace('/```\s*$/', '', $jsonStr);
            $jsonStr = trim($jsonStr);

            $data = json_decode($jsonStr, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to decode AI JSON response', [
                    'error' => json_last_error_msg(),
                    'response' => substr($response, 0, 200),
                ]);
                return [
                    'action' => 'HOLD',
                    'confidence' => 0,
                    'reasoning' => 'Failed to parse AI response',
                    'model' => null,
                ];
            }

            return [
                'action' => $data['action'] ?? 'HOLD',
                'confidence' => (int) ($data['confidence'] ?? 0),
                'reasoning' => $data['reasoning'] ?? 'No reasoning provided',
                'model' => $data['model'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Error parsing AI response', [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 200),
            ]);

            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => 'Error parsing AI response: ' . $e->getMessage(),
                'model' => null,
            ];
        }
    }
}
