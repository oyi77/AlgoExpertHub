<?php

namespace Addons\AiTradingAddon\App\Contracts;

use Addons\AiTradingAddon\App\Models\AiModelProfile;

interface AiTradingProviderInterface
{
    /**
     * Get provider name.
     */
    public function getName(): string;

    /**
     * Get provider identifier.
     */
    public function getProvider(): string;

    /**
     * Analyze for signal confirmation.
     * 
     * @param array $marketData OHLCV + indicators
     * @param array $signalData Signal data (pair, direction, entry, SL, TP, etc.)
     * @param AiModelProfile $profile AI model profile
     * @return array|null Analysis result or null on failure
     *   - alignment: float (0-100)
     *   - safety_score: float (0-100)
     *   - decision: string (ACCEPT, REJECT, SIZE_DOWN, etc.)
     *   - reasoning: string
     *   - confidence: float (0-100)
     */
    public function analyzeForConfirmation(array $marketData, array $signalData, AiModelProfile $profile): ?array;

    /**
     * Analyze for pure market scan.
     * 
     * @param array $marketData OHLCV + indicators
     * @param AiModelProfile $profile AI model profile
     * @return array|null Analysis result or null on failure
     *   - should_open_trade: bool
     *   - direction: string (BUY, SELL)
     *   - entry: float
     *   - sl: float
     *   - tp: float
     *   - confidence: float (0-100)
     *   - reasoning: string
     */
    public function analyzeForScan(array $marketData, AiModelProfile $profile): ?array;

    /**
     * Analyze for position management.
     * 
     * @param array $positionData Open position data
     * @param array $marketData Current market data
     * @param AiModelProfile $profile AI model profile
     * @return array|null Analysis result or null on failure
     *   - action: string (SET_BE, ADJUST_SL, TIGHTEN_TP, CLOSE_PARTIAL, CLOSE_FULL, HOLD)
     *   - new_sl: float|null
     *   - new_tp: float|null
     *   - close_percentage: float|null (0-100)
     *   - reasoning: string
     */
    public function analyzeForPositionMgmt(array $positionData, array $marketData, AiModelProfile $profile): ?array;

    /**
     * Test API connection.
     * 
     * @param AiModelProfile $profile
     * @return bool
     */
    public function testConnection(AiModelProfile $profile): bool;
}

