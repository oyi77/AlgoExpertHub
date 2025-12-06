<?php

namespace Addons\AiTradingAddon\App\Services;

use Addons\AiTradingAddon\App\Models\AiModelProfile;
use Addons\FilterStrategyAddon\App\Services\MarketDataService;
use Addons\FilterStrategyAddon\App\Services\IndicatorService;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

class MarketAnalysisAiService
{
    protected AiTradingProviderFactory $providerFactory;
    protected MarketDataService $marketDataService;
    protected IndicatorService $indicatorService;

    public function __construct(
        AiTradingProviderFactory $providerFactory,
        MarketDataService $marketDataService,
        IndicatorService $indicatorService
    ) {
        $this->providerFactory = $providerFactory;
        $this->marketDataService = $marketDataService;
        $this->indicatorService = $indicatorService;
    }

    /**
     * Analyze signal for confirmation (CONFIRM mode).
     * 
     * @param Signal $signal
     * @param AiModelProfile $profile
     * @param mixed $connection Optional execution connection
     * @return array|null ['alignment' => float, 'safety_score' => float, 'decision' => string, 'reasoning' => string, 'confidence' => float]
     */
    public function confirmSignal(Signal $signal, AiModelProfile $profile, $connection = null): ?array
    {
        try {
            if ($profile->mode !== 'CONFIRM') {
                Log::warning("AI Model Profile {$profile->id} is not in CONFIRM mode");
                return null;
            }

            // Get provider
            $provider = $this->providerFactory->createFromProfile($profile);
            if (!$provider) {
                Log::error("Failed to create AI provider for profile {$profile->id}");
                return null;
            }

            // Fetch market data
            $symbol = $signal->pair->name ?? null;
            $timeframe = $signal->time->name ?? '1h';

            if (!$symbol) {
                Log::error("Signal {$signal->id} missing currency pair");
                return null;
            }

            $candles = $this->marketDataService->getOhlcv($symbol, $timeframe, 200, $connection);
            if (empty($candles)) {
                Log::warning("No market data available for {$symbol} {$timeframe}");
                return null;
            }

            // Calculate basic indicators (for context)
            $indicators = $this->calculateBasicIndicators($candles);

            // Prepare market data
            $marketData = [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'candles' => array_slice($candles, -50), // Last 50 candles
                'indicators' => $indicators,
                'latest_price' => $candles[count($candles) - 1]['close'] ?? null,
            ];

            // Prepare signal data
            $signalData = [
                'pair' => $symbol,
                'direction' => $signal->direction,
                'entry' => $signal->open_price,
                'sl' => $signal->sl,
                'tp' => $signal->tp,
                'timeframe' => $timeframe,
            ];

            // Call AI provider
            $result = $provider->analyzeForConfirmation($marketData, $signalData, $profile);

            if (!$result) {
                Log::warning("AI confirmation returned null for signal {$signal->id}");
                return null;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("MarketAnalysisAiService: Signal confirmation failed", [
                'signal_id' => $signal->id,
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Analyze market for trade opportunities (SCAN mode).
     * 
     * @param string $symbol
     * @param string $timeframe
     * @param AiModelProfile $profile
     * @param mixed $connection Optional execution connection
     * @return array|null ['should_open_trade' => bool, 'direction' => string, 'entry' => float, 'sl' => float, 'tp' => float, 'confidence' => float, 'reasoning' => string]
     */
    public function scanMarket(string $symbol, string $timeframe, AiModelProfile $profile, $connection = null): ?array
    {
        try {
            if ($profile->mode !== 'SCAN') {
                Log::warning("AI Model Profile {$profile->id} is not in SCAN mode");
                return null;
            }

            // Get provider
            $provider = $this->providerFactory->createFromProfile($profile);
            if (!$provider) {
                Log::error("Failed to create AI provider for profile {$profile->id}");
                return null;
            }

            // Fetch market data
            $candles = $this->marketDataService->getOhlcv($symbol, $timeframe, 200, $connection);
            if (empty($candles)) {
                Log::warning("No market data available for {$symbol} {$timeframe}");
                return null;
            }

            // Calculate basic indicators
            $indicators = $this->calculateBasicIndicators($candles);

            // Prepare market data
            $marketData = [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'candles' => array_slice($candles, -50), // Last 50 candles
                'indicators' => $indicators,
                'latest_price' => $candles[count($candles) - 1]['close'] ?? null,
            ];

            // Call AI provider
            $result = $provider->analyzeForScan($marketData, $profile);

            if (!$result) {
                Log::warning("AI scan returned null for {$symbol} {$timeframe}");
                return null;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("MarketAnalysisAiService: Market scan failed", [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calculate basic indicators for context.
     */
    protected function calculateBasicIndicators(array $candles): array
    {
        $indicators = [];

        try {
            // EMA 20
            $indicators['ema20'] = $this->indicatorService->calculateEMA($candles, 20);
            
            // EMA 50
            $indicators['ema50'] = $this->indicatorService->calculateEMA($candles, 50);
            
            // Stochastic
            $stoch = $this->indicatorService->calculateStochastic($candles, 14, 3, 3);
            $indicators['stoch_k'] = $stoch['k'] ?? [];
            $indicators['stoch_d'] = $stoch['d'] ?? [];
        } catch (\Exception $e) {
            Log::warning("Failed to calculate indicators for AI analysis", ['error' => $e->getMessage()]);
        }

        return $indicators;
    }
}

