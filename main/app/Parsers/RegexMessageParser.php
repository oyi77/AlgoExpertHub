<?php

namespace App\Parsers;

use App\Contracts\MessageParserInterface;
use App\DTOs\ParsedSignalData;

class RegexMessageParser implements MessageParserInterface
{
    /**
     * Default regex patterns for common signal formats.
     *
     * @var array
     */
    protected $patterns = [
        'currency_pair' => [
            '/([A-Z]{2,10}\/[A-Z]{2,10})/',
            '/([A-Z]{2,10}-[A-Z]{2,10})/',
            '/([A-Z]{2,10}USD)/i',
            '/([A-Z]{2,10}USDT)/i',
        ],
        'direction' => [
            '/(BUY|SELL)/i',
            '/(LONG|SHORT)/i',
            '/(UP|DOWN)/i',
        ],
        'open_price' => [
            '/ENTRY[:\s]*([\d,]+\.?\d*)/i',
            '/ENTRY[:\s]*\$?([\d,]+\.?\d*)/i',
            '/PRICE[:\s]*([\d,]+\.?\d*)/i',
            '/@([\d,]+\.?\d*)/',
        ],
        'sl' => [
            '/SL[:\s]*([\d,]+\.?\d*)/i',
            '/STOP[:\s]*LOSS[:\s]*([\d,]+\.?\d*)/i',
            '/STOP[:\s]*([\d,]+\.?\d*)/i',
        ],
        'tp' => [
            '/TP[:\s]*([\d,]+\.?\d*)/i',
            '/TAKE[:\s]*PROFIT[:\s]*([\d,]+\.?\d*)/i',
            '/TARGET[:\s]*([\d,]+\.?\d*)/i',
        ],
        'timeframe' => [
            '/(M1|M5|M15|M30|H1|H4|D1|W1)/i',
            '/(1MIN|5MIN|15MIN|30MIN|1H|4H|1D|1W)/i',
            '/(1M|5M|15M|30M|1H|4H|1D|1W)/i',
        ],
    ];

    /**
     * Check if the parser can parse the given message.
     *
     * @param string $message
     * @return bool
     */
    public function canParse(string $message): bool
    {
        // Try to find at least currency pair and direction
        $hasCurrencyPair = false;
        $hasDirection = false;

        foreach ($this->patterns['currency_pair'] as $pattern) {
            if (preg_match($pattern, $message)) {
                $hasCurrencyPair = true;
                break;
            }
        }

        foreach ($this->patterns['direction'] as $pattern) {
            if (preg_match($pattern, $message)) {
                $hasDirection = true;
                break;
            }
        }

        return $hasCurrencyPair && $hasDirection;
    }

    /**
     * Parse the message and extract signal data.
     *
     * @param string $message
     * @return ParsedSignalData|null
     */
    public function parse(string $message): ?ParsedSignalData
    {
        if (!$this->canParse($message)) {
            return null;
        }

        $data = [];
        $confidence = 0;

        // Extract currency pair
        foreach ($this->patterns['currency_pair'] as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $data['currency_pair'] = strtoupper($matches[1]);
                $confidence += 15;
                break;
            }
        }

        // Extract direction
        foreach ($this->patterns['direction'] as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $direction = strtoupper($matches[1]);
                // Normalize direction
                if (in_array($direction, ['BUY', 'LONG', 'UP'])) {
                    $data['direction'] = 'buy';
                } elseif (in_array($direction, ['SELL', 'SHORT', 'DOWN'])) {
                    $data['direction'] = 'sell';
                }
                $confidence += 15;
                break;
            }
        }

        // Extract open price
        foreach ($this->patterns['open_price'] as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $price = str_replace(',', '', $matches[1]);
                $data['open_price'] = (float) $price;
                $confidence += 20;
                break;
            }
        }

        // Extract stop loss
        foreach ($this->patterns['sl'] as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $sl = str_replace(',', '', $matches[1]);
                $data['sl'] = (float) $sl;
                $confidence += 15;
                break;
            }
        }

        // Extract take profit
        foreach ($this->patterns['tp'] as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $tp = str_replace(',', '', $matches[1]);
                $data['tp'] = (float) $tp;
                $confidence += 15;
                break;
            }
        }

        // Extract timeframe
        foreach ($this->patterns['timeframe'] as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $data['timeframe'] = strtoupper($matches[1]);
                $confidence += 10;
                break;
            }
        }

        // Extract title (first line or first 50 chars)
        $lines = explode("\n", $message);
        if (!empty($lines[0])) {
            $data['title'] = substr(trim($lines[0]), 0, 100);
        }

        // Extract description (remaining text)
        if (count($lines) > 1) {
            $data['description'] = implode("\n", array_slice($lines, 1));
        }

        // Cap confidence at 100
        $data['confidence'] = min($confidence, 100);

        return new ParsedSignalData($data);
    }

    /**
     * Get the parser name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'RegexParser';
    }

    /**
     * Get the parser priority (higher priority tried first).
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 100;
    }
}

