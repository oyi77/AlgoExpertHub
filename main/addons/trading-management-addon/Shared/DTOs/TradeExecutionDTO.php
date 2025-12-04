<?php

namespace Addons\TradingManagement\Shared\DTOs;

use App\Models\Signal;

/**
 * Data Transfer Object for Trade Execution
 * 
 * Standardizes trade execution data across all exchanges/brokers
 */
class TradeExecutionDTO
{
    public Signal $signal;
    public string $symbol;
    public string $side; // buy, sell
    public float $lotSize;
    public ?float $entryPrice;
    public ?float $stopLoss;
    public ?float $takeProfit;
    public array $multipleTakeProfits; // For multi-TP
    public string $orderType; // market, limit
    public array $metadata;

    public function __construct(array $data)
    {
        $this->signal = $data['signal'];
        $this->symbol = $data['symbol'];
        $this->side = $data['side'];
        $this->lotSize = (float) $data['lot_size'];
        $this->entryPrice = isset($data['entry_price']) ? (float) $data['entry_price'] : null;
        $this->stopLoss = isset($data['stop_loss']) ? (float) $data['stop_loss'] : null;
        $this->takeProfit = isset($data['take_profit']) ? (float) $data['take_profit'] : null;
        $this->multipleTakeProfits = $data['multiple_take_profits'] ?? [];
        $this->orderType = $data['order_type'] ?? 'market';
        $this->metadata = $data['metadata'] ?? [];
    }

    /**
     * Convert to array for logging/storage
     */
    public function toArray(): array
    {
        return [
            'signal_id' => $this->signal->id,
            'symbol' => $this->symbol,
            'side' => $this->side,
            'lot_size' => $this->lotSize,
            'entry_price' => $this->entryPrice,
            'stop_loss' => $this->stopLoss,
            'take_profit' => $this->takeProfit,
            'multiple_take_profits' => $this->multipleTakeProfits,
            'order_type' => $this->orderType,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get parameters for exchange adapter
     */
    public function getExchangeParams(): array
    {
        $params = [];

        if ($this->stopLoss) {
            $params['stopLoss'] = ['price' => $this->stopLoss];
        }

        if ($this->takeProfit) {
            $params['takeProfit'] = ['price' => $this->takeProfit];
        }

        if (!empty($this->multipleTakeProfits)) {
            $params['multipleTakeProfits'] = $this->multipleTakeProfits;
        }

        return array_merge($params, $this->metadata);
    }
}

