<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PatternTemplateService
{
    /**
     * Create a pattern template.
     *
     * @param array $data
     * @return MessageParsingPattern
     */
    public function createPattern(array $data): MessageParsingPattern
    {
        $validated = $this->validatePatternData($data);

        $pattern = MessageParsingPattern::create([
            'channel_source_id' => $validated['channel_source_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'pattern_type' => $validated['pattern_type'] ?? 'regex',
            'pattern_config' => $validated['pattern_config'],
            'priority' => $validated['priority'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        Log::info("Created pattern template: {$pattern->name} (ID: {$pattern->id})");

        return $pattern;
    }

    /**
     * Update a pattern template.
     *
     * @param MessageParsingPattern $pattern
     * @param array $data
     * @return MessageParsingPattern
     */
    public function updatePattern(MessageParsingPattern $pattern, array $data): MessageParsingPattern
    {
        $validated = $this->validatePatternData($data, $pattern);

        $pattern->update([
            'name' => $validated['name'] ?? $pattern->name,
            'description' => $validated['description'] ?? $pattern->description,
            'pattern_type' => $validated['pattern_type'] ?? $pattern->pattern_type,
            'pattern_config' => $validated['pattern_config'] ?? $pattern->pattern_config,
            'priority' => $validated['priority'] ?? $pattern->priority,
            'is_active' => $validated['is_active'] ?? $pattern->is_active,
        ]);

        Log::info("Updated pattern template: {$pattern->name} (ID: {$pattern->id})");

        return $pattern->fresh();
    }

    /**
     * Test a pattern against sample messages.
     *
     * @param array $patternConfig
     * @param string $sampleMessage
     * @return array
     */
    public function testPattern(array $patternConfig, string $sampleMessage): array
    {
        try {
            // Create a temporary pattern model for testing
            $tempPattern = new \Addons\MultiChannelSignalAddon\App\Models\MessageParsingPattern([
                'id' => 0,
                'name' => 'Test Pattern',
                'pattern_type' => $patternConfig['pattern_type'] ?? 'regex',
                'pattern_config' => $patternConfig,
            ]);

            // Use reflection to access protected method or create a test parser
            // AdvancedPatternParser requires a ChannelSource, but for testing we can pass null
            $parser = new \Addons\MultiChannelSignalAddon\App\Parsers\AdvancedPatternParser(null);
            
            // For testing, we need to manually test the pattern config
            // Since AdvancedPatternParser loads patterns from DB, we'll test directly
            $patternType = $patternConfig['pattern_type'] ?? 'regex';
            $patterns = $patternConfig['patterns'] ?? [];
            
            // Simple regex matching test
            if ($patternType === 'regex' && !empty($patterns)) {
                $foundFields = [];
                $parsedData = [];
                
                // Test currency pair
                if (isset($patterns['currency_pair'])) {
                    foreach ($patterns['currency_pair'] as $regex) {
                        if (preg_match($regex, $sampleMessage, $matches)) {
                            $parsedData['currency_pair'] = $matches[1] ?? $matches[0];
                            $foundFields[] = 'currency_pair';
                            break;
                        }
                    }
                }
                
                // Test direction
                if (isset($patterns['direction'])) {
                    foreach ($patterns['direction'] as $regex) {
                        if (preg_match($regex, $sampleMessage, $matches)) {
                            $parsedData['direction'] = strtolower($matches[1] ?? $matches[0]);
                            $foundFields[] = 'direction';
                            break;
                        }
                    }
                }
                
                // Test open price
                if (isset($patterns['open_price'])) {
                    foreach ($patterns['open_price'] as $regex) {
                        if (preg_match($regex, $sampleMessage, $matches)) {
                            $parsedData['open_price'] = (float) preg_replace('/[^\d.]/', '', $matches[1] ?? $matches[0]);
                            $foundFields[] = 'open_price';
                            break;
                        }
                    }
                }
                
                // Test SL
                if (isset($patterns['sl'])) {
                    foreach ($patterns['sl'] as $regex) {
                        if (preg_match($regex, $sampleMessage, $matches)) {
                            $parsedData['sl'] = (float) preg_replace('/[^\d.]/', '', $matches[1] ?? $matches[0]);
                            $foundFields[] = 'sl';
                            break;
                        }
                    }
                }
                
                // Test TP
                if (isset($patterns['tp'])) {
                    foreach ($patterns['tp'] as $regex) {
                        if (preg_match($regex, $sampleMessage, $matches)) {
                            $parsedData['tp'] = (float) preg_replace('/[^\d.]/', '', $matches[1] ?? $matches[0]);
                            $foundFields[] = 'tp';
                            break;
                        }
                    }
                }
                
                $requiredFields = $patternConfig['required_fields'] ?? ['currency_pair', 'direction'];
                $hasRequiredFields = count(array_intersect($requiredFields, $foundFields)) >= count($requiredFields);
                
                if ($hasRequiredFields && !empty($parsedData)) {
                    return [
                        'success' => true,
                        'parsed_data' => $parsedData,
                        'confidence' => min(100, count($foundFields) * 20),
                        'found_fields' => $foundFields,
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Pattern did not match the sample message',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get default pattern templates.
     *
     * @return array
     */
    public function getDefaultTemplates(): array
    {
        return [
            [
                'name' => 'Standard Signal Format',
                'description' => 'Common format: PAIR DIRECTION ENTRY SL TP',
                'pattern_type' => 'regex',
                'pattern_config' => [
                    'required_fields' => ['currency_pair', 'direction', 'open_price'],
                    'patterns' => [
                        'currency_pair' => [
                            '/([A-Z]{2,10}\/[A-Z]{2,10})/',
                            '/([A-Z]{2,10}-[A-Z]{2,10})/',
                        ],
                        'direction' => [
                            '/(BUY|SELL)/i',
                            '/(LONG|SHORT)/i',
                        ],
                        'open_price' => [
                            '/ENTRY[:\s]*([\d,]+\.?\d*)/i',
                            '/PRICE[:\s]*([\d,]+\.?\d*)/i',
                        ],
                        'sl' => [
                            '/SL[:\s]*([\d,]+\.?\d*)/i',
                            '/STOP[:\s]*LOSS[:\s]*([\d,]+\.?\d*)/i',
                        ],
                        'tp' => [
                            '/TP[:\s]*([\d,]+\.?\d*)/i',
                            '/TAKE[:\s]*PROFIT[:\s]*([\d,]+\.?\d*)/i',
                        ],
                    ],
                    'confidence_weights' => [
                        'currency_pair' => 15,
                        'direction' => 15,
                        'open_price' => 20,
                        'sl' => 15,
                        'tp' => 15,
                    ],
                ],
                'priority' => 50,
            ],
            [
                'name' => 'Line-Based Template',
                'description' => 'Each field on separate line',
                'pattern_type' => 'template',
                'pattern_config' => [
                    'required_fields' => ['currency_pair', 'direction'],
                    'line_mappings' => [
                        0 => ['field' => 'currency_pair', 'pattern' => '/([A-Z]{2,10}\/[A-Z]{2,10})/', 'match_index' => 1],
                        1 => ['field' => 'direction', 'pattern' => '/(BUY|SELL)/i', 'match_index' => 1],
                        2 => ['field' => 'open_price', 'pattern' => '/([\d,]+\.?\d*)/', 'match_index' => 1],
                        3 => ['field' => 'sl', 'pattern' => '/([\d,]+\.?\d*)/', 'match_index' => 1],
                        4 => ['field' => 'tp', 'pattern' => '/([\d,]+\.?\d*)/', 'match_index' => 1],
                    ],
                ],
                'priority' => 40,
            ],
            [
                'name' => 'Forex Auto Format',
                'description' => 'Format: buy USA100 q=0.01 tt=0.46% td=0.46%',
                'pattern_type' => 'regex',
                'pattern_config' => [
                    'required_fields' => ['currency_pair', 'direction'],
                    'patterns' => [
                        'direction' => [
                            '/(?:^|\s)(buy|sell)(?:\s|$)/i',
                        ],
                        'symbol' => [
                            '/(?:^|\s)([A-Z0-9]{2,10})(?:\s|q=)/i', // Matches symbol before q=
                            '/(?:buy|sell)\s+([A-Z0-9]{2,10})(?:\s|q=)/i', // Matches symbol after direction
                        ],
                        'currency_pair' => [
                            '/(?:buy|sell)\s+([A-Z0-9]{2,10})(?:\s|q=)/i', // Use symbol as currency_pair
                        ],
                        'tp' => [
                            '/tt\s*=\s*([\d.]+)\s*%/i', // tt=0.46%
                            '/tp\s*=\s*([\d.]+)\s*%/i', // Alternative: tp=0.46%
                        ],
                        'sl' => [
                            '/td\s*=\s*([\d.]+)\s*%/i', // td=0.46%
                            '/sl\s*=\s*([\d.]+)\s*%/i', // Alternative: sl=0.46%
                        ],
                    ],
                    'confidence_weights' => [
                        'currency_pair' => 20,
                        'symbol' => 20,
                        'direction' => 20,
                        'tp' => 20,
                        'sl' => 20,
                    ],
                ],
                'priority' => 80, // Higher priority for this specific format
            ],
            [
                'name' => 'Gold Multi-TP Format',
                'description' => 'Format: Gold SELL Limit, TP1/TP2/TP3/TP MAX, entry range',
                'pattern_type' => 'regex',
                'pattern_config' => [
                    'required_fields' => ['currency_pair', 'direction'],
                    'patterns' => [
                        'direction' => [
                            '/(?:^|\s)(Gold|XAU|GOLD)\s+(BUY|SELL|LONG|SHORT)/i',
                            '/(BUY|SELL|LONG|SHORT)\s+(?:Limit|Market)/i',
                        ],
                        'symbol' => [
                            '/(Gold|XAU|GOLD)\s+(?:BUY|SELL|LONG|SHORT)/i',
                            '/(?:^|\s)(Gold|XAU|GOLD)(?:\s|$)/i',
                        ],
                        'currency_pair' => [
                            '/(Gold|XAU|GOLD)\s+(?:BUY|SELL|LONG|SHORT)/i',
                            '/(?:^|\s)(Gold|XAU|GOLD)(?:\s|$)/i',
                        ],
                        'open_price' => [
                            '/(?:^|\n)\s*(\d{3,5}\.?\d*)\s*-\s*(\d{3,5}\.?\d*)\s*(?:\n|$)/', // Price range: 4081-4082
                            '/(?:^|\n)\s*(\d{3,5}\.?\d*)\s*(?:\n|$)/', // Single price on its own line
                        ],
                        'tp' => [
                            '/TP\s*MAX\s*[:\s]*([\d.]+)/i', // TP MAX : 4030
                            '/TP\s*(\d+)\s*[:\s]*([\d.]+)/i', // TP1 : 4076, TP2 : 4071, TP3 : 4066
                            '/TP\s*[:\s]*([\d.]+)/i', // Generic TP : 4076
                        ],
                        'sl' => [
                            '/STOP\s*LOSS\s*[:\s]*([\d.]+)/i', // STOP LOSS : 4084.5
                            '/SL\s*[:\s]*([\d.]+)/i', // SL : 4084.5
                        ],
                    ],
                    'confidence_weights' => [
                        'currency_pair' => 20,
                        'symbol' => 20,
                        'direction' => 20,
                        'open_price' => 20,
                        'tp' => 15,
                        'sl' => 15,
                    ],
                ],
                'priority' => 85, // Higher priority for this specific format
            ],
        ];
    }

    /**
     * Validate pattern data.
     *
     * @param array $data
     * @param MessageParsingPattern|null $pattern
     * @return array
     */
    protected function validatePatternData(array $data, ?MessageParsingPattern $pattern = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pattern_type' => 'required|in:regex,template,ai_fallback',
            'pattern_config' => 'required|array',
            'priority' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|boolean',
            'channel_source_id' => 'nullable|exists:channel_sources,id',
            'user_id' => 'nullable|exists:users,id',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid pattern data: ' . $validator->errors()->first());
        }

        // Validate pattern_config structure
        $config = $data['pattern_config'];
        $type = $data['pattern_type'] ?? 'regex';

        if ($type === 'regex') {
            if (empty($config['patterns']) || !is_array($config['patterns'])) {
                throw new \InvalidArgumentException('Pattern config must contain "patterns" array for regex type');
            }
        } elseif ($type === 'template') {
            if (empty($config['line_mappings']) && empty($config['template'])) {
                throw new \InvalidArgumentException('Pattern config must contain "line_mappings" or "template" for template type');
            }
        }

        return $validator->validated();
    }
}

