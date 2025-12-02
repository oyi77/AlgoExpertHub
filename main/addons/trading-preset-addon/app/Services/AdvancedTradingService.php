<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use Illuminate\Support\Facades\Log;

/**
 * Service for advanced trading features:
 * - Dynamic equity calculation
 * - ATR-based calculations
 * - Chandelier stop loss
 * - Candle-based exit logic
 */
class AdvancedTradingService
{
    protected ?ConnectionService $connectionService;

    public function __construct()
    {
        if (class_exists(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)) {
            $this->connectionService = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class);
        } else {
            $this->connectionService = null;
        }
    }

    /**
     * Calculate dynamic equity based on preset configuration
     * 
     * @param PresetConfigurationDTO $config Preset configuration
     * @param ExecutionConnection $connection Execution connection
     * @param float $baseEquity Base equity amount
     * @return float Adjusted equity
     */
    public function calculateDynamicEquity(
        PresetConfigurationDTO $config,
        ExecutionConnection $connection,
        float $baseEquity
    ): float {
        if ($config->equity_dynamic_mode === 'NONE') {
            return $baseEquity;
        }

        if (!$this->connectionService) {
            return $baseEquity;
        }

        try {
            $adapter = $this->connectionService->getAdapter($connection);
            if (!$adapter) {
                return $baseEquity;
            }

            $balanceData = $adapter->getBalance();
            $currentEquity = $balanceData['equity'] ?? $balanceData['balance'] ?? $baseEquity;

            switch ($config->equity_dynamic_mode) {
                case 'LINEAR':
                    // Linear adjustment based on current equity vs base
                    if ($config->equity_base && $config->equity_base > 0) {
                        $ratio = $currentEquity / $config->equity_base;
                        return $baseEquity * $ratio;
                    }
                    return $currentEquity;

                case 'STEP':
                    // Step-based adjustment
                    if ($config->equity_base && $config->equity_step_factor) {
                        $steps = floor($currentEquity / $config->equity_base);
                        return $baseEquity * pow($config->equity_step_factor, $steps);
                    }
                    return $currentEquity;

                default:
                    return $baseEquity;
            }
        } catch (\Exception $e) {
            Log::warning("AdvancedTradingService: Failed to calculate dynamic equity", [
                'error' => $e->getMessage(),
            ]);
            return $baseEquity;
        }
    }

    /**
     * Calculate ATR (Average True Range) from price history
     * 
     * @param array $candles Array of candles with ['high', 'low', 'close'] keys
     * @param int $period ATR period (default 14)
     * @return float|null ATR value or null if insufficient data
     */
    public function calculateATR(array $candles, int $period = 14): ?float
    {
        if (count($candles) < $period + 1) {
            return null; // Need at least period+1 candles
        }

        $trueRanges = [];

        for ($i = 1; $i < count($candles); $i++) {
            $current = $candles[$i];
            $previous = $candles[$i - 1];

            $high = $current['high'] ?? 0;
            $low = $current['low'] ?? 0;
            $prevClose = $previous['close'] ?? 0;

            // True Range = max(high - low, abs(high - prevClose), abs(low - prevClose))
            $tr1 = $high - $low;
            $tr2 = abs($high - $prevClose);
            $tr3 = abs($low - $prevClose);

            $trueRanges[] = max($tr1, $tr2, $tr3);
        }

        // Calculate ATR as SMA of True Ranges
        if (count($trueRanges) < $period) {
            return null;
        }

        $atrValues = array_slice($trueRanges, -$period);
        return array_sum($atrValues) / $period;
    }

    /**
     * Calculate ATR-based trailing stop price
     * 
     * @param float $currentPrice Current price
     * @param float $atr ATR value
     * @param float $atrMultiplier ATR multiplier (e.g., 2.0 for 2x ATR)
     * @param string $direction 'buy' or 'sell'
     * @param float|null $currentSl Current stop loss price
     * @return float|null New trailing stop price
     */
    public function calculateATRTrailingStop(
        float $currentPrice,
        float $atr,
        float $atrMultiplier,
        string $direction,
        ?float $currentSl = null
    ): ?float {
        $atrDistance = $atr * $atrMultiplier;

        if ($direction === 'buy') {
            $newSl = $currentPrice - $atrDistance;
            // Only move SL up (favorable direction)
            if ($currentSl && $newSl < $currentSl) {
                return $currentSl; // Don't move SL down
            }
            return $newSl;
        } else {
            // sell
            $newSl = $currentPrice + $atrDistance;
            // Only move SL down (favorable direction)
            if ($currentSl && $newSl > $currentSl) {
                return $currentSl; // Don't move SL up
            }
            return $newSl;
        }
    }

    /**
     * Calculate Chandelier Stop Loss
     * Chandelier Stop = Highest High (or Lowest Low) - (ATR * Multiplier)
     * 
     * @param array $candles Array of candles with ['high', 'low', 'close'] keys
     * @param int $lookbackPeriod Lookback period for highest/lowest
     * @param float $atrMultiplier ATR multiplier
     * @param string $direction 'buy' or 'sell'
     * @return float|null Chandelier stop price
     */
    public function calculateChandelierStop(
        array $candles,
        int $lookbackPeriod,
        float $atrMultiplier,
        string $direction
    ): ?float {
        if (count($candles) < max($lookbackPeriod, 14)) {
            return null; // Need sufficient data
        }

        // Get recent candles for lookback
        $recentCandles = array_slice($candles, -$lookbackPeriod);

        // Calculate ATR
        $atr = $this->calculateATR($candles, 14);
        if (!$atr) {
            return null;
        }

        if ($direction === 'buy') {
            // For buy: Chandelier = Highest High - (ATR * Multiplier)
            $highestHigh = max(array_column($recentCandles, 'high'));
            return $highestHigh - ($atr * $atrMultiplier);
        } else {
            // For sell: Chandelier = Lowest Low + (ATR * Multiplier)
            $lowestLow = min(array_column($recentCandles, 'low'));
            return $lowestLow + ($atr * $atrMultiplier);
        }
    }

    /**
     * Get price history/candles for a symbol
     * This is a placeholder - actual implementation depends on data source
     * 
     * @param ExecutionConnection $connection Execution connection
     * @param string $symbol Symbol (e.g., 'EURUSD', 'BTC/USDT')
     * @param string $timeframe Timeframe (e.g., '1h', '4h', '1d')
     * @param int $limit Number of candles to fetch
     * @return array Array of candles
     */
    public function getPriceHistory(
        ExecutionConnection $connection,
        string $symbol,
        string $timeframe,
        int $limit = 50
    ): array {
        if (!$this->connectionService) {
            return [];
        }

        try {
            $adapter = $this->connectionService->getAdapter($connection);
            if (!$adapter) {
                return [];
            }

            // Try to fetch candles from adapter if it supports it
            if (method_exists($adapter, 'fetchOHLCV')) {
                $candles = $adapter->fetchOHLCV($symbol, $timeframe, $limit);
                return $this->formatCandles($candles);
            }

            // Fallback: return empty array (would need external data source)
            Log::debug("AdvancedTradingService: Adapter does not support fetchOHLCV", [
                'connection_id' => $connection->id,
                'symbol' => $symbol,
            ]);

            return [];
        } catch (\Exception $e) {
            Log::warning("AdvancedTradingService: Failed to fetch price history", [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id,
                'symbol' => $symbol,
            ]);
            return [];
        }
    }

    /**
     * Format candles from adapter to standard format
     * 
     * @param array $rawCandles Raw candles from adapter
     * @return array Formatted candles
     */
    protected function formatCandles(array $rawCandles): array
    {
        $formatted = [];

        foreach ($rawCandles as $candle) {
            // Handle different formats: [timestamp, open, high, low, close, volume] or object
            if (is_array($candle)) {
                $formatted[] = [
                    'timestamp' => $candle[0] ?? null,
                    'open' => $candle[1] ?? 0,
                    'high' => $candle[2] ?? 0,
                    'low' => $candle[3] ?? 0,
                    'close' => $candle[4] ?? 0,
                    'volume' => $candle[5] ?? 0,
                ];
            } elseif (is_object($candle)) {
                $formatted[] = [
                    'timestamp' => $candle->timestamp ?? $candle->time ?? null,
                    'open' => $candle->open ?? 0,
                    'high' => $candle->high ?? 0,
                    'low' => $candle->low ?? 0,
                    'close' => $candle->close ?? 0,
                    'volume' => $candle->volume ?? 0,
                ];
            }
        }

        return $formatted;
    }

    /**
     * Check if position should be closed based on candle close logic
     * 
     * @param PresetConfigurationDTO $config Preset configuration
     * @param \DateTime $positionOpenedAt When position was opened
     * @param string $timeframe Timeframe to check (e.g., '1h', '4h')
     * @return array ['should_close' => bool, 'reason' => string|null]
     */
    public function checkCandleCloseLogic(
        PresetConfigurationDTO $config,
        \DateTime $positionOpenedAt,
        string $timeframe
    ): array {
        if (!$config->auto_close_on_candle_close) {
            return ['should_close' => false, 'reason' => null];
        }

        // Check hold_max_candles
        if ($config->hold_max_candles && $config->hold_max_candles > 0) {
            $candlesHeld = $this->calculateCandlesHeld($positionOpenedAt, $timeframe);
            if ($candlesHeld >= $config->hold_max_candles) {
                return [
                    'should_close' => true,
                    'reason' => "Maximum candles ({$config->hold_max_candles}) reached",
                ];
            }
        }

        // Check if current candle is closing
        // This would need to be called at candle close time
        // For now, return false (would need scheduler integration)
        return ['should_close' => false, 'reason' => null];
    }

    /**
     * Calculate number of candles held since position opened
     * 
     * @param \DateTime $positionOpenedAt When position was opened
     * @param string $timeframe Timeframe (e.g., '1h', '4h', '1d')
     * @return int Number of candles
     */
    protected function calculateCandlesHeld(\DateTime $positionOpenedAt, string $timeframe): int
    {
        $now = new \DateTime();
        $secondsElapsed = $now->getTimestamp() - $positionOpenedAt->getTimestamp();

        // Convert timeframe to seconds
        $timeframeSeconds = $this->timeframeToSeconds($timeframe);
        if ($timeframeSeconds === 0) {
            return 0;
        }

        return (int) floor($secondsElapsed / $timeframeSeconds);
    }

    /**
     * Convert timeframe string to seconds
     * 
     * @param string $timeframe Timeframe (e.g., '1m', '5m', '1h', '4h', '1d')
     * @return int Seconds
     */
    protected function timeframeToSeconds(string $timeframe): int
    {
        $timeframe = strtolower(trim($timeframe));
        
        // Extract number and unit
        if (preg_match('/^(\d+)([mhd])$/', $timeframe, $matches)) {
            $number = (int) $matches[1];
            $unit = $matches[2];

            switch ($unit) {
                case 'm': // minutes
                    return $number * 60;
                case 'h': // hours
                    return $number * 3600;
                case 'd': // days
                    return $number * 86400;
            }
        }

        return 0; // Invalid timeframe
    }
}

