<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services;

use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Illuminate\Support\Facades\Log;

/**
 * PositionLimitService
 * 
 * Enforces position limits per connection and per symbol
 */
class PositionLimitService
{
    /**
     * Check if new position is allowed based on limits
     * 
     * @param ExecutionConnection $connection Execution connection
     * @param string|null $symbol Trading symbol (optional, for per-symbol limit)
     * @return array ['allowed' => bool, 'reason' => string|null, 'current_count' => int, 'limit' => int]
     */
    public function checkPositionLimit(ExecutionConnection $connection, ?string $symbol = null): array
    {
        // Check total open positions limit
        $totalOpenPositions = $this->getOpenPositionsCount($connection);
        $maxOpenPositions = $connection->max_open_positions ?? 5;
        
        if ($totalOpenPositions >= $maxOpenPositions) {
            return [
                'allowed' => false,
                'reason' => "Maximum open positions limit reached ({$totalOpenPositions}/{$maxOpenPositions})",
                'current_count' => $totalOpenPositions,
                'limit' => $maxOpenPositions,
                'limit_type' => 'total',
            ];
        }
        
        // Check per-symbol limit if symbol provided
        if ($symbol) {
            $symbolOpenPositions = $this->getOpenPositionsCount($connection, $symbol);
            $maxPositionsPerSymbol = $connection->max_positions_per_symbol ?? 1;
            
            if ($symbolOpenPositions >= $maxPositionsPerSymbol) {
                return [
                    'allowed' => false,
                    'reason' => "Maximum positions per symbol limit reached for {$symbol} ({$symbolOpenPositions}/{$maxPositionsPerSymbol})",
                    'current_count' => $symbolOpenPositions,
                    'limit' => $maxPositionsPerSymbol,
                    'limit_type' => 'per_symbol',
                    'symbol' => $symbol,
                ];
            }
        }
        
        return [
            'allowed' => true,
            'reason' => null,
            'current_count' => $totalOpenPositions,
            'limit' => $maxOpenPositions,
            'limit_type' => 'total',
        ];
    }

    /**
     * Get count of open positions for a connection
     * 
     * @param ExecutionConnection $connection Execution connection
     * @param string|null $symbol Optional symbol filter
     * @return int Number of open positions
     */
    public function getOpenPositionsCount(ExecutionConnection $connection, ?string $symbol = null): int
    {
        try {
            $query = ExecutionPosition::where('connection_id', $connection->id)
                ->where('status', 'open');
            
            if ($symbol) {
                $query->where('symbol', strtoupper($symbol));
            }
            
            return $query->count();
        } catch (\Exception $e) {
            Log::error('PositionLimitService: Failed to get open positions count', [
                'connection_id' => $connection->id,
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
            return 0; // Fail-safe: allow trade if we can't check
        }
    }

    /**
     * Check if trade should be prevented due to position limits
     * 
     * @param ExecutionConnection $connection Execution connection
     * @param string $symbol Trading symbol
     * @return array ['should_prevent' => bool, 'reason' => string|null]
     */
    public function shouldPreventTrade(ExecutionConnection $connection, string $symbol): array
    {
        $limitCheck = $this->checkPositionLimit($connection, $symbol);
        
        if (!$limitCheck['allowed']) {
            return [
                'should_prevent' => true,
                'reason' => $limitCheck['reason'],
            ];
        }
        
        return [
            'should_prevent' => false,
            'reason' => null,
        ];
    }
}

