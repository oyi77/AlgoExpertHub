<?php

namespace Addons\TradingManagement\Database\Seeders;

use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo Trading Bot Seeder
 * 
 * Creates sample data for investor demo:
 * - Sample connections (paper trading)
 * - Sample presets (Conservative, Moderate, Aggressive)
 * - Sample filter strategies
 * - Sample trading bots
 */
class DemoTradingBotSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // Get or create demo user
            $demoUser = User::firstOrCreate(
                ['email' => 'demo@investor.com'],
                [
                    'username' => 'demo_investor',
                    'password' => bcrypt('demo123'),
                    'status' => 1,
                    'is_email_verified' => 1,
                ]
            );

            // Create sample connections (paper trading)
            $connections = $this->createDemoConnections($demoUser);

            // Create sample presets
            $presets = $this->createDemoPresets($demoUser);

            // Create sample filter strategies
            $filters = $this->createDemoFilters($demoUser);

            // Create sample bots
            $this->createDemoBots($demoUser, $connections, $presets, $filters);
        });
    }

    protected function createDemoConnections(User $user): array
    {
        $connections = [];

        // Binance Testnet Connection
        $connections['binance'] = ExecutionConnection::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Binance Testnet (Demo)',
            ],
            [
                'type' => 'crypto',
                'exchange_name' => 'binance',
                'credentials' => encrypt(json_encode([
                    'api_key' => 'test_key',
                    'api_secret' => 'test_secret',
                ])),
                'status' => 'active',
                'is_active' => true,
                'settings' => json_encode([
                    'is_paper_trading' => true,
                ]),
            ]
        );

        // MT4 Demo Connection
        $connections['mt4'] = ExecutionConnection::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'MT4 Demo Account',
            ],
            [
                'type' => 'fx',
                'exchange_name' => 'mt4',
                'credentials' => encrypt(json_encode([
                    'api_key' => 'demo_key',
                    'api_secret' => 'demo_secret',
                    'account_id' => '123456',
                ])),
                'status' => 'active',
                'is_active' => true,
                'settings' => json_encode([
                    'is_paper_trading' => true,
                ]),
            ]
        );

        return $connections;
    }

    protected function createDemoPresets(User $user): array
    {
        $presets = [];

        // Conservative Preset
        $presets['conservative'] = TradingPreset::firstOrCreate(
            [
                'name' => 'Conservative Scalper',
                'created_by_user_id' => $user->id,
            ],
            [
                'description' => 'Low risk, small positions, tight stops',
                'position_size_mode' => 'RISK_PERCENT',
                'risk_per_trade_pct' => 0.5,
                'sl_mode' => 'PIPS',
                'sl_pips' => 30,
                'tp_mode' => 'SINGLE',
                'tp1_rr' => 2.0,
                'max_positions' => 1,
                'enabled' => true,
                'visibility' => 'PRIVATE',
            ]
        );

        // Moderate Preset
        $presets['moderate'] = TradingPreset::firstOrCreate(
            [
                'name' => 'Moderate Swing Trader',
                'created_by_user_id' => $user->id,
            ],
            [
                'description' => 'Balanced risk, multi-TP, break-even enabled',
                'position_size_mode' => 'RISK_PERCENT',
                'risk_per_trade_pct' => 1.0,
                'sl_mode' => 'PIPS',
                'sl_pips' => 50,
                'tp_mode' => 'MULTI',
                'tp1_enabled' => true,
                'tp1_rr' => 2.0,
                'tp1_close_pct' => 30,
                'tp2_enabled' => true,
                'tp2_rr' => 3.0,
                'tp2_close_pct' => 30,
                'tp3_enabled' => true,
                'tp3_rr' => 5.0,
                'tp3_close_pct' => 40,
                'be_enabled' => true,
                'be_trigger_rr' => 1.5,
                'max_positions' => 2,
                'enabled' => true,
                'visibility' => 'PRIVATE',
            ]
        );

        // Aggressive Preset
        $presets['aggressive'] = TradingPreset::firstOrCreate(
            [
                'name' => 'Aggressive Day Trader',
                'created_by_user_id' => $user->id,
            ],
            [
                'description' => 'High risk, layering, trailing stop',
                'position_size_mode' => 'RISK_PERCENT',
                'risk_per_trade_pct' => 2.0,
                'sl_mode' => 'PIPS',
                'sl_pips' => 40,
                'tp_mode' => 'MULTI',
                'tp1_enabled' => true,
                'tp1_rr' => 2.0,
                'tp1_close_pct' => 25,
                'tp2_enabled' => true,
                'tp2_rr' => 3.0,
                'tp2_close_pct' => 25,
                'tp3_enabled' => true,
                'tp3_rr' => 5.0,
                'tp3_close_pct' => 50,
                'layering_enabled' => true,
                'max_layers_per_symbol' => 3,
                'layer_distance_pips' => 30,
                'ts_enabled' => true,
                'ts_mode' => 'STEP_PIPS',
                'ts_trigger_rr' => 2.0,
                'ts_step_pips' => 20,
                'max_positions' => 3,
                'enabled' => true,
                'visibility' => 'PRIVATE',
            ]
        );

        return $presets;
    }

    protected function createDemoFilters(User $user): array
    {
        $filters = [];

        // Uptrend Filter
        $filters['uptrend'] = FilterStrategy::firstOrCreate(
            [
                'name' => 'Uptrend Filter',
                'created_by_user_id' => $user->id,
            ],
            [
                'description' => 'Only execute buy signals in uptrend (EMA fast > slow, PSAR below price)',
                'enabled' => true,
                'visibility' => 'PRIVATE',
                'config' => [
                    'indicators' => [
                        'ema_fast' => ['period' => 10],
                        'ema_slow' => ['period' => 100],
                        'psar' => ['step' => 0.02, 'max' => 0.2],
                    ],
                    'rules' => [
                        [
                            'operator' => 'AND',
                            'conditions' => [
                                ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'],
                                ['left' => 'psar', 'operator' => 'below_price', 'right' => null],
                            ],
                        ],
                    ],
                ],
            ]
        );

        // Oversold Buy Filter
        $filters['oversold'] = FilterStrategy::firstOrCreate(
            [
                'name' => 'Oversold Buy Filter',
                'created_by_user_id' => $user->id,
            ],
            [
                'description' => 'Only buy when Stochastic is oversold (< 20)',
                'enabled' => true,
                'visibility' => 'PRIVATE',
                'config' => [
                    'indicators' => [
                        'stochastic' => ['k' => 14, 'd' => 3, 'smooth' => 3],
                    ],
                    'rules' => [
                        [
                            'operator' => 'AND',
                            'conditions' => [
                                ['left' => 'stochastic', 'operator' => '<', 'right' => 20],
                            ],
                        ],
                    ],
                ],
            ]
        );

        // Trend + Momentum Filter
        $filters['trend_momentum'] = FilterStrategy::firstOrCreate(
            [
                'name' => 'Trend + Momentum Filter',
                'created_by_user_id' => $user->id,
            ],
            [
                'description' => 'Uptrend with momentum confirmation (EMA + Stochastic)',
                'enabled' => true,
                'visibility' => 'PRIVATE',
                'config' => [
                    'indicators' => [
                        'ema_fast' => ['period' => 10],
                        'ema_slow' => ['period' => 100],
                        'stochastic' => ['k' => 14, 'd' => 3, 'smooth' => 3],
                    ],
                    'rules' => [
                        [
                            'operator' => 'AND',
                            'conditions' => [
                                ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'],
                                ['left' => 'stochastic', 'operator' => '>', 'right' => 50],
                            ],
                        ],
                    ],
                ],
            ]
        );

        return $filters;
    }

    protected function createDemoBots(User $user, array $connections, array $presets, array $filters): void
    {
        // EUR/USD Conservative Bot
        TradingBot::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'EUR/USD Conservative Bot',
            ],
            [
                'description' => 'Conservative trading bot for EUR/USD with uptrend filter',
                'exchange_connection_id' => $connections['mt4']->id,
                'trading_preset_id' => $presets['conservative']->id,
                'filter_strategy_id' => $filters['uptrend']->id,
                'is_active' => true,
                'is_paper_trading' => true,
            ]
        );

        // BTC/USD Aggressive Bot
        TradingBot::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'BTC/USD Aggressive Bot',
            ],
            [
                'description' => 'Aggressive crypto bot with trend + momentum filter',
                'exchange_connection_id' => $connections['binance']->id,
                'trading_preset_id' => $presets['aggressive']->id,
                'filter_strategy_id' => $filters['trend_momentum']->id,
                'is_active' => true,
                'is_paper_trading' => true,
            ]
        );

        // Multi-Pair Moderate Bot
        TradingBot::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Multi-Pair Moderate Bot',
            ],
            [
                'description' => 'Moderate risk bot for multiple pairs, no filter (executes all signals)',
                'exchange_connection_id' => $connections['mt4']->id,
                'trading_preset_id' => $presets['moderate']->id,
                'filter_strategy_id' => null, // No filter - executes all
                'is_active' => true,
                'is_paper_trading' => true,
            ]
        );
    }
}
