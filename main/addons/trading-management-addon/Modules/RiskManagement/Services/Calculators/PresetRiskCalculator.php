<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services\Calculators;

use Addons\TradingManagement\Shared\Contracts\RiskCalculatorInterface;
use Addons\TradingManagement\Modules\RiskManagement\Services\SymbolSpecService;
use Addons\TradingManagement\Modules\RiskManagement\Services\MarginManagementService;
use Addons\TradingManagement\Modules\RiskManagement\Services\CorrelationRiskService;
use Addons\TradingManagement\Modules\FilterStrategy\Services\IndicatorService;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

/**
 * Preset Risk Calculator
 * 
 * Manual preset-based position sizing
 * Implements traditional risk management rules
 */
class PresetRiskCalculator implements RiskCalculatorInterface
{
    /**
     * Calculate position size
     */
    public function calculatePositionSize(Signal $signal, array $accountInfo, array $config): array
    {
        $mode = $config['position_size_mode'] ?? 'RISK_PERCENT';

        // Kelly Criterion position sizing
        if ($mode === 'KELLY') {
            return $this->calculateKellyPositionSize($signal, $accountInfo, $config);
        }

        if ($mode === 'FIXED') {
            $lotSize = (float) ($config['fixed_lot'] ?? 0.01);
            
            // Calculate actual risk even in FIXED mode (including spread/slippage)
            $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 10000);
            $slDistance = $this->calculateSLDistance($signal, $config);
            
            // Account for spread and slippage in risk calculation
            $spread = $this->getSpread($signal, $accountInfo, $config);
            $slippage = $this->getExpectedSlippage($signal, $accountInfo, $config);
            $totalCost = $slDistance + $spread + $slippage;
            
            $pipValue = $this->getPipValue($signal, $accountInfo);
            $riskAmount = $totalCost > 0 ? ($totalCost * $pipValue * $lotSize) : 0;
            $riskPercent = $equity > 0 ? ($riskAmount / $equity) * 100 : 0;
            
            return [
                'lot_size' => $lotSize,
                'risk_amount' => $riskAmount,
                'risk_percent' => $riskPercent,
                'spread_pips' => $spread,
                'slippage_pips' => $slippage,
            ];
        }

