<?php

namespace Addons\MultiChannelSignalAddon\App\Parsers;

use Addons\MultiChannelSignalAddon\App\Contracts\MessageParserInterface;
use Addons\MultiChannelSignalAddon\App\DTOs\ParsedSignalData;
use Addons\MultiChannelSignalAddon\App\Models\AiParsingProfile;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Illuminate\Support\Facades\Log;

class AiMessageParser implements MessageParserInterface
{
    protected ?AiParsingProfile $profile = null;
    protected int $priority = 50;
    protected $aiConnectionService;

    public function __construct(?AiParsingProfile $profile = null, ?AiConnectionService $aiConnectionService = null)
    {
        $this->profile = $profile;
        $this->aiConnectionService = $aiConnectionService ?? app(AiConnectionService::class);
        
        // If no profile provided, try to get first enabled profile
        if (!$this->profile) {
            $this->profile = AiParsingProfile::with('aiConnection')
                ->enabled()
                ->byPriority()
                ->first();
        }
        
        // Set priority from profile if available
        if ($this->profile) {
            $this->priority = $this->profile->priority ?? 50;
        }
    }

    public function canParse(string $message): bool
    {
        // Only use AI parser if profile is available and usable
        if (!$this->profile || !$this->profile->isUsable()) {
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
            // Build parsing prompt
            $prompt = $this->buildParsingPrompt($message);

            // Get settings from profile
            $settings = $this->profile->getEffectiveSettings();

            // Execute AI call through centralized service
            $result = $this->aiConnectionService->execute(
                connectionId: $this->profile->ai_connection_id,
                prompt: $prompt,
                options: $settings,
                feature: 'signal_parsing'
            );

            if (!$result['success'] || empty($result['response'])) {
                return null;
            }

            // Parse JSON response
            $parsedData = $this->parseAiResponse($result['response']);
            if (!$parsedData) {
                return null;
            }

            return $this->buildParsedData($parsedData, $message);

        } catch (\Exception $e) {
            Log::error("AI parser error: " . $e->getMessage(), [
                'exception' => $e,
                'message_preview' => substr($message, 0, 100),
                'profile_id' => $this->profile->id ?? null,
                'connection_id' => $this->profile->ai_connection_id ?? null,
            ]);
            return null;
        }
    }

    /**
     * Build parsing prompt for AI
     */
    protected function buildParsingPrompt(string $message): string
    {
        // Use custom parsing prompt from profile if available
        if ($this->profile->parsing_prompt) {
            return str_replace('{message}', $message, $this->profile->parsing_prompt);
        }

        // Default parsing prompt
        return <<<PROMPT
You are a trading signal parser. Extract trading signal information from the following message and return ONLY valid JSON.

Required fields:
- currency_pair (string): The trading pair (e.g., "EUR/USD", "BTC/USDT", "GOLD")
- direction (string): "buy" or "sell"

Optional fields:
- open_price (float): Entry price (use 0 if not specified, meaning market entry)
- sl (float): Stop loss price
- tp (float or array): Take profit price(s)
- sl_percentage (float): Stop loss as percentage
- tp_percentage (float or array): Take profit as percentage(s)
- timeframe (string): Trading timeframe (e.g., "1H", "4H", "1D")
- title (string): Short title for the signal
- description (string): Additional description

Message to parse:
{$message}

Return only valid JSON with no additional text.
PROMPT;
    }

    /**
     * Parse AI response to extract structured data
     */
    protected function parseAiResponse(string $response): ?array
    {
        // Try to extract JSON from response
        $response = trim($response);
        
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        $response = trim($response);

        try {
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Failed to parse AI JSON response", [
                    'error' => json_last_error_msg(),
                    'response' => substr($response, 0, 200),
                ]);
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error("AI response parsing error", [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 200),
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
            'pattern_used' => 'AI Parser (' . ($this->profile->name ?? 'Unknown') . ')',
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

