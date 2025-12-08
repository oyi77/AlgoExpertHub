<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Jobs;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use Addons\TradingManagement\Modules\RiskManagement\Services\RiskCalculatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RiskManagementJob
 * 
 * Applies risk management based on connection profile (TradingPreset)
 * Calculates position sizes, SL/TP levels
 */
class RiskManagementJob implements ShouldQueue
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
            // Get connection (for execution)
            $connection = $this->bot->exchangeConnection;
            if (!$connection) {
                Log::warning('Bot has no exchange connection', ['bot_id' => $this->bot->id]);
                return;
            }

            // Get trading preset
            $preset = $connection->preset ?? $this->bot->tradingPreset;
            if (!$preset) {
                Log::warning('No trading preset configured', [
                    'bot_id' => $this->bot->id,
                    'connection_id' => $connection->id,
                ]);
                return;
            }

            // Get account info
            $accountInfo = $this->getAccountInfo($connection);
            if (!$accountInfo) {
                Log::warning('Failed to get account info', [
                    'bot_id' => $this->bot->id,
                    'connection_id' => $connection->id,
                ]);
                return;
            }

            // Calculate position size using RiskCalculatorService
            $riskCalculator = app(RiskCalculatorService::class);
            $riskConfig = $this->getRiskConfig($preset);
            
            // Create mock signal for risk calculation
            $signal = (object) [
                'open_price' => $this->marketData[0]['close'] ?? 0,
                'sl' => $this->decision['stop_loss'] ?? null,
                'tp' => $this->decision['take_profit'] ?? null,
                'direction' => $this->decision['direction'],
            ];

            $positionSize = $riskCalculator->calculatePositionSize($signal, $accountInfo, $riskConfig);
            
            // Calculate SL/TP if not provided
            if (!isset($this->decision['stop_loss']) || !isset($this->decision['take_profit'])) {
                $slTp = $this->calculateSlTp($preset, $signal, $positionSize);
                $this->decision['stop_loss'] = $slTp['stop_loss'];
                $this->decision['take_profit'] = $slTp['take_profit'];
            }

            // Validate trade meets risk criteria
            $validation = $riskCalculator->validateTrade($signal, $accountInfo, $riskConfig);
            if (!$validation['valid']) {
                Log::info('Trade rejected by risk validation', [
                    'bot_id' => $this->bot->id,
                    'reason' => $validation['reason'],
                ]);
                return;
            }

            // Prepare execution data
            $executionData = [
                'bot_id' => $this->bot->id,
                'connection_id' => $connection->id,
                'symbol' => $this->marketData[0]['symbol'] ?? '',
                'direction' => $this->decision['direction'],
                'quantity' => $positionSize['lot_size'] ?? $positionSize['volume'] ?? 0.01,
                'stop_loss' => $this->decision['stop_loss'],
                'take_profit' => $this->decision['take_profit'],
                'entry_price' => $this->marketData[0]['close'] ?? 0,
                'risk_amount' => $positionSize['risk_amount'] ?? 0,
                'risk_percent' => $positionSize['risk_percent'] ?? 0,
            ];

            // Dispatch to Execution Worker
            ExecutionJob::dispatch($executionData);

            Log::info('Risk management completed, dispatched to execution', [
                'bot_id' => $this->bot->id,
                'position_size' => $executionData['quantity'],
                'risk_percent' => $executionData['risk_percent'],
            ]);

        } catch (\Exception $e) {
            Log::error('Risk management job failed', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get account information
     */
    protected function getAccountInfo($connection): ?array
    {
        try {
            // Use adapter to get account info
            $adapter = app(\Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory::class)
                ->create($connection->provider, $connection->credentials ?? []);
            
            return $adapter->fetchBalance();
        } catch (\Exception $e) {
            Log::error('Failed to get account info', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get risk configuration from preset
     */
    protected function getRiskConfig($preset): array
    {
        return [
            'position_sizing_strategy' => $preset->position_sizing_strategy ?? 'fixed',
            'position_sizing_value' => $preset->position_sizing_value ?? 0.01,
            'risk_percent' => $preset->risk_percent ?? 1.0,
            'stop_loss_pips' => $preset->stop_loss_pips ?? null,
            'take_profit_pips' => $preset->take_profit_pips ?? null,
            'max_lot_size' => $preset->max_lot_size ?? 10.0,
            'min_lot_size' => $preset->min_lot_size ?? 0.01,
        ];
    }

    /**
     * Calculate SL/TP levels
     */
    protected function calculateSlTp($preset, $signal, array $positionSize): array
    {
        $entryPrice = $signal->open_price;
        $direction = $signal->direction;
        
        // Calculate SL/TP based on preset settings
        $slPips = $preset->stop_loss_pips ?? 20;
        $tpPips = $preset->take_profit_pips ?? 40;
        
        // For crypto, use percentage instead of pips
        $isCrypto = strpos($signal->open_price, '/') !== false || $entryPrice > 1;
        
        if ($isCrypto) {
            // Percentage-based
            $slPercent = $slPips / 100; // Convert pips to percent
            $tpPercent = $tpPips / 100;
            
            if ($direction === 'buy' || $direction === 'long') {
                $stopLoss = $entryPrice * (1 - $slPercent);
                $takeProfit = $entryPrice * (1 + $tpPercent);
            } else {
                $stopLoss = $entryPrice * (1 + $slPercent);
                $takeProfit = $entryPrice * (1 - $tpPercent);
            }
        } else {
            // Pip-based (FX)
            $pipValue = 0.0001; // For most FX pairs
            if (strpos($entryPrice, 'JPY') !== false) {
                $pipValue = 0.01; // For JPY pairs
            }
            
            if ($direction === 'buy' || $direction === 'long') {
                $stopLoss = $entryPrice - ($slPips * $pipValue);
                $takeProfit = $entryPrice + ($tpPips * $pipValue);
            } else {
                $stopLoss = $entryPrice + ($slPips * $pipValue);
                $takeProfit = $entryPrice - ($tpPips * $pipValue);
            }
        }

        return [
            'stop_loss' => round($stopLoss, 5),
            'take_profit' => round($takeProfit, 5),
        ];
    }
}
