<?php

namespace Addons\TradingManagement\Shared\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: DataReceived
 * 
 * Dispatched when new market data is fetched from a provider
 * 
 * Listeners:
 * - MarketDataModule: Store data in database
 * - FilterStrategyModule: Evaluate filters
 * - AiAnalysisModule: Analyze with AI
 */
class DataReceived
{
    use Dispatchable, SerializesModels;

    public int $dataConnectionId;
    public string $symbol;
    public string $timeframe;
    public array $candles; // Array of MarketDataDTO
    public string $sourceType;
    public int $timestamp;

    public function __construct(
        int $dataConnectionId,
        string $symbol,
        string $timeframe,
        array $candles,
        string $sourceType
    ) {
        $this->dataConnectionId = $dataConnectionId;
        $this->symbol = $symbol;
        $this->timeframe = $timeframe;
        $this->candles = $candles;
        $this->sourceType = $sourceType;
        $this->timestamp = time();
    }
}

