<?php

namespace Addons\MultiChannelSignalAddon\App\DTOs;

class ParsedSignalData
{
    public ?string $currency_pair;
    public ?float $open_price;
    public ?float $sl;
    public ?float $tp;
    public ?float $sl_percentage;
    public ?array $tp_percentage;
    public ?string $direction;
    public ?string $timeframe;
    public ?string $market;
    public int $confidence;
    public ?string $title;
    public ?string $description;
    public bool $needs_price_fetch;

    public function __construct(array $data = [])
    {
        $this->currency_pair = $data['currency_pair'] ?? null;
        $this->open_price = isset($data['open_price']) ? (float) $data['open_price'] : null;
        $this->sl = isset($data['sl']) ? (float) $data['sl'] : null;
        $this->tp = isset($data['tp']) ? (float) $data['tp'] : null;
        $this->sl_percentage = isset($data['sl_percentage']) ? (float) $data['sl_percentage'] : null;
        $this->tp_percentage = isset($data['tp_percentage']) && is_array($data['tp_percentage']) ? $data['tp_percentage'] : null;
        $this->direction = $data['direction'] ?? null;
        $this->timeframe = $data['timeframe'] ?? null;
        $this->market = $data['market'] ?? null;
        $this->confidence = (int) ($data['confidence'] ?? 0);
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->needs_price_fetch = isset($data['needs_price_fetch']) ? (bool) $data['needs_price_fetch'] : false;
    }

    public function isValid(): bool
    {
        return !empty($this->currency_pair)
            && !empty($this->open_price)
            && !empty($this->direction);
    }

    public function getMissingFields(): array
    {
        $missing = [];

        if (empty($this->currency_pair)) {
            $missing[] = 'currency_pair';
        }
        if (empty($this->open_price)) {
            $missing[] = 'open_price';
        }
        if (empty($this->direction)) {
            $missing[] = 'direction';
        }

        return $missing;
    }

    public function toArray(): array
    {
        return [
            'currency_pair' => $this->currency_pair,
            'open_price' => $this->open_price,
            'sl' => $this->sl,
            'tp' => $this->tp,
            'sl_percentage' => $this->sl_percentage,
            'tp_percentage' => $this->tp_percentage,
            'direction' => $this->direction,
            'timeframe' => $this->timeframe,
            'market' => $this->market,
            'confidence' => $this->confidence,
            'title' => $this->title,
            'description' => $this->description,
            'needs_price_fetch' => $this->needs_price_fetch,
        ];
    }

    /**
     * Calculate TP/SL from percentages if entry price is available.
     */
    public function calculatePricesFromPercentages(float $entryPrice): void
    {
        if ($this->tp_percentage && !empty($this->tp_percentage)) {
            $tpPercent = $this->tp_percentage[0];
            if ($this->direction === 'buy') {
                $this->tp = $entryPrice * (1 + $tpPercent / 100);
            } else {
                $this->tp = $entryPrice * (1 - $tpPercent / 100);
            }
        }

        if ($this->sl_percentage !== null) {
            $slPercent = $this->sl_percentage;
            if ($this->direction === 'buy') {
                $this->sl = $entryPrice * (1 - $slPercent / 100);
            } else {
                $this->sl = $entryPrice * (1 + $slPercent / 100);
            }
        }
    }
}
