<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services\Calculators;

use Addons\TradingManagement\Shared\Contracts\RiskCalculatorInterface;
use Addons\TradingManagement\Modules\RiskManagement\Services\SymbolSpecService;
use Addons\TradingManagement\Modules\RiskManagement\Services\MarginManagementService;
use Addons\TradingManagement\Modules\RiskManagement\Services\CorrelationRiskService;
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

        if ($mode === 'FIXED') {
            $lotSize = (float) ($config['fixed_lot'] ?? 0.01);
            
            // Calculate actual risk even in FIXED mode
            $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 10000);
            $slDistance = $this->calculateSLDistance($signal, $config);
            $pipValue = $this->getPipValue($signal, $accountInfo);
            $riskAmount = $slDistance > 0 ? ($slDistance * $pipValue * $lotSize) : 0;
            $riskPercent = $equity > 0 ? ($riskAmount / $equity) * 100 : 0;
            
            return [
                'lot_size' => $lotSize,
                'risk_amount' => $riskAmount,
                'risk_percent' => $riskPercent,
            ];
        }

        // RISK_PERCENT mode
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 10000);
        $riskPercent = (float) ($config['risk_per_trade_pct'] ?? 1.0);
        $riskAmount = $equity * ($riskPercent / 100);

        // Calculate lot size based on SL distance
        $slDistance = $this->calculateSLDistance($signal, $config);
        
        if ($slDistance <= 0) {
            // Fallback to fixed lot if SL distance invalid
            $lotSize = 0.01;
        } else {
            // Lot size = Risk Amount / (SL Distance in pips * Pip Value)
            $pipValue = $this->getPipValue($signal, $accountInfo);
            $lotSize = $riskAmount / ($slDistance * $pipValue);
            
            // Ensure within reasonable bounds
            $lotSize = max(0.01, min($lotSize, 10.0));
        }

        return [
            'lot_size' => round($lotSize, 2),
            'risk_amount' => $riskAmount,
            'risk_percent' => $riskPercent,
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

