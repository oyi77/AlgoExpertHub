<?php

namespace Addons\TradingManagement\Database\Seeders;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Prebuilt Trading Bot Seeder
 * 
 * Creates prebuilt bot templates for investor demos using MA100, MA10, Parabolic SAR
 * All bots are public templates that users can clone
 */
class PrebuiltTradingBotSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // First, create filter strategies with MA100/MA10/PSAR if not exist
            $filters = $this->createFilterStrategies();

            // Lookup presets by name (from TradingPresetSeeder)
            $presets = $this->lookupPresets();

            // Create bot templates
            $this->createBotTemplates($presets, $filters);
        });
    }

    /**
     * Create filter strategies using MA100, MA10, PSAR
     */
    protected function createFilterStrategies(): array
    {
        $filters = [];

        // 1. MA10/MA100/PSAR Uptrend Filter
        $filters['ma_uptrend'] = FilterStrategy::firstOrCreate(
            [
                'name' => 'MA10/MA100/PSAR Uptrend Filter',
            ],
            [
                'description' => 'Only execute BUY signals when MA10 > MA100 (uptrend) AND PSAR below price (bullish momentum). Professional trend confirmation using Moving Average crossover and Parabolic SAR.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'config' => [
                    'indicators' => [
                        'ema_fast' => ['period' => 10], // MA10
                        'ema_slow' => ['period' => 100], // MA100
                        'psar' => ['step' => 0.02, 'max' => 0.2],
                    ],
                    'rules' => [
                        'logic' => 'AND',
                        'conditions' => [
                            ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'], // MA10 > MA100
                            ['left' => 'psar', 'operator' => 'below_price', 'right' => null], // PSAR below price
                        ],
                    ],
                ],
            ]
        );

        // 2. MA Crossover Filter
        $filters['ma_crossover'] = FilterStrategy::firstOrCreate(
            [
                'name' => 'MA Crossover Filter',
            ],
            [
                'description' => 'Enter on MA10 crossing above MA100 with PSAR confirmation. Captures trend reversals and strong momentum moves.',
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
                            ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'],
                            ['left' => 'psar', 'operator' => 'below_price', 'right' => null],
                        ],
                    ],
                ],
            ]
        );

        // 3. Strong Trend Filter (MA100 + PSAR)
        $filters['strong_trend'] = FilterStrategy::firstOrCreate(
            [
                'name' => 'Strong Trend Filter (MA100 + PSAR)',
            ],
            [
                'description' => 'Only trade in strong uptrends: Price > MA100 AND MA10 > MA100 AND PSAR below price. Multiple confirmation levels for high-probability trades.',
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
                            ['left' => 'price', 'operator' => '>', 'right' => 'ema_slow'], // Price > MA100
                            ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'], // MA10 > MA100
                            ['left' => 'psar', 'operator' => 'below_price', 'right' => null],
                        ],
                    ],
                ],
            ]
        );

        // 4. Basic MA Filter
        $filters['basic_ma'] = FilterStrategy::firstOrCreate(
            [
                'name' => 'Basic MA Filter',
            ],
            [
                'description' => 'Simple trend confirmation: MA10 > MA100 with PSAR. Conservative filter for steady trending markets.',
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
                            ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'],
                            ['left' => 'psar', 'operator' => 'below_price', 'right' => null],
                        ],
                    ],
                ],
            ]
        );

        // 5. Comprehensive MA/PSAR Filter
        $filters['comprehensive_ma'] = FilterStrategy::firstOrCreate(
            [
                'name' => 'Comprehensive MA/PSAR Filter',
            ],
            [
                'description' => 'Advanced multi-level confirmation: Price > MA100, MA10 > MA100, and PSAR below price. Maximum trend strength confirmation for highest probability trades.',
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
            ]
        );

        return $filters;
    }

    /**
     * Lookup presets by name
     */
    protected function lookupPresets(): array
    {
        $presetNames = [
            'Conservative Scalper',
            'Moderate Swing Trader',
            'Swing Trader',
            'Aggressive Day Trader',
        ];

        $presets = [];
        foreach ($presetNames as $name) {
            // Try trading-management-addon preset first
            $preset = TradingPreset::where('name', $name)
                ->where('is_default_template', true)
                ->first();

            // Fallback to trading-preset-addon if exists
            if (!$preset && class_exists(\Addons\TradingPresetAddon\App\Models\TradingPreset::class)) {
                $preset = \Addons\TradingPresetAddon\App\Models\TradingPreset::where('name', $name)
                    ->where('is_default_template', true)
                    ->first();
            }

            if ($preset) {
                $presets[strtolower(str_replace(' ', '_', $name))] = $preset;
            }
        }

        return $presets;
    }

    /**
     * Create bot templates
     */
    protected function createBotTemplates(array $presets, array $filters): void
    {
        // 1. MA Trend Confirmation Bot (Forex) - DEMO FOCUS
        TradingBot::firstOrCreate(
            [
                'name' => 'MA Trend Confirmation Bot (Forex)',
            ],
            [
                'description' => 'Professional forex bot using Moving Average crossover (MA10/MA100) and Parabolic SAR for trend confirmation. Perfect for demo showcasing technical analysis with conservative risk management.',
                'trading_preset_id' => $presets['conservative_scalper']->id ?? null,
                'filter_strategy_id' => $filters['ma_uptrend']->id ?? null,
                'ai_model_profile_id' => null,
                'user_id' => null,
                'admin_id' => null,
                'exchange_connection_id' => null, // Templates don't have connections - user provides during clone
                'is_active' => false,
                'is_paper_trading' => true,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'is_default_template' => true,
                'created_by_user_id' => null,
                'suggested_connection_type' => 'fx',
                'tags' => ['forex', 'conservative', 'ma', 'psar', 'trend', 'demo'],
            ]
        );

        // 2. MA10/MA100 Crossover Bot (Forex)
        TradingBot::firstOrCreate(
            [
                'name' => 'MA10/MA100 Crossover Bot (Forex)',
            ],
            [
                'description' => 'Swing trading bot that enters on MA crossover signals with PSAR confirmation. Captures trend reversals and momentum shifts in forex markets.',
                'trading_preset_id' => $presets['moderate_swing_trader']->id ?? $presets['swing_trader']->id ?? null,
                'filter_strategy_id' => $filters['ma_crossover']->id ?? null,
                'ai_model_profile_id' => null,
                'user_id' => null,
                'admin_id' => null,
                'exchange_connection_id' => null, // Templates don't have connections - user provides during clone
                'is_active' => false,
                'is_paper_trading' => true,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'is_default_template' => true,
                'created_by_user_id' => null,
                'suggested_connection_type' => 'fx',
                'tags' => ['forex', 'swing', 'crossover', 'ma', 'psar'],
            ]
        );

        // 3. MA100 + PSAR Trend Follower (Crypto)
        TradingBot::firstOrCreate(
            [
                'name' => 'MA100 + PSAR Trend Follower (Crypto)',
            ],
            [
                'description' => 'Crypto bot for strong trending markets using multiple MA levels and PSAR. Filters for Price > MA100, MA10 > MA100, and PSAR confirmation.',
                'trading_preset_id' => $presets['aggressive_day_trader']->id ?? null,
                'filter_strategy_id' => $filters['strong_trend']->id ?? null,
                'ai_model_profile_id' => null,
                'user_id' => null,
                'admin_id' => null,
                'exchange_connection_id' => null, // Templates don't have connections - user provides during clone
                'is_active' => false,
                'is_paper_trading' => true,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'is_default_template' => true,
                'created_by_user_id' => null,
                'suggested_connection_type' => 'crypto',
                'tags' => ['crypto', 'aggressive', 'trend', 'ma100', 'psar'],
            ]
        );

        // 4. Conservative MA Trend Bot (Multi-Market)
        TradingBot::firstOrCreate(
            [
                'name' => 'Conservative MA Trend Bot (Multi-Market)',
            ],
            [
                'description' => 'Simple, conservative bot using basic MA trend confirmation (MA10 > MA100 + PSAR). Safe for both Forex and Crypto markets with minimal risk.',
                'trading_preset_id' => $presets['conservative_scalper']->id ?? null,
                'filter_strategy_id' => $filters['basic_ma']->id ?? null,
                'ai_model_profile_id' => null,
                'user_id' => null,
                'admin_id' => null,
                'exchange_connection_id' => null, // Templates don't have connections - user provides during clone
                'is_active' => false,
                'is_paper_trading' => true,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'is_default_template' => true,
                'created_by_user_id' => null,
                'suggested_connection_type' => 'both',
                'tags' => ['multi', 'conservative', 'ma', 'psar', 'beginner'],
            ]
        );

        // 5. Advanced MA + PSAR Multi-Strategy (Forex) - DEMO FOCUS
        TradingBot::firstOrCreate(
            [
                'name' => 'Advanced MA + PSAR Multi-Strategy (Forex)',
            ],
            [
                'description' => 'Advanced bot showcasing comprehensive technical analysis: Price > MA100, MA10 > MA100, PSAR confirmation. Features break-even, trailing stops, and multi-TP for sophisticated risk management.',
                'trading_preset_id' => $presets['swing_trader']->id ?? $presets['moderate_swing_trader']->id ?? null,
                'filter_strategy_id' => $filters['comprehensive_ma']->id ?? null,
                'ai_model_profile_id' => null,
                'user_id' => null,
                'admin_id' => null,
                'exchange_connection_id' => null, // Templates don't have connections - user provides during clone
                'is_active' => false,
                'is_paper_trading' => true,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'is_default_template' => true,
                'created_by_user_id' => null,
                'suggested_connection_type' => 'fx',
                'tags' => ['forex', 'advanced', 'ma', 'psar', 'multi-tp', 'break-even', 'trailing-stop', 'demo'],
            ]
        );

        // 6. MA100 Support/Resistance Bot (Forex)
        TradingBot::firstOrCreate(
            [
                'name' => 'MA100 Support/Resistance Bot (Forex)',
            ],
            [
                'description' => 'Bot that trades bounces off MA100 support/resistance levels with PSAR and MA10 momentum confirmation. Captures mean reversion in trending markets.',
                'trading_preset_id' => $presets['swing_trader']->id ?? $presets['moderate_swing_trader']->id ?? null,
                'filter_strategy_id' => $filters['ma_uptrend']->id ?? null,
                'ai_model_profile_id' => null,
                'user_id' => null,
                'admin_id' => null,
                'exchange_connection_id' => null, // Templates don't have connections - user provides during clone
                'is_active' => false,
                'is_paper_trading' => true,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'is_default_template' => true,
                'created_by_user_id' => null,
                'suggested_connection_type' => 'fx',
                'tags' => ['forex', 'swing', 'support', 'resistance', 'ma100', 'psar'],
            ]
        );
    }
}