        // RISK_PERCENT mode
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 10000);
        $baseRiskPercent = (float) ($config['risk_per_trade_pct'] ?? 1.0);
        
        // Apply drawdown-based risk reduction if enabled
        $riskPercent = $baseRiskPercent;
        if (($config['drawdown_risk_reduction_enabled'] ?? false)) {
            $riskPercent = $this->adjustRiskForDrawdown(
                $baseRiskPercent,
                $accountInfo,
                $config
            );
        }
        
        $riskAmount = $equity * ($riskPercent / 100);

        // Calculate lot size based on SL distance
        $slDistance = $this->calculateSLDistance($signal, $config);
        
        if ($slDistance <= 0) {
            // Fallback to fixed lot if SL distance invalid
            $lotSize = 0.01;
        } else {
            // Get spread and slippage to account for trading costs
            $spread = $this->getSpread($signal, $accountInfo, $config);
            $slippage = $this->getExpectedSlippage($signal, $accountInfo, $config);
            
            // Total cost includes SL distance + spread + slippage
            $totalCost = $slDistance + $spread + $slippage;
            
            // Lot size = Risk Amount / (Total Cost in pips * Pip Value)
            $pipValue = $this->getPipValue($signal, $accountInfo);
            $lotSize = $totalCost > 0 ? $riskAmount / ($totalCost * $pipValue) : 0;
            
            // Apply ATR-based volatility adjustment if enabled
            if (($config['atr_adjustment_enabled'] ?? false) && $lotSize > 0) {
                $lotSize = $this->applyATRAdjustment($lotSize, $signal, $config);
            }
            
            // Ensure within reasonable bounds
            $lotSize = max(0.01, min($lotSize, 10.0));
        }

        return [
            'lot_size' => round($lotSize, 2),
            'risk_amount' => $riskAmount,
            'risk_percent' => $riskPercent,
            'spread_pips' => $spread,
            'slippage_pips' => $slippage,
            'total_cost_pips' => $totalCost,
        ];
    }

    /**
     * Calculate stop loss price
     */
    public function calculateStopLoss(Signal $signal, float $lotSize, array $config): float
    {
        $slMode = $config['sl_mode'] ?? 'PIPS';
        $entryPrice = (float) $signal->open_price;
        $direction = $signal->direction;

        if ($slMode === 'PIPS') {
            $slPips = (float) ($config['sl_pips'] ?? 50);
            $pipSize = $this->getPipSize($signal);
            
            if ($direction === 'buy' || $direction === 'long') {
                return $entryPrice - ($slPips * $pipSize);
            } else {
                return $entryPrice + ($slPips * $pipSize);
            }
        }

        // R_MULTIPLE or STRUCTURE mode - use signal's SL
        return (float) $signal->sl;
    }

    /**
     * Calculate take profit prices
     */
    public function calculateTakeProfits(Signal $signal, float $lotSize, array $config): array
    {
        $tpMode = $config['tp_mode'] ?? 'SINGLE';
        
        if ($tpMode === 'DISABLED') {
            return [];
        }

        $entryPrice = (float) $signal->open_price;
        $slPrice = $this->calculateStopLoss($signal, $lotSize, $config);
        $riskDistance = abs($entryPrice - $slPrice);
        $direction = $signal->direction;
        
        $takeProfits = [];

        // TP1
        if ($config['tp1_enabled'] ?? true) {
            $tp1RR = (float) ($config['tp1_rr'] ?? 2.0);
            $tp1Distance = $riskDistance * $tp1RR;
            
            $takeProfits[] = $direction === 'buy' || $direction === 'long'
                ? $entryPrice + $tp1Distance
                : $entryPrice - $tp1Distance;
        }

        // TP2
        if ($tpMode === 'MULTI' && ($config['tp2_enabled'] ?? false)) {
            $tp2RR = (float) ($config['tp2_rr'] ?? 3.0);
            $tp2Distance = $riskDistance * $tp2RR;
            
            $takeProfits[] = $direction === 'buy' || $direction === 'long'
                ? $entryPrice + $tp2Distance
                : $entryPrice - $tp2Distance;
        }

        // TP3
        if ($tpMode === 'MULTI' && ($config['tp3_enabled'] ?? false)) {
            $tp3RR = (float) ($config['tp3_rr'] ?? 5.0);
            $tp3Distance = $riskDistance * $tp3RR;
            
            $takeProfits[] = $direction === 'buy' || $direction === 'long'
                ? $entryPrice + $tp3Distance
                : $entryPrice - $tp3Distance;
        }

        return $takeProfits;
    }

    /**
     * Validate trade
     */
    public function validateTrade(Signal $signal, array $accountInfo, array $config): array
    {
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 0);

        if ($equity <= 0) {
            return ['valid' => false, 'reason' => 'Insufficient account balance'];
        }

        // Calculate position size
        $positionData = $this->calculatePositionSize($signal, $accountInfo, $config);

        if ($positionData['lot_size'] < 0.01) {
            return ['valid' => false, 'reason' => 'Calculated lot size too small (< 0.01)'];
        }

        if ($positionData['lot_size'] > 10.0) {
            return ['valid' => false, 'reason' => 'Calculated lot size too large (> 10.0)'];
        }

        // Check margin requirements
        $marginService = app(MarginManagementService::class);
        $symbol = $signal->pair->name ?? '';
        $entryPrice = (float) $signal->open_price;
        $leverage = (int) ($config['leverage'] ?? $accountInfo['leverage'] ?? 100);
        
        if ($entryPrice > 0 && !empty($symbol)) {
            $requiredMargin = $marginService->calculateRequiredMargin(
                $positionData['lot_size'],
                $entryPrice,
                $leverage,
                $symbol
            );
            
            $marginCheck = $marginService->shouldPreventTrade($accountInfo, $requiredMargin, $config);
            
            if ($marginCheck['should_prevent']) {
                return [
                    'valid' => false,
                    'reason' => $marginCheck['reason'] ?? 'Insufficient margin for trade',
                ];
            }
        }

        // Check correlation risk
        $correlationService = app(CorrelationRiskService::class);
        $maxCorrelationExposurePct = (float) ($config['max_correlation_exposure_pct'] ?? 50.0);
        
        // Get existing open positions for this connection (if available)
        $existingPositions = $this->getExistingPositions($accountInfo, $symbol);
        $newPositionValue = $positionData['lot_size'] * $entryPrice;
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 0);
        
        if (!empty($symbol) && $equity > 0 && $newPositionValue > 0) {
            $correlationCheck = $correlationService->shouldPreventTrade(
                $symbol,
                $existingPositions,
                $newPositionValue,
                $equity,
                $maxCorrelationExposurePct
            );
            
            if ($correlationCheck['should_prevent']) {
                return [
                    'valid' => false,
                    'reason' => $correlationCheck['reason'] ?? 'Correlation risk exceeds limit',
                ];
            }
        }

        return ['valid' => true, 'reason' => null];
    }

    public function getCalculatorName(): string
    {
        return 'preset';
    }

    /**
     * Calculate position size using Kelly Criterion
     * 
     * Kelly% = (WinRate * AvgWin - (1 - WinRate) * AvgLoss) / AvgWin
     * Uses fractional Kelly (25% of full Kelly) for safety
     * 
     * @param Signal $signal
     * @param array $accountInfo
     * @param array $config ['win_rate' => float, 'avg_win_r' => float, 'avg_loss_r' => float, 'kelly_fraction' => float]
     * @return array
     */
    protected function calculateKellyPositionSize(Signal $signal, array $accountInfo, array $config): array
    {
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 10000);
        
        // Get Kelly parameters from config or accountInfo
        $winRate = (float) ($config['win_rate'] ?? $accountInfo['win_rate'] ?? 0.5);
        $avgWinR = (float) ($config['avg_win_r'] ?? $accountInfo['avg_win_r'] ?? 2.0); // Average win in R multiples
        $avgLossR = (float) ($config['avg_loss_r'] ?? $accountInfo['avg_loss_r'] ?? 1.0); // Average loss in R multiples
        $kellyFraction = (float) ($config['kelly_fraction'] ?? 0.25); // Fractional Kelly (25% of full Kelly)
        
        // Calculate full Kelly percentage
        // Kelly% = (WinRate * AvgWin - (1 - WinRate) * AvgLoss) / AvgWin
        if ($avgWinR <= 0) {
            // Invalid parameters, fallback to risk percent
            Log::warning('PresetRiskCalculator: Invalid avg_win_r for Kelly, falling back to RISK_PERCENT', [
                'avg_win_r' => $avgWinR,
            ]);
            $config['position_size_mode'] = 'RISK_PERCENT';
            return $this->calculatePositionSize($signal, $accountInfo, $config);
        }
        
        $fullKelly = ($winRate * $avgWinR - (1 - $winRate) * $avgLossR) / $avgWinR;
        
        // Apply fractional Kelly and cap at 25% of equity
        $fractionalKelly = $fullKelly * $kellyFraction;
        $kellyPercent = max(0, min($fractionalKelly, 0.25)); // Cap at 25% of equity
        
        // Calculate risk amount based on Kelly percentage
        $riskAmount = $equity * $kellyPercent;
        
        // Calculate lot size based on SL distance (same as RISK_PERCENT mode)
        $slDistance = $this->calculateSLDistance($signal, $config);
        
        if ($slDistance <= 0) {
            $lotSize = 0.01;
        } else {
            // Account for spread and slippage
            $spread = $this->getSpread($signal, $accountInfo, $config);
            $slippage = $this->getExpectedSlippage($signal, $accountInfo, $config);
            $totalCost = $slDistance + $spread + $slippage;
            
            $pipValue = $this->getPipValue($signal, $accountInfo);
            $lotSize = $totalCost > 0 ? $riskAmount / ($totalCost * $pipValue) : 0;
            $lotSize = max(0.01, min($lotSize, 10.0));
        }
        
        return [
            'lot_size' => round($lotSize, 2),
            'risk_amount' => $riskAmount,
            'risk_percent' => $kellyPercent * 100,
            'kelly_percent' => $kellyPercent * 100,
            'full_kelly_percent' => $fullKelly * 100,
            'win_rate' => $winRate * 100,
            'avg_win_r' => $avgWinR,
            'avg_loss_r' => $avgLossR,
            'spread_pips' => $spread,
            'slippage_pips' => $slippage,
            'total_cost_pips' => $totalCost,
        ];
    }

    /**
     * Calculate SL distance in pips
     */
    protected function calculateSLDistance(Signal $signal, array $config): float
    {
        $slMode = $config['sl_mode'] ?? 'PIPS';

        if ($slMode === 'PIPS') {
            return (float) ($config['sl_pips'] ?? 50);
        }

        // Calculate from signal's SL
        $entryPrice = (float) $signal->open_price;
        $slPrice = (float) $signal->sl;
        $slDistance = abs($entryPrice - $slPrice);
        $pipSize = $this->getPipSize($signal);

        return $slDistance / $pipSize;
    }

    /**
     * Get pip size for a symbol (0.0001 for most FX, 0.01 for JPY pairs)
     */
    protected function getPipSize(Signal $signal): float
    {
        $symbol = $signal->pair->name ?? '';
        
        // JPY pairs have different pip size
        if (str_contains($symbol, 'JPY')) {
            return 0.01;
        }

        // XAU (gold) has different pip size
        if (str_contains($symbol, 'XAU') || str_contains($symbol, 'GOLD')) {
            return 0.10;
        }

        // Most FX pairs
        return 0.0001;
    }

    /**
     * Get pip value (how much 1 pip is worth in account currency)
     */
    protected function getPipValue(Signal $signal, array $accountInfo): float
    {
        $symbolSpecService = app(SymbolSpecService::class);
        $symbol = $signal->pair->name ?? '';
        $accountCurrency = $accountInfo['currency'] ?? 'USD';
        $entryPrice = (float) $signal->open_price;
        
        if (empty($symbol) || $entryPrice <= 0) {
            Log::warning('PresetRiskCalculator: Invalid symbol or entry price for pip value calculation', [
                'symbol' => $symbol,
                'entry_price' => $entryPrice,
            ]);
            // Fallback to standard $10 per pip for 1.0 lot
            return 10.0;
        }
        
        return $symbolSpecService->getPipValue($symbol, 1.0, $accountCurrency, $entryPrice);
    }

    /**
     * Get spread for a symbol (in pips)
     * 
     * @param Signal $signal
     * @param array $accountInfo
     * @param array $config
     * @return float Spread in pips
     */
    protected function getSpread(Signal $signal, array $accountInfo, array $config): float
    {
        $symbol = $signal->pair->name ?? '';
        
        // Try to get spread from accountInfo (if provided by exchange)
        if (isset($accountInfo['spread']) && $accountInfo['spread'] > 0) {
            $spread = (float) $accountInfo['spread'];
            $pipSize = $this->getPipSize($signal);
            return $spread / $pipSize; // Convert to pips
        }
        
        // Try to get from config (preset-specific spread override)
        if (isset($config['spread_pips']) && $config['spread_pips'] > 0) {
            return (float) $config['spread_pips'];
        }
        
        // Default spreads based on symbol type
        $symbolUpper = strtoupper($symbol);
        
        // Major FX pairs: 1-2 pips
        $majorPairs = ['EUR/USD', 'GBP/USD', 'USD/JPY', 'USD/CHF', 'AUD/USD', 'USD/CAD', 'NZD/USD'];
        if (in_array($symbol, $majorPairs)) {
            return 1.5; // Average spread for major pairs
        }
        
        // Minor FX pairs: 2-3 pips
        if ($this->isForexPair($symbolUpper)) {
            return 2.5; // Average spread for minor pairs
        }
        
        // Crypto: typically 0.01-0.1% of price (convert to pips equivalent)
        if ($this->isCryptoPair($symbolUpper)) {
            $entryPrice = (float) $signal->open_price;
            $pipSize = $this->getPipSize($signal);
            // Assume 0.05% spread for crypto
            $spreadPercent = 0.0005;
            $spreadPrice = $entryPrice * $spreadPercent;
            return $spreadPrice / $pipSize; // Convert to pips
        }
        
        // Commodities: 0.2-0.5 pips (in price terms)
        if ($this->isCommodity($symbolUpper)) {
            return 0.3; // Average spread for commodities
        }
        
        // Default: 2 pips
        return 2.0;
    }
    
    /**
     * Get expected slippage for a symbol (in pips)
     * 
     * @param Signal $signal
     * @param array $accountInfo
     * @param array $config
     * @return float Expected slippage in pips
     */
    protected function getExpectedSlippage(Signal $signal, array $accountInfo, array $config): float
    {
        $symbol = $signal->pair->name ?? '';
        
        // Try to get from config (preset-specific slippage buffer)
        if (isset($config['slippage_pips']) && $config['slippage_pips'] >= 0) {
            return (float) $config['slippage_pips'];
        }
        
        // Default slippage based on symbol type and market conditions
        $symbolUpper = strtoupper($symbol);
        
        // Major FX pairs: 0.5-1 pip slippage
        $majorPairs = ['EUR/USD', 'GBP/USD', 'USD/JPY', 'USD/CHF', 'AUD/USD', 'USD/CAD', 'NZD/USD'];
        if (in_array($symbol, $majorPairs)) {
            return 0.5; // Low slippage for liquid major pairs
        }
        
        // Minor FX pairs: 1-2 pips slippage
        if ($this->isForexPair($symbolUpper)) {
            return 1.0; // Moderate slippage for minor pairs
        }
        
        // Crypto: 0.01-0.05% slippage (convert to pips equivalent)
        if ($this->isCryptoPair($symbolUpper)) {
            $entryPrice = (float) $signal->open_price;
            $pipSize = $this->getPipSize($signal);
            // Assume 0.02% slippage for crypto
            $slippagePercent = 0.0002;
            $slippagePrice = $entryPrice * $slippagePercent;
            return $slippagePrice / $pipSize; // Convert to pips
        }
        
        // Commodities: 0.1-0.3 pips slippage
        if ($this->isCommodity($symbolUpper)) {
            return 0.2; // Low slippage for commodities
        }
        
        // Default: 1 pip
        return 1.0;
    }
    
    /**
     * Check if symbol is a forex pair
     */
    protected function isForexPair(string $symbol): bool
    {
        $forexCurrencies = ['USD', 'EUR', 'GBP', 'JPY', 'CHF', 'AUD', 'CAD', 'NZD', 'SEK', 'NOK', 'DKK', 'PLN', 'HUF', 'CZK', 'RUB', 'TRY', 'ZAR', 'MXN', 'BRL', 'CNY', 'HKD', 'SGD'];
        
        $parts = preg_split('/[\/\-_]/', $symbol);
        if (count($parts) !== 2) {
            return false;
        }
        
        return in_array($parts[0], $forexCurrencies) && in_array($parts[1], $forexCurrencies);
    }
    
    /**
     * Check if symbol is a crypto pair
     */
    protected function isCryptoPair(string $symbol): bool
    {
        $cryptoCurrencies = ['BTC', 'ETH', 'BNB', 'ADA', 'SOL', 'XRP', 'DOT', 'DOGE', 'MATIC', 'AVAX', 'LINK', 'UNI', 'LTC', 'BCH', 'ALGO', 'ATOM', 'VET', 'FIL', 'TRX', 'ETC', 'XLM', 'EOS', 'AAVE', 'MKR', 'COMP', 'SUSHI', 'YFI', 'SNX', 'CRV', 'USDT', 'USDC', 'BUSD', 'DAI', 'TUSD', 'PAX', 'USDP'];
        
        $parts = preg_split('/[\/\-_]/', $symbol);
        if (count($parts) !== 2) {
            return false;
        }
        
        return in_array($parts[0], $cryptoCurrencies) || in_array($parts[1], $cryptoCurrencies);
    }
    
    /**
     * Check if symbol is a commodity
     */
    protected function isCommodity(string $symbol): bool
    {
        return str_contains($symbol, 'XAU') || 
               str_contains($symbol, 'XAG') || 
               str_contains($symbol, 'GOLD') || 
               str_contains($symbol, 'SILVER') ||
               str_contains($symbol, 'OIL') ||
               str_contains($symbol, 'CRUDE');
    }

    /**
     * Apply ATR-based volatility adjustment to position size
     * 
     * Higher volatility = smaller position size
     * Lower volatility = larger position size (up to max adjustment)
     * 
     * @param float $baseLotSize Base lot size before adjustment
     * @param Signal $signal
     * @param array $config ['atr_period' => int, 'atr_baseline' => float, 'atr_volatility_factor' => float]
     * @return float Adjusted lot size
     */
    protected function applyATRAdjustment(float $baseLotSize, Signal $signal, array $config): float
    {
        try {
            $symbol = $signal->pair->name ?? '';
            $timeframe = $this->getTimeframeForATR($signal);
            $atrPeriod = (int) ($config['atr_period'] ?? 14);
            $baselineATR = (float) ($config['atr_baseline'] ?? null);
            $volatilityFactor = (float) ($config['atr_volatility_factor'] ?? 1.0);
            
            if (empty($symbol)) {
                return $baseLotSize; // Can't adjust without symbol
            }
            
            // Get market data to calculate ATR
            $marketDataService = app(MarketDataService::class);
            $marketDataRecords = $marketDataService->getLatest($symbol, $timeframe, $atrPeriod + 10);
            
            if ($marketDataRecords->isEmpty()) {
                Log::debug('PresetRiskCalculator: No market data for ATR calculation', [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                ]);
                return $baseLotSize; // Fallback to base size
            }
            
            // Convert to candles array format
            $candles = $marketDataRecords->map(function ($data) {
                return [
                    'high' => (float) $data->high,
                    'low' => (float) $data->low,
                    'close' => (float) $data->close,
                    'open' => (float) $data->open,
                ];
            })->reverse()->values()->toArray();
            
            // Calculate ATR
            $indicatorService = app(IndicatorService::class);
            $currentATR = $indicatorService->getLatestATR($candles, $atrPeriod);
            
            if ($currentATR === null || $currentATR <= 0) {
                return $baseLotSize; // Can't adjust without ATR
            }
            
            // If baseline not provided, use current ATR as baseline (no adjustment)
            if ($baselineATR === null || $baselineATR <= 0) {
                $baselineATR = $currentATR;
            }
            
            // Calculate volatility ratio
            $volatilityRatio = $currentATR / $baselineATR;
            
            // Adjust position size: higher volatility = smaller size
            // Adjustment = volatilityFactor / volatilityRatio
            // When volatility is 2x baseline, size is halved (if factor = 1.0)
            $adjustment = $volatilityFactor / max(0.1, $volatilityRatio); // Prevent division by zero
            
            // Cap adjustment to prevent extreme sizes (0.5x to 2x)
            $adjustment = max(0.5, min($adjustment, 2.0));
            
            $adjustedLotSize = $baseLotSize * $adjustment;
            
            Log::debug('PresetRiskCalculator: ATR adjustment applied', [
                'symbol' => $symbol,
                'base_lot_size' => $baseLotSize,
                'adjusted_lot_size' => $adjustedLotSize,
                'current_atr' => $currentATR,
                'baseline_atr' => $baselineATR,
                'volatility_ratio' => $volatilityRatio,
                'adjustment' => $adjustment,
            ]);
            
            return $adjustedLotSize;
        } catch (\Exception $e) {
            Log::warning('PresetRiskCalculator: Failed to apply ATR adjustment', [
                'symbol' => $signal->pair->name ?? '',
                'error' => $e->getMessage(),
            ]);
            return $baseLotSize; // Fallback to base size on error
        }
    }
    
    /**
     * Adjust risk based on current drawdown
     * 
     * Reduces position size when account is in drawdown to protect capital
     * 
     * @param float $baseRiskPercent Base risk percentage
     * @param array $accountInfo Account information with equity, balance, peak_equity
     * @param array $config ['max_drawdown_percent' => float, 'drawdown_reduction_factor' => float]
     * @return float Adjusted risk percent
     */
    protected function adjustRiskForDrawdown(
        float $baseRiskPercent,
        array $accountInfo,
        array $config
    ): float {
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 0);
        $peakEquity = (float) ($accountInfo['peak_equity'] ?? $accountInfo['balance'] ?? $equity);
        $maxDrawdownPercent = (float) ($config['max_drawdown_percent'] ?? 20.0);
        $reductionFactor = (float) ($config['drawdown_reduction_factor'] ?? 0.5); // How much to reduce at max DD
        
        if ($equity <= 0 || $peakEquity <= 0) {
            return $baseRiskPercent; // Can't calculate drawdown
        }
        
        // Calculate current drawdown percentage
        $currentDrawdownPercent = (($peakEquity - $equity) / $peakEquity) * 100;
        
        // If drawdown exceeds maximum, reduce risk to minimum (10% of base)
        if ($currentDrawdownPercent >= $maxDrawdownPercent) {
            $adjustedRisk = $baseRiskPercent * 0.1; // Reduce to 10% of base risk
            Log::info('PresetRiskCalculator: Maximum drawdown reached, reducing risk', [
                'current_drawdown' => $currentDrawdownPercent,
                'max_drawdown' => $maxDrawdownPercent,
                'base_risk' => $baseRiskPercent,
                'adjusted_risk' => $adjustedRisk,
            ]);
            return $adjustedRisk;
        }
        
        // Linear reduction: at max drawdown, reduce by reductionFactor
        // Example: at 10% drawdown with 20% max, reduce by 25% (half of reductionFactor)
        $drawdownRatio = $currentDrawdownPercent / $maxDrawdownPercent;
        $reduction = $drawdownRatio * $reductionFactor;
        $adjustmentFactor = 1.0 - $reduction;
        
        // Ensure minimum risk (at least 10% of base)
        $adjustmentFactor = max(0.1, $adjustmentFactor);
        
        $adjustedRisk = $baseRiskPercent * $adjustmentFactor;
        
        if ($currentDrawdownPercent > 0) {
            Log::debug('PresetRiskCalculator: Drawdown-based risk adjustment', [
                'current_drawdown' => $currentDrawdownPercent,
                'max_drawdown' => $maxDrawdownPercent,
                'base_risk' => $baseRiskPercent,
                'adjusted_risk' => $adjustedRisk,
                'adjustment_factor' => $adjustmentFactor,
            ]);
        }
        
        return $adjustedRisk;
    }

    /**
     * Get timeframe for ATR calculation
     * Uses signal timeframe or defaults to H1
     */
    protected function getTimeframeForATR(Signal $signal): string
    {
        if ($signal->time && $signal->time->name) {
            return $this->mapTimeframeToStandard($signal->time->name);
        }
        return 'H1'; // Default to H1
    }
    
    /**
     * Map timeframe to standard format
     */
    protected function mapTimeframeToStandard(string $timeframe): string
    {
        $mapping = [
            'M1' => 'M1', 'M5' => 'M5', 'M15' => 'M15', 'M30' => 'M30',
            'H1' => 'H1', 'H4' => 'H4', 'D1' => 'D1', 'W1' => 'W1',
            '1m' => 'M1', '5m' => 'M5', '15m' => 'M15', '30m' => 'M30',
            '1h' => 'H1', '4h' => 'H4', '1d' => 'D1', '1w' => 'W1',
        ];
        
        return $mapping[strtoupper($timeframe)] ?? 'H1';
    }

    /**
     * Get existing open positions for correlation check
     * 
     * @param array $accountInfo Account information
     * @param string $symbol Current symbol (to exclude from check)
     * @return array Array of existing positions
     */
    protected function getExistingPositions(array $accountInfo, string $symbol): array
    {
        $connectionId = $accountInfo['connection_id'] ?? null;
        
        if (!$connectionId) {
            Log::warning('PresetRiskCalculator: No connection_id in accountInfo, cannot fetch existing positions', [
                'accountInfo_keys' => array_keys($accountInfo),
            ]);
            return [];
        }

        try {
            $positions = ExecutionPosition::where('connection_id', $connectionId)
                ->where('status', 'open')
                ->get();

            return $positions->map(function ($position) {
                return [
                    'symbol' => $position->symbol ?? '',
                    'quantity' => (float) ($position->quantity ?? 0),
                    'entry_price' => (float) ($position->entry_price ?? 0),
                    'direction' => $position->direction ?? 'buy',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('PresetRiskCalculator: Failed to fetch existing positions', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

