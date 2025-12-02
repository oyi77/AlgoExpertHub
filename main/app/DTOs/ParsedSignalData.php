<?php

namespace App\DTOs;

/**
 * Data Transfer Object for parsed signal data.
 * 
 * Immutable value object containing parsed signal information.
 */
class ParsedSignalData
{
    /**
     * @var string|null
     */
    public $currency_pair;

    /**
     * @var float|null
     */
    public $open_price;

    /**
     * @var float|null
     */
    public $sl;

    /**
     * @var float|null
     */
    public $tp;

    /**
     * @var string|null
     */
    public $direction;

    /**
     * @var string|null
     */
    public $timeframe;

    /**
     * @var string|null
     */
    public $market;

    /**
     * @var int
     */
    public $confidence;

    /**
     * @var string|null
     */
    public $title;

    /**
     * @var string|null
     */
    public $description;

    /**
     * ParsedSignalData constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->currency_pair = $data['currency_pair'] ?? null;
        $this->open_price = isset($data['open_price']) ? (float) $data['open_price'] : null;
        $this->sl = isset($data['sl']) ? (float) $data['sl'] : null;
        $this->tp = isset($data['tp']) ? (float) $data['tp'] : null;
        $this->direction = $data['direction'] ?? null;
        $this->timeframe = $data['timeframe'] ?? null;
        $this->market = $data['market'] ?? null;
        $this->confidence = (int) ($data['confidence'] ?? 0);
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
    }

    /**
     * Check if the parsed data is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // Minimum required fields for a valid signal
        return !empty($this->currency_pair) 
            && !empty($this->open_price) 
            && !empty($this->direction);
    }

    /**
     * Get missing required fields.
     *
     * @return array
     */
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

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'currency_pair' => $this->currency_pair,
            'open_price' => $this->open_price,
            'sl' => $this->sl,
            'tp' => $this->tp,
            'direction' => $this->direction,
            'timeframe' => $this->timeframe,
            'market' => $this->market,
            'confidence' => $this->confidence,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}

