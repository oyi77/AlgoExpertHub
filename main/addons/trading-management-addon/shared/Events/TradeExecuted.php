<?php

namespace Addons\TradingManagement\Shared\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: TradeExecuted
 * 
 * Dispatched when a trade is successfully executed
 * 
 * Listeners:
 * - PositionMonitoringModule: Start monitoring position
 * - CopyTradingModule: Copy trade to followers
 * - AnalyticsModule: Update statistics
 */
class TradeExecuted
{
    use Dispatchable, SerializesModels;

    public int $executionConnectionId;
    public int $signalId;
    public string $orderId;
    public array $orderData;
    public array $executionData;
    public int $timestamp;

    public function __construct(
        int $executionConnectionId,
        int $signalId,
        string $orderId,
        array $orderData,
        array $executionData = []
    ) {
        $this->executionConnectionId = $executionConnectionId;
        $this->signalId = $signalId;
        $this->orderId = $orderId;
        $this->orderData = $orderData;
        $this->executionData = $executionData;
        $this->timestamp = time();
    }
}

