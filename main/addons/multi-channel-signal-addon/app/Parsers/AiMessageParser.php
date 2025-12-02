<?php

namespace Addons\MultiChannelSignalAddon\App\Parsers;

use Addons\MultiChannelSignalAddon\App\Contracts\MessageParserInterface;
use Addons\MultiChannelSignalAddon\App\DTOs\ParsedSignalData;
use Addons\MultiChannelSignalAddon\App\Models\AiConfiguration;
use Addons\MultiChannelSignalAddon\App\Services\AiProviderFactory;
use Illuminate\Support\Facades\Log;

class AiMessageParser implements MessageParserInterface
{
    protected ?AiConfiguration $config = null;
    protected int $priority = 50;

    public function __construct(?AiConfiguration $config = null)
    {
        $this->config = $config;
        
        // If no config provided, try to get first active config
        if (!$this->config) {
            $this->config = AiConfiguration::getActive()->first();
        }
        
        // Set priority from config if available
        if ($this->config) {
            $this->priority = $this->config->priority ?? 50;
        }
    }

    public function canParse(string $message): bool
    {
        // Only use AI parser if configuration is available and enabled
        if (!$this->config || !$this->config->enabled) {
            return false;
        }

        // Check if message looks like it might contain signal data
        // Basic heuristics: contains currency pair indicators or trading terms
        $hasTradingTerms = preg_match('/(BUY|SELL|LONG|SHORT|ENTRY|SL|TP|STOP|TAKE|PROFIT|LOSS)/i', $message);
        $hasCurrencyPair = preg_match('/([A-Z]{2,10}[\/\-_][A-Z]{2,10}|[A-Z]{2,10}USD|[A-Z]{2,10}USDT|Gold|XAU)/i', $message);
        $hasNumbers = preg_match('/[\d,]+\.?\d*/', $message);

        return ($hasTradingTerms || $hasCurrencyPair) && $hasNumbers;
    }

    public function parse(string $message): ?ParsedSignalData
    {
        if (!$this->canParse($message)) {
            return null;
        }

        try {
            $provider = AiProviderFactory::createFromConfig($this->config);
            if (!$provider) {
                return null;
            }

            $parsedData = $provider->parse($message, $this->config);
            if (!$parsedData) {
                return null;
            }

            return $this->buildParsedData($parsedData, $message);

        } catch (\Exception $e) {
            Log::error("AI parser error: " . $e->getMessage(), [
                'exception' => $e,
                'message_preview' => substr($message, 0, 100),
                'provider' => $this->config->provider ?? 'unknown',
            ]);
            return null;
        }
    }

    /**
     * Build ParsedSignalData from AI response.
     */
    protected function buildParsedData(array $data, string $originalMessage): ?ParsedSignalData
    {
        // Validate required fields (open_price can be 0 for market entry)
        if (empty($data['currency_pair']) || empty($data['direction'])) {
            return null;
        }

        // Normalize direction
        $direction = strtolower($data['direction']);
        if (!in_array($direction, ['buy', 'sell'])) {
            if (in_array(strtoupper($direction), ['BUY', 'LONG', 'UP'])) {
                $direction = 'buy';
            } elseif (in_array(strtoupper($direction), ['SELL', 'SHORT', 'DOWN'])) {
                $direction = 'sell';
            } else {
                return null;
            }
        }

        // Normalize currency pair
        $currencyPair = strtoupper($data['currency_pair']);
        $currencyPair = str_replace(['-', '_'], '/', $currencyPair);

        // Build data array
        $parsedData = [
            'currency_pair' => $currencyPair,
            'direction' => $direction,
            'open_price' => isset($data['open_price']) ? (float) $data['open_price'] : 0,
            'sl' => isset($data['sl']) ? (float) $data['sl'] : null,
            'tp' => isset($data['tp']) ? (float) $data['tp'] : null,
            'tp_multiple' => isset($data['tp_multiple']) && is_array($data['tp_multiple']) ? $data['tp_multiple'] : null,
            'sl_percentage' => isset($data['sl_percentage']) ? (float) $data['sl_percentage'] : null,
            'tp_percentage' => isset($data['tp_percentage']) ? (is_array($data['tp_percentage']) ? $data['tp_percentage'] : (float) $data['tp_percentage']) : null,
            'timeframe' => $data['timeframe'] ?? null,
            'title' => $data['title'] ?? "Signal: {$currencyPair} {$direction}",
            'description' => $data['description'] ?? $originalMessage,
            'confidence' => $this->calculateConfidence($data),
            'pattern_used' => 'AI Parser (' . ($this->config->name ?? $this->config->provider) . ')',
        ];

        // Handle market entry (open_price = 0)
        if ($parsedData['open_price'] == 0) {
            $parsedData['needs_price_fetch'] = true;
        }

        return new ParsedSignalData($parsedData);
    }

    /**
     * Calculate confidence score based on extracted data.
     */
    protected function calculateConfidence(array $data): float
    {
        $confidence = 0;

        // Required fields
        if (!empty($data['currency_pair'])) $confidence += 20;
        if (!empty($data['direction'])) $confidence += 20;
        if (isset($data['open_price'])) $confidence += 20; // Can be 0 for market entry

        // Optional fields
        if (!empty($data['sl']) || !empty($data['sl_percentage'])) $confidence += 15;
        if (!empty($data['tp']) || !empty($data['tp_percentage']) || !empty($data['tp_multiple'])) $confidence += 15;
        if (!empty($data['timeframe'])) $confidence += 10;

        return min($confidence, 100);
    }

    public function getName(): string
    {
        return 'AiMessageParser';
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}

