<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * RulebookEaStrategySeeder
 * 
 * Creates RULEBOOK EA - Multi-Timeframe Strategy filter
 * 
 * Strategy Rules:
 * - H4 timeframe: Primary trend (EMA 10/100, Stochastic, PSAR)
 * - D1 timeframe: Support/Resistance mapping
 * - M15/H1 timeframe: Confirmation for entry
 * - Fibonacci retracement: Entry zones (23.6-38.2%, custom 11-61%)
 * - 3-bar candle validation: Signal must be confirmed across 3 candles
 */
class RulebookEaStrategySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Check which model class exists
        $modelClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)) {
            $modelClass = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class;
        } elseif (class_exists(\Addons\FilterStrategyAddon\App\Models\FilterStrategy::class)) {
            $modelClass = \Addons\FilterStrategyAddon\App\Models\FilterStrategy::class;
        }

        if (!$modelClass) {
            $this->command->warn('Filter Strategy model not found. Skipping Rulebook EA seeder.');
            return;
        }

        $strategy = [
            'name' => 'RULEBOOK EA - Multi-Timeframe Strategy',
            'description' => 'Professional multi-timeframe trading strategy implementing RULEBOOK EA rules. Uses H4 for primary trend analysis (EMA 10/100 cross, Stochastic 14/3/3, Parabolic SAR 0.02), D1 for support/resistance mapping, M15/H1 for entry confirmation, Fibonacci retracement zones (23.6-38.2%, custom 11-61%), and 3-bar candle validation for signal confirmation. All conditions must be met for BUY/SELL signals.',
            'created_by_user_id' => null,
            'visibility' => 'PUBLIC_MARKETPLACE',
            'clonable' => true,
            'enabled' => true,
            'config' => [
                // Multi-timeframe configuration
                'timeframes' => [
                    'primary' => 'H4',        // Main trend timeframe
                    'sr_mapping' => 'D1',    // Support/Resistance mapping timeframe
                    'confirmation' => ['M15', 'H1'],  // Entry confirmation timeframes
                ],

                // Indicators configuration per timeframe
                'indicators' => [
                    // H4 indicators (primary trend)
                    'h4' => [
                        'ema_fast' => [
                            'period' => 10,
                        ],
                        'ema_slow' => [
                            'period' => 100,
                        ],
                        'stoch' => [
                            'k' => 14,
                            'd' => 3,
                            'smooth' => 3,
                        ],
                        'psar' => [
                            'step' => 0.02,
                            'max' => 0.2,
                        ],
                    ],
                    // Confirmation timeframe indicators (M15/H1)
                    'confirmation' => [
                        'ema_fast' => [
                            'period' => 10,
                        ],
                        'ema_slow' => [
                            'period' => 30,
                        ],
                        'stoch' => [
                            'k' => 14,
                            'd' => 3,
                            'smooth' => 3,
                        ],
                        'psar' => [
                            'step' => 0.02,
                            'max' => 0.2,
                        ],
                    ],
                ],

                // Rules configuration
                'rules' => [
                    'logic' => 'AND',  // All conditions must pass

                    // Main conditions - All must be TRUE for signal
                    'conditions' => [
                        // H4 Trend: EMA 10 crosses above EMA 100 (BUY) OR crosses below (SELL)
                        [
                            'left' => 'h4.ema_fast',
                            'operator' => 'crosses_above',
                            'right' => 'h4.ema_slow',
                        ],
                        // H4 Stochastic: Stoch K crosses above D from below 80 (BUY) OR crosses below D from above 20 (SELL)
                        [
                            'left' => 'h4.stoch_k',
                            'operator' => 'stoch_cross_up',
                            'right' => 'h4.stoch_d',
                            'level' => 80,
                        ],
                        // H4 PSAR: PSAR below price (BUY) OR above price (SELL)
                        [
                            'left' => 'h4.psar',
                            'operator' => 'below_price',
                            'right' => null,
                        ],
                        // Confirmation: EMA 10 > EMA 30 on confirmation timeframe
                        [
                            'left' => 'confirmation.ema_fast',
                            'operator' => '>',
                            'right' => 'confirmation.ema_slow',
                        ],
                        // Confirmation: Stoch cross up from oversold
                        [
                            'left' => 'confirmation.stoch_k',
                            'operator' => 'stoch_cross_up',
                            'right' => 'confirmation.stoch_d',
                            'level' => 20,
                        ],
                        // Confirmation: PSAR below price
                        [
                            'left' => 'confirmation.psar',
                            'operator' => 'below_price',
                            'right' => null,
                        ],
                    ],

                    // Fibonacci Retracement Rule
                    'fibonacci' => [
                        'enabled' => true,
                        'levels' => [0.236, 0.382, 0.11, 0.61],  // Standard + custom levels
                        'lookback' => 20,
                        'tolerance' => 0.001,
                        'direction' => 'BUY',  // Will be determined by signal direction
                    ],

                    // Support/Resistance Mapping Rule
                    'sr_mapping' => [
                        'enabled' => true,
                        'timeframe' => 'D1',
                        'lookback' => 20,
                        'min_strength' => 0.5,
                        'validate_break' => true,
                        'direction' => 'BUY',  // Will be determined by signal direction
                    ],

                    // 3-Bar Candle Validation Rule
                    'candle_validation' => [
                        'enabled' => true,
                        'bars' => 3,
                        'min_confirmations' => 2,  // At least 2 of 3 candles must confirm
                    ],
                ],
            ],
        ];

        try {
            // Check if exists (including soft deleted)
            $existing = $modelClass::withTrashed()->where('name', $strategy['name'])->first();
            
            if ($existing) {
                if ($existing->trashed()) {
                    // Restore if soft deleted
                    $existing->restore();
                    $this->command->info('RULEBOOK EA Strategy was soft deleted. Restored and updated.');
                } else {
                    $this->command->info('RULEBOOK EA Strategy already exists. Updating with latest config.');
                }
                $existing->update($strategy);
                $this->command->info("✅ Strategy ID: {$existing->id}, Name: {$existing->name}");
                $this->command->info("   Enabled: " . ($existing->enabled ? 'Yes' : 'No'));
                $this->command->info("   Visibility: {$existing->visibility}");
                $this->command->info("   Config size: " . strlen(json_encode($existing->config)) . " bytes");
            } else {
                // Create new
                $created = $modelClass::create($strategy);
                $this->command->info('✅ RULEBOOK EA Strategy created successfully!');
                $this->command->info("   Strategy ID: {$created->id}, Name: {$created->name}");
                $this->command->info("   Enabled: " . ($created->enabled ? 'Yes' : 'No'));
                $this->command->info("   Visibility: {$created->visibility}");
                $this->command->info("   Config size: " . strlen(json_encode($created->config)) . " bytes");
                
                // Verify it can be retrieved
                $verify = $modelClass::find($created->id);
                if ($verify) {
                    $this->command->info("   ✅ Verification: Strategy can be retrieved from database");
                } else {
                    $this->command->error("   ❌ Verification: Strategy NOT found after creation!");
                }
            }
        } catch (\Exception $e) {
            $this->command->error('❌ Error creating RULEBOOK EA Strategy: ' . $e->getMessage());
            $this->command->error('   File: ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }
}

