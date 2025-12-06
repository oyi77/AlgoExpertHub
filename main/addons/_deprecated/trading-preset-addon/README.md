# Trading Preset Addon

Comprehensive preset system for trading configurations that can be applied to signals, trading bots, and copy trading executions.

## Installation

1. The addon is located in `main/addons/trading-preset-addon/`
2. Run migrations: `php artisan migrate`
3. Seed default presets: `php artisan db:seed --class="Addons\TradingPresetAddon\Database\Seeders\TradingPresetSeeder"`

## Features

- **Preset Management**: Create, edit, clone, and manage trading presets
- **Default Presets**: 6 pre-configured presets for different trading styles
- **User Onboarding**: Automatic preset assignment for new users
- **Auto-Assignment**: New connections automatically get user's default preset
- **Multi-TP Support**: Multiple take profit levels with partial closes
- **Advanced Features**: Break-even, trailing stop, layering, hedging, weekly targets

## Default Presets

1. **Conservative Scalper** - Low risk (0.5%), quick profits, perfect for beginners
2. **Swing Trader** - Medium risk (1%), multiple TPs, break-even and trailing stop
3. **Aggressive Day Trader** - High risk (2%), layering enabled, ATR trailing stop
4. **Safe Long-Term** - Very conservative (0.25%), long-term strategy
5. **Grid Trading** - Grid/martingale with layering and hedging
6. **Breakout Trader** - Structure-based SL, Chandelier trailing stop

## Usage

### Seeding Default Presets

```bash
php artisan db:seed --class="Addons\TradingPresetAddon\Database\Seeders\TradingPresetSeeder"
```

### User Onboarding

When a new user is created, the system automatically:
- Assigns "Conservative Scalper" as their default preset
- Sets `users.default_preset_id` to the preset ID

### Connection Auto-Assignment

When a new execution connection is created:
- Automatically assigns the user's default preset
- Sets `execution_connections.preset_id` to user's default preset

## Integration

The addon integrates with:
- **Execution Engine**: Presets control position sizing, SL/TP, and advanced features
- **Copy Trading**: Presets can be assigned to copy trading subscriptions
- **Trading Bots**: Presets can be assigned to trading bots (future)

## Documentation

See full documentation in `specs/active/trading-preset/plan.md`

