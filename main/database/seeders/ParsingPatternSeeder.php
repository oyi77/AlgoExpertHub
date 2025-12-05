<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParsingPatternSeeder extends Seeder
{
    public function run()
    {
        $patterns = [
            [
                'channel_source_id' => null,
                'user_id' => null,
                'name' => 'Forex Auto Format',
                'description' => 'Format: buy USA100 q=0.01 tt=0.46% td=0.46%',
                'pattern_type' => 'regex',
                'pattern_config' => json_encode([
                    'required_fields' => ['currency_pair', 'direction'],
                    'patterns' => [
                        'direction' => ['/(?:^|\s)(buy|sell)(?:\s|$)/i'],
                        'symbol' => ['/(?:^|\s)([A-Z0-9]{2,10})(?:\s|q=)/i', '/(?:buy|sell)\s+([A-Z0-9]{2,10})(?:\s|q=)/i'],
                        'currency_pair' => ['/(?:buy|sell)\s+([A-Z0-9]{2,10})(?:\s|q=)/i'],
                        'tp' => ['/tt\s*=\s*([\d.]+)\s*%/i', '/tp\s*=\s*([\d.]+)\s*%/i'],
                        'sl' => ['/td\s*=\s*([\d.]+)\s*%/i', '/sl\s*=\s*([\d.]+)\s*%/i']
                    ],
                    'confidence_weights' => [
                        'currency_pair' => 20,
                        'symbol' => 20,
                        'direction' => 20,
                        'tp' => 20,
                        'sl' => 20
                    ]
                ]),
                'priority' => 80,
                'is_active' => 1,
                'success_count' => 0,
                'failure_count' => 0
            ],
            [
                'channel_source_id' => null,
                'user_id' => null,
                'name' => 'Gold Multi-TP Format',
                'description' => 'Format: Gold SELL Limit, TP1/TP2/TP3/TP MAX, entry range',
                'pattern_type' => 'regex',
                'pattern_config' => json_encode([
                    'required_fields' => ['currency_pair', 'direction'],
                    'patterns' => [
                        'direction' => ['/(?:^|\s)(Gold|XAU|GOLD)\s+(BUY|SELL|LONG|SHORT)/i', '/(BUY|SELL|LONG|SHORT)\s+(?:Limit|Market)/i'],
                        'symbol' => ['/(Gold|XAU|GOLD)\s+(?:BUY|SELL|LONG|SHORT)/i', '/(?:^|\s)(Gold|XAU|GOLD)(?:\s|$)/i'],
                        'currency_pair' => ['/(Gold|XAU|GOLD)\s+(?:BUY|SELL|LONG|SHORT)/i'],
                        'open_price' => ['/(?:^|\n)\s*(\d{3,5}\.?\d*)\s*-\s*(\d{3,5}\.?\d*)\s*(?:\n|$)/', '/(?:^|\n)\s*(\d{3,5}\.?\d*)\s*(?:\n|$)/'],
                        'tp' => ['/TP\s*MAX\s*[:\s]*([\d.]+)/i', '/TP\s*(\d+)\s*[:\s]*([\d.]+)/i', '/TP\s*[:\s]*([\d.]+)/i'],
                        'sl' => ['/STOP\s*LOSS\s*[:\s]*([\d.]+)/i', '/SL\s*[:\s]*([\d.]+)/i']
                    ],
                    'confidence_weights' => [
                        'currency_pair' => 20,
                        'symbol' => 20,
                        'direction' => 20,
                        'open_price' => 20,
                        'tp' => 15,
                        'sl' => 15
                    ]
                ]),
                'priority' => 85,
                'is_active' => 1,
                'success_count' => 0,
                'failure_count' => 0
            ],
            [
                'channel_source_id' => null,
                'user_id' => null,
                'name' => 'Standard Signal Format',
                'description' => 'Common format: PAIR DIRECTION ENTRY SL TP',
                'pattern_type' => 'regex',
                'pattern_config' => json_encode([
                    'required_fields' => ['currency_pair', 'direction', 'open_price'],
                    'patterns' => [
                        'currency_pair' => ['/([A-Z]{2,10}\/[A-Z]{2,10})/', '/([A-Z]{2,10}-[A-Z]{2,10})/'],
                        'direction' => ['/(BUY|SELL)/i', '/(LONG|SHORT)/i'],
                        'open_price' => ['/ENTRY[:\s]*([\d,]+\.?\d*)/i', '/PRICE[:\s]*([\d,]+\.?\d*)/i'],
                        'sl' => ['/SL[:\s]*([\d,]+\.?\d*)/i', '/STOP[\s]*LOSS[:\s]*([\d,]+\.?\d*)/i'],
                        'tp' => ['/TP[:\s]*([\d,]+\.?\d*)/i', '/TAKE[\s]*PROFIT[:\s]*([\d,]+\.?\d*)/i']
                    ],
                    'confidence_weights' => [
                        'currency_pair' => 15,
                        'direction' => 15,
                        'open_price' => 20,
                        'sl' => 15,
                        'tp' => 15
                    ]
                ]),
                'priority' => 50,
                'is_active' => 1,
                'success_count' => 0,
                'failure_count' => 0
            ],
            [
                'channel_source_id' => null,
                'user_id' => null,
                'name' => 'Line-Based Template',
                'description' => 'Each field on separate line',
                'pattern_type' => 'template',
                'pattern_config' => json_encode([
                    'required_fields' => ['currency_pair', 'direction'],
                    'line_mappings' => [
                        ['field' => 'currency_pair', 'pattern' => '/([A-Z]{2,10}\/[A-Z]{2,10})/', 'match_index' => 1],
                        ['field' => 'direction', 'pattern' => '/(BUY|SELL)/i', 'match_index' => 1],
                        ['field' => 'open_price', 'pattern' => '/([\d,]+\.?\d*)/', 'match_index' => 1],
                        ['field' => 'sl', 'pattern' => '/([\d,]+\.?\d*)/', 'match_index' => 1],
                        ['field' => 'tp', 'pattern' => '/([\d,]+\.?\d*)/', 'match_index' => 1]
                    ]
                ]),
                'priority' => 40,
                'is_active' => 1,
                'success_count' => 0,
                'failure_count' => 0
            ]
        ];

        foreach ($patterns as $pattern) {
            DB::table('message_parsing_patterns')->updateOrInsert(
                ['name' => $pattern['name']],
                array_merge($pattern, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}

