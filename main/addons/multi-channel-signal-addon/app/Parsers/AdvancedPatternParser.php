<?php

namespace Addons\MultiChannelSignalAddon\App\Parsers;

use Addons\MultiChannelSignalAddon\App\Contracts\MessageParserInterface;
use Addons\MultiChannelSignalAddon\App\DTOs\ParsedSignalData;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern;
use Illuminate\Support\Facades\Log;

class AdvancedPatternParser implements MessageParserInterface
{
    protected ?ChannelSource $channelSource = null;
    protected array $patterns = [];

    public function __construct(?ChannelSource $channelSource = null)
    {
        $this->channelSource = $channelSource;
        $this->loadPatterns();
    }

    /**
     * Load patterns from database for this channel.
     */
    protected function loadPatterns(): void
    {
        $query = MessageParsingPattern::active();

        if ($this->channelSource) {
            $query->forChannel($this->channelSource->id);
        } else {
            // Load only global patterns
            $query->whereNull('channel_source_id');
        }

        $this->patterns = $query->orderedByPriority()->get()->toArray();
    }

    public function canParse(string $message): bool
    {
        if (empty($this->patterns)) {
            return false;
        }

        // Try each pattern to see if it can parse
        foreach ($this->patterns as $pattern) {
            if ($this->testPattern($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    public function parse(string $message): ?ParsedSignalData
    {
        if (empty($this->patterns)) {
            return null;
        }

        $bestResult = null;
        $highestConfidence = 0;
        $matchedPattern = null;

        // Try each pattern in priority order
        foreach ($this->patterns as $pattern) {
            $result = $this->applyPattern($pattern, $message);
            
            if ($result && $result->confidence > $highestConfidence) {
                $bestResult = $result;
                $highestConfidence = $result->confidence;
                $matchedPattern = $pattern;
            }
        }

        // Record success/failure
        if ($matchedPattern && $bestResult) {
            $patternModel = MessageParsingPattern::find($matchedPattern['id']);
            if ($patternModel) {
                $patternModel->incrementSuccess();
            }
        }

        return $bestResult;
    }

    /**
     * Test if pattern can potentially parse the message.
     */
    protected function testPattern(array $pattern, string $message): bool
    {
        $config = $pattern['pattern_config'] ?? [];
        $type = $pattern['pattern_type'] ?? 'regex';

        if ($type === 'regex') {
            return $this->testRegexPattern($config, $message);
        } elseif ($type === 'template') {
            return $this->testTemplatePattern($config, $message);
        }

        return false;
    }

    /**
     * Apply pattern to extract signal data.
     */
    protected function applyPattern(array $pattern, string $message): ?ParsedSignalData
    {
        $config = $pattern['pattern_config'] ?? [];
        $type = $pattern['pattern_type'] ?? 'regex';

        try {
            if ($type === 'regex') {
                return $this->applyRegexPattern($config, $message, $pattern);
            } elseif ($type === 'template') {
                return $this->applyTemplatePattern($config, $message, $pattern);
            }
        } catch (\Exception $e) {
            Log::warning("Pattern parsing failed: " . $e->getMessage(), [
                'pattern_id' => $pattern['id'],
                'pattern_name' => $pattern['name'],
            ]);

            // Record failure
            $patternModel = MessageParsingPattern::find($pattern['id']);
            if ($patternModel) {
                $patternModel->incrementFailure();
            }
        }

        return null;
    }

    /**
     * Test regex pattern.
     */
    protected function testRegexPattern(array $config, string $message): bool
    {
        $requiredFields = $config['required_fields'] ?? ['currency_pair', 'direction'];
        $patterns = $config['patterns'] ?? [];

        $foundFields = 0;
        foreach ($requiredFields as $field) {
            if (isset($patterns[$field])) {
                foreach ($patterns[$field] as $pattern) {
                    if (preg_match($pattern, $message)) {
                        $foundFields++;
                        break;
                    }
                }
            }
        }

        return $foundFields >= count($requiredFields);
    }

    /**
     * Apply regex pattern.
     */
    protected function applyRegexPattern(array $config, string $message, array $pattern): ?ParsedSignalData
    {
        $patterns = $config['patterns'] ?? [];
        $fieldMappings = $config['field_mappings'] ?? [];
        $confidenceWeights = $config['confidence_weights'] ?? [];

        $data = [];
        $confidence = 0;

        // Extract currency pair or symbol (indices, stocks, etc.)
        if (isset($patterns['currency_pair'])) {
            foreach ($patterns['currency_pair'] as $regex) {
                if (preg_match($regex, $message, $matches)) {
                    $pair = $this->normalizeCurrencyPair($matches[1] ?? $matches[0]);
                    $data['currency_pair'] = $pair;
                    $confidence += $confidenceWeights['currency_pair'] ?? 15;
                    break;
                }
            }
        }

        // Extract symbol (for indices, stocks, etc. that aren't currency pairs)
        if (isset($patterns['symbol'])) {
            foreach ($patterns['symbol'] as $regex) {
                if (preg_match($regex, $message, $matches)) {
                    $symbol = strtoupper(trim($matches[1] ?? $matches[0]));
                    // If no currency_pair found, use symbol as currency_pair
                    if (empty($data['currency_pair'])) {
                        $data['currency_pair'] = $symbol;
                    }
                    $data['symbol'] = $symbol;
                    $confidence += $confidenceWeights['symbol'] ?? 15;
                    break;
                }
            }
        }

        // Extract direction
        if (isset($patterns['direction'])) {
            foreach ($patterns['direction'] as $regex) {
                if (preg_match($regex, $message, $matches)) {
                    // Handle patterns that might capture symbol and direction together
                    $directionValue = null;
                    if (isset($matches[2])) {
                        // Pattern captured symbol in group 1, direction in group 2
                        $directionValue = $matches[2];
                    } elseif (isset($matches[1])) {
                        $directionValue = $matches[1];
                    } else {
                        $directionValue = $matches[0];
                    }
                    
                    $direction = $this->normalizeDirection($directionValue);
                    $data['direction'] = $direction;
                    $confidence += $confidenceWeights['direction'] ?? 15;
                    break;
                }
            }
        }

        // Extract open price (supports price ranges like 4081-4082)
        if (isset($patterns['open_price'])) {
            foreach ($patterns['open_price'] as $regex) {
                if (preg_match($regex, $message, $matches)) {
                    // Check if it's a price range (e.g., 4081-4082)
                    // Pattern might capture both prices in separate groups
                    if (isset($matches[2]) && is_numeric($matches[1]) && is_numeric($matches[2])) {
                        // Price range captured
                        $price1 = (float) $matches[1];
                        $price2 = (float) $matches[2];
                        // Use average of range
                        $data['open_price'] = ($price1 + $price2) / 2;
                        $data['open_price_range'] = ['min' => min($price1, $price2), 'max' => max($price1, $price2)];
                        $confidence += $confidenceWeights['open_price'] ?? 20;
                        break;
                    } else {
                        // Single price or check if range is in the value
                        $priceValue = $matches[1] ?? $matches[0];
                        // Check if it's a range in the value itself
                        if (preg_match('/([\d.]+)\s*-\s*([\d.]+)/', $priceValue, $rangeMatch)) {
                            $price1 = (float) $rangeMatch[1];
                            $price2 = (float) $rangeMatch[2];
                            $data['open_price'] = ($price1 + $price2) / 2;
                            $data['open_price_range'] = ['min' => min($price1, $price2), 'max' => max($price1, $price2)];
                            $confidence += $confidenceWeights['open_price'] ?? 20;
                            break;
                        } else {
                            $price = $this->extractPrice($priceValue);
                            if ($price > 0) {
                                $data['open_price'] = $price;
                                $confidence += $confidenceWeights['open_price'] ?? 20;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Extract stop loss (absolute price or percentage)
        if (isset($patterns['sl'])) {
            foreach ($patterns['sl'] as $regex) {
                if (preg_match($regex, $message, $matches)) {
                    $slValue = $matches[1] ?? $matches[0];
                    // Check if it's a percentage
                    if (preg_match('/([\d.]+)\s*%/', $slValue, $percentMatch)) {
                        $data['sl_percentage'] = (float) $percentMatch[1];
                        $confidence += $confidenceWeights['sl'] ?? 15;
                    } else {
                        $sl = $this->extractPrice($slValue);
                        if ($sl > 0) {
                            $data['sl'] = $sl;
                            $confidence += $confidenceWeights['sl'] ?? 15;
                        }
                    }
                    break;
                }
            }
        }

        // Extract take profit (absolute price or percentage, support multiple TP)
        if (isset($patterns['tp'])) {
            $tps = [];
            $tpOrdered = []; // For TP1, TP2, TP3, etc.
            
            foreach ($patterns['tp'] as $regex) {
                // First try to match TP1, TP2, TP3 format with named groups
                if (preg_match_all('/TP\s*(\d+)\s*[:\s]*([\d.]+)/i', $message, $numberedMatches, PREG_SET_ORDER)) {
                    foreach ($numberedMatches as $match) {
                        $tpLevel = $match[1]; // TP level number (1, 2, 3, etc.)
                        $tpValue = $match[2]; // TP price value
                        $tp = $this->extractPrice($tpValue);
                        if ($tp > 0) {
                            $tps[] = $tp;
                            $tpOrdered['TP' . $tpLevel] = $tp;
                        }
                    }
                }
                
                // Match TP MAX format
                if (preg_match_all('/TP\s*MAX\s*[:\s]*([\d.]+)/i', $message, $maxMatches, PREG_SET_ORDER)) {
                    foreach ($maxMatches as $match) {
                        $tpValue = $match[1];
                        $tp = $this->extractPrice($tpValue);
                        if ($tp > 0 && !in_array($tp, $tps)) {
                            $tps[] = $tp;
                            $tpOrdered['TP_MAX'] = $tp;
                        }
                    }
                }
                
                // Match generic TP format (fallback)
                if (preg_match_all($regex, $message, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $tpValue = $match[1] ?? $match[0];
                        
                        // Skip if already captured by TP1/TP2/TP MAX patterns
                        if (preg_match('/TP\s*(\d+|MAX)/i', $match[0] ?? '')) {
                            continue;
                        }
                        
                        // Check if it's a percentage
                        if (preg_match('/([\d.]+)\s*%/', $tpValue, $percentMatch)) {
                            if (!isset($data['tp_percentage'])) {
                                $data['tp_percentage'] = [];
                            }
                            $data['tp_percentage'][] = (float) $percentMatch[1];
                        } else {
                            $tp = $this->extractPrice($tpValue);
                            if ($tp > 0 && !in_array($tp, $tps)) {
                                $tps[] = $tp;
                            }
                        }
                    }
                }
            }
            
            // Sort TPs by price (for SELL: descending, for BUY: ascending)
            if (!empty($tps)) {
                $direction = $data['direction'] ?? 'buy';
                if ($direction === 'sell') {
                    rsort($tps); // Descending for SELL (highest TP first)
                } else {
                    sort($tps); // Ascending for BUY (lowest TP first)
                }
                
                // Use first TP as primary (closest TP for the direction)
                $data['tp'] = $tps[0];
                $data['tp_multiple'] = $tps; // Store all TPs
                
                // Store ordered TPs if available (TP1, TP2, TP3, TP MAX)
                if (!empty($tpOrdered)) {
                    $data['tp_ordered'] = $tpOrdered;
                }
                
                $confidence += $confidenceWeights['tp'] ?? 15;
            }
            // Note: tp_percentage is stored separately and will be calculated when entry price is available
            if (!empty($data['tp_percentage'])) {
                $confidence += $confidenceWeights['tp'] ?? 15;
            }
        }

        // Extract timeframe
        if (isset($patterns['timeframe'])) {
            foreach ($patterns['timeframe'] as $regex) {
                if (preg_match($regex, $message, $matches)) {
                    $data['timeframe'] = $this->normalizeTimeframe($matches[1] ?? $matches[0]);
                    $confidence += $confidenceWeights['timeframe'] ?? 10;
                    break;
                }
            }
        }

        // Extract title and description using line-based patterns
        if (isset($patterns['title'])) {
            foreach ($patterns['title'] as $regex) {
                if (preg_match($regex, $message, $matches)) {
                    $data['title'] = trim($matches[1] ?? $matches[0]);
                    break;
                }
            }
        }

        if (empty($data['title'])) {
            $lines = explode("\n", $message);
            if (!empty($lines[0])) {
                $data['title'] = substr(trim($lines[0]), 0, 100);
            }
        }

        if (isset($patterns['description'])) {
            foreach ($patterns['description'] as $regex) {
                if (preg_match($regex, $message, $matches)) {
                    $data['description'] = trim($matches[1] ?? $matches[0]);
                    break;
                }
            }
        }

        if (empty($data['description'])) {
            $lines = explode("\n", $message);
            if (count($lines) > 1) {
                $data['description'] = implode("\n", array_slice($lines, 1));
            }
        }

        // Validate required fields
        // Note: open_price might not be present in some formats (percentage-based TP/SL)
        // When no entry price is specified, it means market entry at current price
        $hasRequiredFields = !empty($data['currency_pair']) && !empty($data['direction']);
        $hasPriceOrPercentage = !empty($data['open_price']) || 
                                (!empty($data['tp_percentage']) || !empty($data['sl_percentage']));
        
        if (!$hasRequiredFields || (!$hasPriceOrPercentage && empty($data['open_price']))) {
            return null;
        }

        // If we have percentage-based TP/SL but no open_price, it means market entry
        // Mark it to fetch current market price later
        if (empty($data['open_price']) && (!empty($data['tp_percentage']) || !empty($data['sl_percentage']))) {
            $data['open_price'] = 0; // Will be replaced with current market price
            $data['needs_price_fetch'] = true;
        }

        $data['confidence'] = min($confidence, 100);
        $data['pattern_used'] = $pattern['name'] ?? 'Unknown';
        $data['pattern_id'] = $pattern['id'] ?? null;

        return new ParsedSignalData($data);
    }

    /**
     * Test template pattern (line-based or structured format).
     */
    protected function testTemplatePattern(array $config, string $message): bool
    {
        $template = $config['template'] ?? '';
        $requiredFields = $config['required_fields'] ?? [];

        if (empty($template)) {
            return false;
        }

        // Simple template matching - check if message structure matches
        $lines = explode("\n", trim($message));
        $templateLines = explode("\n", trim($template));

        // Check if number of lines match (approximately)
        if (abs(count($lines) - count($templateLines)) > 2) {
            return false;
        }

        // Check for required keywords/patterns
        $foundFields = 0;
        foreach ($requiredFields as $field) {
            $fieldPatterns = $config['field_patterns'][$field] ?? [];
            foreach ($fieldPatterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    $foundFields++;
                    break;
                }
            }
        }

        return $foundFields >= count($requiredFields);
    }

    /**
     * Apply template pattern.
     */
    protected function applyTemplatePattern(array $config, string $message, array $pattern): ?ParsedSignalData
    {
        $template = $config['template'] ?? '';
        $fieldMappings = $config['field_mappings'] ?? [];
        $lineMappings = $config['line_mappings'] ?? [];

        $data = [];
        $lines = array_map('trim', explode("\n", trim($message)));
        $confidence = 0;

        // Map fields based on line position
        foreach ($lineMappings as $lineIndex => $fieldConfig) {
            if (isset($lines[$lineIndex])) {
                $line = $lines[$lineIndex];
                $fieldName = $fieldConfig['field'] ?? null;
                $regex = $fieldConfig['pattern'] ?? null;

                if ($fieldName && $regex && preg_match($regex, $line, $matches)) {
                    $value = $matches[$fieldConfig['match_index'] ?? 1] ?? null;
                    
                    if ($value) {
                        switch ($fieldName) {
                            case 'currency_pair':
                                $data['currency_pair'] = $this->normalizeCurrencyPair($value);
                                $confidence += 15;
                                break;
                            case 'direction':
                                $data['direction'] = $this->normalizeDirection($value);
                                $confidence += 15;
                                break;
                            case 'open_price':
                                $data['open_price'] = $this->extractPrice($value);
                                $confidence += 20;
                                break;
                            case 'sl':
                                $data['sl'] = $this->extractPrice($value);
                                $confidence += 15;
                                break;
                            case 'tp':
                                $data['tp'] = $this->extractPrice($value);
                                $confidence += 15;
                                break;
                            case 'timeframe':
                                $data['timeframe'] = $this->normalizeTimeframe($value);
                                $confidence += 10;
                                break;
                        }
                    }
                }
            }
        }

        // Also try regex patterns if line mapping doesn't work
        if (isset($config['patterns'])) {
            $regexResult = $this->applyRegexPattern($config, $message, $pattern);
            if ($regexResult && $regexResult->confidence > $confidence) {
                return $regexResult;
            }
        }

        // Validate required fields
        // Note: open_price might not be present in some formats (percentage-based TP/SL)
        // When no entry price is specified, it means market entry at current price
        $hasRequiredFields = !empty($data['currency_pair']) && !empty($data['direction']);
        $hasPriceOrPercentage = !empty($data['open_price']) || 
                                (!empty($data['tp_percentage']) || !empty($data['sl_percentage']));
        
        if (!$hasRequiredFields || (!$hasPriceOrPercentage && empty($data['open_price']))) {
            return null;
        }

        // If we have percentage-based TP/SL but no open_price, it means market entry
        // Mark it to fetch current market price later
        if (empty($data['open_price']) && (!empty($data['tp_percentage']) || !empty($data['sl_percentage']))) {
            $data['open_price'] = 0; // Will be replaced with current market price
            $data['needs_price_fetch'] = true;
        }

        $data['confidence'] = min($confidence, 100);
        $data['pattern_used'] = $pattern['name'] ?? 'Unknown';
        $data['pattern_id'] = $pattern['id'] ?? null;

        return new ParsedSignalData($data);
    }

    /**
     * Normalize currency pair format.
     */
    protected function normalizeCurrencyPair(string $pair): string
    {
        $pair = strtoupper(trim($pair));
        $pair = str_replace('-', '/', $pair);
        $pair = str_replace('_', '/', $pair);
        
        // Handle cases like EURUSD -> EUR/USD
        if (strlen($pair) === 6 && !strpos($pair, '/')) {
            $pair = substr($pair, 0, 3) . '/' . substr($pair, 3);
        }

        return $pair;
    }

    /**
     * Normalize direction.
     */
    protected function normalizeDirection(string $direction): string
    {
        $direction = strtoupper(trim($direction));
        
        if (in_array($direction, ['BUY', 'LONG', 'UP', '↑', '🔺', '📈'])) {
            return 'buy';
        } elseif (in_array($direction, ['SELL', 'SHORT', 'DOWN', '↓', '🔻', '📉'])) {
            return 'sell';
        }

        return $direction;
    }

    /**
     * Extract price from string.
     */
    protected function extractPrice(string $value): float
    {
        // Remove currency symbols, commas, spaces
        $value = preg_replace('/[^\d.]/', '', $value);
        $price = (float) $value;
        return $price > 0 ? $price : 0;
    }

    /**
     * Normalize timeframe.
     */
    protected function normalizeTimeframe(string $timeframe): string
    {
        $timeframe = strtoupper(trim($timeframe));
        
        $mapping = [
            'M1' => 'M1', '1MIN' => 'M1', '1M' => 'M1',
            'M5' => 'M5', '5MIN' => 'M5', '5M' => 'M5',
            'M15' => 'M15', '15MIN' => 'M15', '15M' => 'M15',
            'M30' => 'M30', '30MIN' => 'M30', '30M' => 'M30',
            'H1' => 'H1', '1H' => 'H1', '1HOUR' => 'H1',
            'H4' => 'H4', '4H' => 'H4', '4HOUR' => 'H4',
            'D1' => 'D1', '1D' => 'D1', '1DAY' => 'D1',
            'W1' => 'W1', '1W' => 'W1', '1WEEK' => 'W1',
        ];

        return $mapping[$timeframe] ?? $timeframe;
    }

    public function getName(): string
    {
        return 'AdvancedPatternParser';
    }

    public function getPriority(): int
    {
        return 200; // Higher priority than basic regex parser
    }
}

