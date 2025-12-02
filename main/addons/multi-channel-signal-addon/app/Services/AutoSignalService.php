<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App\DTOs\ParsedSignalData;
use Addons\MultiChannelSignalAddon\App\Models\ChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use App\Models\CurrencyPair;
use App\Models\Market;
use App\Models\Signal;
use App\Models\TimeFrame;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoSignalService
{
    /**
     * Create a signal from parsed message data.
     *
     * @param ParsedSignalData $parsedData
     * @param ChannelSource $channelSource
     * @param ChannelMessage $channelMessage
     * @return Signal|null
     */
    public function createFromParsedData(
        ParsedSignalData $parsedData,
        ChannelSource $channelSource,
        ChannelMessage $channelMessage
    ): ?Signal {
        if (!$parsedData->isValid()) {
            Log::warning("Invalid parsed data, missing fields: " . implode(', ', $parsedData->getMissingFields()));
            return null;
        }

        try {
            DB::beginTransaction();

            // Map currency pair
            $currencyPair = $this->findOrCreateCurrencyPair($parsedData->currency_pair);
            if (!$currencyPair) {
                throw new \Exception("Could not find or create currency pair: {$parsedData->currency_pair}");
            }

            // Map timeframe
            $timeframe = $this->findOrCreateTimeframe($parsedData->timeframe ?? $channelSource->default_timeframe_id);
            if (!$timeframe) {
                throw new \Exception("Could not find or create timeframe");
            }

            // Map market
            $market = $this->findOrCreateMarket($parsedData->market ?? $channelSource->default_market_id);
            if (!$market) {
                throw new \Exception("Could not find or create market");
            }

            // Handle percentage-based TP/SL
            $openPrice = $parsedData->open_price;
            $sl = $parsedData->sl;
            $tp = $parsedData->tp;

            // If no entry price is specified, it means market entry at current price
            // Fetch current market price and use it as entry price
            if ($parsedData->needs_price_fetch || ($openPrice == 0 && ($parsedData->tp_percentage || $parsedData->sl_percentage !== null))) {
                Log::info("No entry price specified for {$parsedData->currency_pair}, fetching current market price for market entry");
                
                // Fetch current market price
                $currentPrice = $this->fetchCurrentPrice($parsedData->currency_pair);
                
                if ($currentPrice > 0) {
                    $openPrice = $currentPrice;
                    $parsedData->open_price = $currentPrice;
                    Log::info("Using current market price as entry: {$currentPrice} for {$parsedData->currency_pair}");
                    
                    // Calculate TP/SL from percentages based on current market price
                    if ($parsedData->tp_percentage || $parsedData->sl_percentage !== null) {
                        $parsedData->calculatePricesFromPercentages($currentPrice);
                        $sl = $parsedData->sl ?? 0;
                        $tp = $parsedData->tp ?? 0;
                        Log::info("Calculated TP/SL from percentages - TP: {$tp}, SL: {$sl}");
                    }
                } else {
                    // If we can't fetch price, mark for manual review
                    throw new \Exception("Cannot fetch current market price for {$parsedData->currency_pair}. Please ensure price API is configured or set entry price manually.");
                }
            } elseif ($parsedData->tp_percentage || $parsedData->sl_percentage !== null) {
                // We have percentages and explicit entry price, calculate TP/SL
                if ($openPrice > 0) {
                    $parsedData->calculatePricesFromPercentages($openPrice);
                    $sl = $parsedData->sl ?? 0;
                    $tp = $parsedData->tp ?? 0;
                    Log::info("Calculated TP/SL from percentages with explicit entry price - TP: {$tp}, SL: {$sl}");
                }
            }

            // Validate prices
            if (!$this->validatePrice($openPrice) || $openPrice <= 0) {
                throw new \Exception("Invalid open price: {$openPrice}");
            }

            if ($sl && !$this->validatePrice($sl)) {
                throw new \Exception("Invalid stop loss: {$sl}");
            }

            if ($tp && !$this->validatePrice($tp)) {
                throw new \Exception("Invalid take profit: {$tp}");
            }

            // Create signal
            $signal = Signal::create([
                'title' => $parsedData->title ?? "Signal: {$parsedData->currency_pair} {$parsedData->direction}",
                'currency_pair_id' => $currencyPair->id,
                'time_frame_id' => $timeframe->id,
                'market_id' => $market->id,
                'open_price' => $openPrice,
                'sl' => $sl ?? 0,
                'tp' => $tp ?? 0,
                'direction' => $parsedData->direction,
                'description' => $parsedData->description,
                'is_published' => 0, // Draft
                'auto_created' => 1,
                'channel_source_id' => $channelSource->id,
                'message_hash' => $channelMessage->message_hash,
                'status' => 1,
                'published_date' => now(),
            ]);

            // Assign to default plan if set
            if ($channelSource->default_plan_id) {
                $signal->plans()->attach($channelSource->default_plan_id);
            }

            // Update channel message
            $channelMessage->update([
                'signal_id' => $signal->id,
                'parsed_data' => $parsedData->toArray(),
                'confidence_score' => $parsedData->confidence,
            ]);

            DB::commit();

            Log::info("Auto-created signal {$signal->id} from channel message {$channelMessage->id}");

            // Track analytics
            $analyticsService = app(\Addons\MultiChannelSignalAddon\App\Services\SignalAnalyticsService::class);
            $analyticsService->trackSignal($signal, $channelSource->id, [
                'pattern_used' => $parsedData->pattern_used ?? null,
                'pattern_id' => $parsedData->pattern_id ?? null,
                'confidence' => $parsedData->confidence,
                'message_id' => $channelMessage->id,
            ]);

            // Filter Strategy Evaluation (Sprint 1: Filter Strategy)
            $filterResult = $this->evaluateFilterStrategy($signal, $channelSource);
            
            // Store filter result in channel message metadata
            if ($filterResult) {
                $channelMessage->update([
                    'parsed_data' => array_merge(
                        $channelMessage->parsed_data ?? [],
                        ['filter_evaluation' => $filterResult]
                    ),
                ]);
                
                // If filter failed, do not auto-publish
                if (!$filterResult['pass']) {
                    Log::info("Signal {$signal->id} rejected by filter strategy", [
                        'reason' => $filterResult['reason'],
                        'strategy_id' => $filterResult['strategy_id'] ?? null,
                    ]);
                    
                    // Mark signal as rejected (optional: add status field or metadata)
                    // For now, just don't auto-publish
                    return $signal;
                }
            }

            // AI Market Confirmation (Sprint 2: AI Confirmation)
            $aiResult = $this->evaluateAiConfirmation($signal, $channelSource);
            
            // Store AI result in channel message metadata
            if ($aiResult) {
                $channelMessage->update([
                    'parsed_data' => array_merge(
                        $channelMessage->parsed_data ?? [],
                        ['ai_evaluation' => $aiResult]
                    ),
                ]);
                
                // If AI decision is to reject, do not auto-publish
                if (!$aiResult['execute']) {
                    Log::info("Signal {$signal->id} rejected by AI confirmation", [
                        'reason' => $aiResult['reason'],
                        'profile_id' => $aiResult['profile_id'] ?? null,
                    ]);
                    
                    return $signal;
                }
            }

            // If admin-owned channel, distribute to recipients
            if ($channelSource->isAdminOwned()) {
                $this->distributeToRecipients($signal, $channelSource);
            } else {
                // Auto-publish if confidence >= threshold (for user channels)
                if ($parsedData->confidence >= $channelSource->auto_publish_confidence_threshold) {
                    $this->autoPublish($signal);
                }
            }

            return $signal;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create signal from parsed data: " . $e->getMessage(), [
                'exception' => $e,
                'parsed_data' => $parsedData->toArray(),
                'channel_source_id' => $channelSource->id,
            ]);

            return null;
        }
    }

    /**
     * Find or create currency pair.
     *
     * @param string|null $pairName
     * @return CurrencyPair|null
     */
    protected function findOrCreateCurrencyPair(?string $pairName): ?CurrencyPair
    {
        if (!$pairName) {
            return CurrencyPair::whereStatus(true)->first();
        }

        // Normalize pair name
        $pairName = strtoupper(trim($pairName));
        $pairName = str_replace('-', '/', $pairName);

        // Try to find existing
        $pair = CurrencyPair::where('name', $pairName)
            ->whereStatus(true)
            ->first();

        if ($pair) {
            return $pair;
        }

        // Try to create (if enabled in config)
        // For now, return first active pair as fallback
        return CurrencyPair::whereStatus(true)->first();
    }

    /**
     * Find or create timeframe.
     *
     * @param mixed $timeframe
     * @return TimeFrame|null
     */
    protected function findOrCreateTimeframe($timeframe): ?TimeFrame
    {
        // If it's an ID
        if (is_numeric($timeframe)) {
            return TimeFrame::where('id', $timeframe)->whereStatus(true)->first();
        }

        // If it's a string, try to match
        if (is_string($timeframe)) {
            $timeframe = strtoupper(trim($timeframe));
            
            // Map common timeframe formats
            $mapping = [
                'M1' => 'M1', '1MIN' => 'M1', '1M' => 'M1',
                'M5' => 'M5', '5MIN' => 'M5', '5M' => 'M5',
                'M15' => 'M15', '15MIN' => 'M15', '15M' => 'M15',
                'M30' => 'M30', '30MIN' => 'M30', '30M' => 'M30',
                'H1' => 'H1', '1H' => 'H1', '1HOUR' => 'H1',
                'H4' => 'H4', '4H' => 'H4', '4HOUR' => 'H4',
                'D1' => 'D1', '1D' => 'D1', '1DAY' => 'D1',
                'W1' => 'W1', '1W' => 'W1', '1WEEK' => 'W1',
            ];

            $normalized = $mapping[$timeframe] ?? $timeframe;
            
            $timeframeModel = TimeFrame::where('name', $normalized)
                ->whereStatus(true)
                ->first();

            if ($timeframeModel) {
                return $timeframeModel;
            }
        }

        // Fallback to first active timeframe
        return TimeFrame::whereStatus(true)->first();
    }

    /**
     * Find or create market.
     *
     * @param mixed $market
     * @return Market|null
     */
    protected function findOrCreateMarket($market): ?Market
    {
        // If it's an ID
        if (is_numeric($market)) {
            return Market::where('id', $market)->whereStatus(true)->first();
        }

        // If it's a string, try to find
        if (is_string($market)) {
            $marketModel = Market::where('name', $market)
                ->whereStatus(true)
                ->first();

            if ($marketModel) {
                return $marketModel;
            }
        }

        // Fallback to first active market
        return Market::whereStatus(true)->first();
    }

    /**
     * Validate price.
     *
     * @param float|null $price
     * @return bool
     */
    protected function validatePrice(?float $price): bool
    {
        if ($price === null) {
            return false;
        }

        // Price must be positive
        if ($price <= 0) {
            return false;
        }

        // Price must be reasonable (not too high)
        // This is configurable, but for now check if < 1,000,000
        if ($price > 1000000) {
            return false;
        }

        return true;
    }

    /**
     * Fetch current market price for a symbol.
     * Uses CryptoCompare API for cryptocurrency prices.
     * For indices/stocks (like USA100), you may need to implement MT5 or other APIs.
     *
     * @param string $symbol
     * @return float
     */
    protected function fetchCurrentPrice(string $symbol): float
    {
        try {
            $config = \App\Helpers\Helper\Helper::config();
            $apiKey = $config->crypto_api ?? '';
            
            if (empty($apiKey)) {
                Log::warning("Crypto API key not configured. Cannot fetch price for symbol: {$symbol}");
                return 0;
            }

            // Normalize symbol (remove common prefixes/suffixes)
            $normalizedSymbol = strtoupper(trim($symbol));
            
            // Handle indices/stocks that might not be in CryptoCompare
            // For now, try CryptoCompare first, then fallback to manual mapping
            $symbolMapping = [
                'USA100' => 'US100', // Try US100 as alternative
                'US100' => 'US100',
                'SPX500' => 'SPX',
                'UK100' => 'UK100',
            ];
            
            $apiSymbol = $symbolMapping[$normalizedSymbol] ?? $normalizedSymbol;
            
            // Try to fetch from CryptoCompare
            $url = "https://min-api.cryptocompare.com/data/price?fsym={$apiSymbol}&tsyms=USD&api_key={$apiKey}";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET',
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                Log::warning("Failed to fetch price from CryptoCompare for symbol: {$symbol} (API symbol: {$apiSymbol})");
                return 0;
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Invalid JSON response from CryptoCompare for symbol: {$symbol}");
                return 0;
            }
            
            // Check for API errors
            if (isset($data['Response']) && $data['Response'] === 'Error') {
                Log::warning("CryptoCompare API error for symbol {$symbol}: " . ($data['Message'] ?? 'Unknown error'));
                
                // For indices like USA100, CryptoCompare might not have them
                // Return 0 to indicate price fetch failed - signal will need manual entry price
                return 0;
            }
            
            $price = $data['USD'] ?? 0;
            
            if ($price > 0) {
                Log::info("Fetched price for {$symbol}: {$price} USD");
                return (float) $price;
            }
            
            Log::warning("Price not found for symbol: {$symbol} (API symbol: {$apiSymbol})");
            return 0;
            
        } catch (\Exception $e) {
            Log::error("Exception while fetching price for symbol {$symbol}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Distribute signal from admin channel to assigned recipients.
     *
     * @param Signal $signal
     * @param ChannelSource $channelSource
     * @return void
     */
    protected function distributeToRecipients(Signal $signal, ChannelSource $channelSource): void
    {
        try {
            $assignmentService = app(\Addons\MultiChannelSignalAddon\App\Services\ChannelAssignmentService::class);
            $recipients = $assignmentService->getRecipients($channelSource);

            if ($recipients->isEmpty()) {
                Log::warning("No recipients found for admin channel {$channelSource->id}");
                return;
            }

            // Dispatch job for async distribution
            \Addons\MultiChannelSignalAddon\App\Jobs\DistributeAdminSignalJob::dispatch($signal, $channelSource, $recipients);

            Log::info("Dispatched distribution job for signal {$signal->id} to " . $recipients->count() . " recipients");
        } catch (\Exception $e) {
            Log::error("Failed to distribute signal {$signal->id}: " . $e->getMessage(), [
                'exception' => $e,
                'channel_source_id' => $channelSource->id,
            ]);
        }
    }

    /**
     * Evaluate filter strategy for signal (Sprint 1: Filter Strategy)
     * 
     * @param Signal $signal
     * @param ChannelSource $channelSource
     * @return array|null ['pass' => bool, 'reason' => string, 'strategy_id' => int|null, 'indicators' => array]
     */
    protected function evaluateFilterStrategy(Signal $signal, ChannelSource $channelSource): ?array
    {
        try {
            // Check if Filter Strategy addon is available
            if (!class_exists(\Addons\FilterStrategyAddon\App\Services\FilterStrategyResolverService::class)) {
                return null; // Addon not available, skip filter
            }

            // Resolve filter strategy
            $resolver = app(\Addons\FilterStrategyAddon\App\Services\FilterStrategyResolverService::class);
            
            // Try to get connection from channel source owner (if user channel)
            $connection = null;
            $user = null;
            if (!$channelSource->isAdminOwned() && $channelSource->user_id) {
                $user = $channelSource->user;
                // Try to find active connection for this user
                if (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
                    $connection = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::where('user_id', $user->id)
                        ->where('is_active', true)
                        ->first();
                }
            }

            $filterStrategy = $resolver->resolveForSignal($signal, $connection, $user);

            // If no filter strategy configured, skip evaluation
            if (!$filterStrategy) {
                return null;
            }

            // Evaluate filter strategy
            $evaluator = app(\Addons\FilterStrategyAddon\App\Services\FilterStrategyEvaluator::class);
            $result = $evaluator->evaluate($filterStrategy, $signal, $connection);

            return [
                'pass' => $result['pass'],
                'reason' => $result['reason'],
                'strategy_id' => $filterStrategy->id,
                'strategy_name' => $filterStrategy->name,
                'indicators' => $result['indicators'] ?? [],
                'evaluated_at' => now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error("AutoSignalService: Filter strategy evaluation failed", [
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
            
            // Fail-safe: if evaluation fails, reject signal (safer than allowing through)
            return [
                'pass' => false,
                'reason' => 'Filter evaluation error: ' . $e->getMessage(),
                'strategy_id' => null,
                'indicators' => [],
                'evaluated_at' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Evaluate AI confirmation for signal (Sprint 2: AI Confirmation)
     * 
     * @param Signal $signal
     * @param ChannelSource $channelSource
     * @return array|null ['execute' => bool, 'adjusted_risk_factor' => float, 'reason' => string, 'profile_id' => int|null, 'ai_result' => array]
     */
    protected function evaluateAiConfirmation(Signal $signal, ChannelSource $channelSource): ?array
    {
        try {
            // Check if AI Trading addon is available
            if (!class_exists(\Addons\AiTradingAddon\App\Services\MarketAnalysisAiService::class)) {
                return null; // Addon not available, skip AI
            }

            // Resolve AI Model Profile from preset
            $resolver = app(\Addons\FilterStrategyAddon\App\Services\FilterStrategyResolverService::class);
            
            // Try to get connection from channel source owner (if user channel)
            $connection = null;
            $user = null;
            if (!$channelSource->isAdminOwned() && $channelSource->user_id) {
                $user = $channelSource->user;
                // Try to find active connection for this user
                if (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
                    $connection = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::where('user_id', $user->id)
                        ->where('is_active', true)
                        ->first();
                }
            }

            // Resolve preset to get AI profile
            $presetResolver = app(\Addons\TradingPresetAddon\App\Services\PresetResolverService::class);
            $preset = $presetResolver->resolveForSignal($connection, $user, $signal);

            if (!$preset || !$preset->ai_model_profile_id) {
                // No AI profile configured, skip
                return null;
            }

            $aiProfile = $preset->aiModelProfile;
            if (!$aiProfile || !$aiProfile->enabled) {
                return null;
            }

            // Check if AI confirmation is enabled
            if ($preset->ai_confirmation_mode === 'NONE') {
                return null; // AI confirmation disabled
            }

            // Call AI service
            $aiService = app(\Addons\AiTradingAddon\App\Services\MarketAnalysisAiService::class);
            $aiResult = $aiService->confirmSignal($signal, $aiProfile, $connection);

            if (!$aiResult) {
                // AI analysis failed, fail-safe: reject
                return [
                    'execute' => false,
                    'adjusted_risk_factor' => 0.0,
                    'reason' => 'AI analysis failed',
                    'profile_id' => $aiProfile->id,
                    'ai_result' => null,
                ];
            }

            // Make decision using AiDecisionEngine
            $decisionEngine = app(\Addons\AiTradingAddon\App\Services\AiDecisionEngine::class);
            $decision = $decisionEngine->makeDecision($aiResult, $preset);

            return [
                'execute' => $decision['execute'],
                'adjusted_risk_factor' => $decision['adjusted_risk_factor'],
                'reason' => $decision['reason'],
                'profile_id' => $aiProfile->id,
                'ai_result' => $aiResult,
            ];

        } catch (\Exception $e) {
            Log::error("AutoSignalService: AI confirmation evaluation failed", [
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
            
            // Fail-safe: if evaluation fails, reject signal
            return [
                'execute' => false,
                'adjusted_risk_factor' => 0.0,
                'reason' => 'AI evaluation error: ' . $e->getMessage(),
                'profile_id' => null,
                'ai_result' => null,
            ];
        }
    }

    /**
     * Auto-publish signal if confidence is high enough.
     *
     * @param Signal $signal
     * @return void
     */
    protected function autoPublish(Signal $signal): void
    {
        try {
            $signalService = app(\App\Services\SignalService::class);
            $signalService->sent($signal->id);
            
            $signal->update([
                'published_date' => now(),
            ]);

            Log::info("Auto-published signal {$signal->id}");
        } catch (\Exception $e) {
            Log::error("Failed to auto-publish signal {$signal->id}: " . $e->getMessage());
        }
    }
}
