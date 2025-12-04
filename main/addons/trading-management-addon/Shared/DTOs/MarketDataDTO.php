<?php

namespace Addons\TradingManagement\Shared\DTOs;

/**
 * Data Transfer Object for Market Data
 * 
 * Standardizes market data format across all providers
 */
class MarketDataDTO
{
    public string $symbol;
    public string $timeframe;
    public int $timestamp;
    public float $open;
    public float $high;
    public float $low;
    public float $close;
    public ?float $volume;
    public string $sourceType;

    public function __construct(array $data)
    {
        $this->symbol = $data['symbol'];
        $this->timeframe = $data['timeframe'];
        $this->timestamp = $data['timestamp'];
        $this->open = (float) $data['open'];
        $this->high = (float) $data['high'];
        $this->low = (float) $data['low'];
        $this->close = (float) $data['close'];
        $this->volume = isset($data['volume']) ? (float) $data['volume'] : null;
        $this->sourceType = $data['source_type'] ?? 'unknown';
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'timeframe' => $this->timeframe,
            'timestamp' => $this->timestamp,
            'open' => $this->open,
            'high' => $this->high,
            'low' => $this->low,
            'close' => $this->close,
            'volume' => $this->volume,
            'source_type' => $this->sourceType,
        ];
    }

    /**
     * Create from CCXT format [[timestamp, open, high, low, close, volume], ...]
     */
    public static function fromCcxtCandle(string $symbol, string $timeframe, array $candle, string $sourceType = 'ccxt'): self
    {
        return new self([
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'timestamp' => $candle[0] / 1000, // CCXT uses milliseconds
            'open' => $candle[1],
            'high' => $candle[2],
            'low' => $candle[3],
            'close' => $candle[4],
            'volume' => $candle[5] ?? null,
            'source_type' => $sourceType,
        ]);
    }

    /**
     * Create from mtapi.io format
     */
    public static function fromMtapiCandle(string $symbol, string $timeframe, array $candle, string $sourceType = 'mtapi'): self
    {
        return new self([
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'timestamp' => $candle['time'] ?? $candle['timestamp'],
            'open' => $candle['open'],
            'high' => $candle['high'],
            'low' => $candle['low'],
            'close' => $candle['close'],
            'volume' => $candle['tick_volume'] ?? $candle['volume'] ?? null,
            'source_type' => $sourceType,
        ]);
    }
}

