<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FilterStrategySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo filter strategies for technical indicator filtering
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
            $this->command->warn('Filter Strategy model not found. Skipping seeder.');
            return;
        }

        $strategies = [
            [
                'name' => 'EMA Crossover Strategy',
                'description' => 'Buy signals when EMA10 crosses above EMA50. Classic trend-following strategy.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'config' => [
                    'indicators' => [
                        'ema_fast' => ['period' => 10],
                        'ema_slow' => ['period' => 50],
                    ],
                    'rules' => [
                        'logic' => 'AND',
                        'conditions' => [
                            ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Stochastic Oversold/Overbought',
                'description' => 'Filter signals using Stochastic oscillator. Only buy when oversold, sell when overbought.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'config' => [
                    'indicators' => [
                        'stoch' => ['k' => 14, 'd' => 3, 'smooth' => 3],
                    ],
                    'rules' => [
                        'logic' => 'OR',
                        'conditions' => [
                            ['left' => 'stoch_k', 'operator' => '<', 'right' => 20], // Oversold
                            ['left' => 'stoch_k', 'operator' => '>', 'right' => 80], // Overbought
                        ],
                    ],
                ],
            ],
            [
                'name' => 'PSAR Trend Confirmation',
                'description' => 'Only trade when Parabolic SAR confirms trend direction. PSAR below price = uptrend, above = downtrend.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'config' => [
                    'indicators' => [
                        'psar' => ['step' => 0.02, 'max' => 0.2],
                    ],
                    'rules' => [
                        'logic' => 'AND',
                        'conditions' => [
                            ['left' => 'psar', 'operator' => 'below_price', 'right' => null],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Multi-Indicator Confirmation',
                'description' => 'Advanced strategy requiring EMA crossover AND PSAR confirmation AND Stochastic in favorable zone.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'config' => [
                    'indicators' => [
                        'ema_fast' => ['period' => 10],
                        'ema_slow' => ['period' => 50],
                        'psar' => ['step' => 0.02, 'max' => 0.2],
                        'stoch' => ['k' => 14, 'd' => 3, 'smooth' => 3],
                    ],
                    'rules' => [
                        'logic' => 'AND',
                        'conditions' => [
                            ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'],
                            ['left' => 'psar', 'operator' => 'below_price', 'right' => null],
                            ['left' => 'stoch_k', 'operator' => '>', 'right' => 30],
                            ['left' => 'stoch_k', 'operator' => '<', 'right' => 70],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Conservative Trend Filter',
                'description' => 'Conservative filter requiring strong trend confirmation: Price > EMA100 AND EMA10 > EMA100 AND PSAR below price.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'config' => [
                    'indicators' => [
                        'ema_fast' => ['period' => 10],
                        'ema_slow' => ['period' => 100],
                        'psar' => ['step' => 0.02, 'max' => 0.2],
                    ],
                    'rules' => [
                        'logic' => 'AND',
                        'conditions' => [
                            ['left' => 'price', 'operator' => '>', 'right' => 'ema_slow'],
                            ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'],
                            ['left' => 'psar', 'operator' => 'below_price', 'right' => null],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'RSI Divergence Filter',
                'description' => 'Filter using RSI for momentum confirmation. Only trade when RSI is in favorable range (30-70).',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'config' => [
                    'indicators' => [
                        'rsi' => ['period' => 14],
                    ],
                    'rules' => [
                        'logic' => 'AND',
                        'conditions' => [
                            ['left' => 'rsi', 'operator' => '>', 'right' => 30],
                            ['left' => 'rsi', 'operator' => '<', 'right' => 70],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($strategies as $strategy) {
            $modelClass::firstOrCreate(
                ['name' => $strategy['name']],
                $strategy
            );
        }

        $this->command->info('Filter Strategies seeded successfully!');
    }
}
