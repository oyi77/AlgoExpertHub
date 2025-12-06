# Trading Bots Integration Guide

This document explains how to integrate Trading Preset addon with Trading Bots.

## Overview

The Trading Preset addon provides enhanced position sizing, risk management, and trading rules for trading bots. When a bot has a preset assigned, the preset's configuration will be used to calculate position sizes, apply trading rules, and determine SL/TP levels.

## Database Schema

The `trading_bots` table should have a `preset_id` column:

```sql
ALTER TABLE trading_bots
  ADD COLUMN preset_id BIGINT UNSIGNED NULL,
  ADD INDEX idx_preset_id (preset_id),
  ADD FOREIGN KEY (preset_id) REFERENCES trading_presets(id) ON DELETE SET NULL;
```

Migration is provided: `2025_01_29_100006_add_preset_id_to_trading_bots.php`

## Integration Points

### 1. Preset Resolution

The `TradingBotEnhancer` service resolves presets using the following hierarchy:

1. **Bot preset** (`bot.preset_id`) - Highest priority
2. **Connection preset** (`connection.preset_id`)
3. **User default preset** (`user.default_preset_id`)
4. **System default preset** - Lowest priority

### 2. Pre-Execution Checks

Before executing a signal, check if preset rules allow execution:

```php
use Addons\TradingPresetAddon\App\Services\TradingBotEnhancer;

// In your bot execution logic
$enhancer = app(TradingBotEnhancer::class);
$check = $enhancer->canExecuteSignal($bot, $connection, $signal, $user);

if (!$check['allowed']) {
    Log::info("Bot execution blocked by preset rules", [
        'bot_id' => $bot->id,
        'signal_id' => $signal->id,
        'reason' => $check['reason'],
    ]);
    return; // Skip execution
}
```

### 3. Order Options Enhancement

Enhance order options with preset configurations:

```php
use Addons\TradingPresetAddon\App\Services\TradingBotEnhancer;

// In your bot execution logic
$enhancer = app(TradingBotEnhancer::class);

$baseOptions = [];
$orderOptions = $enhancer->enhanceOrderOptions(
    $bot,
    $connection,
    $signal,
    $user,
    $baseOptions
);

// Execute signal with enhanced options
$result = $signalExecutionService->executeSignal(
    $signal,
    $connection->id,
    $orderOptions
);
```

### 4. Complete Integration Example

```php
use Addons\TradingPresetAddon\App\Services\TradingBotEnhancer;
use Addons\TradingExecutionEngine\App\Services\SignalExecutionService;

class BotExecutionService
{
    protected TradingBotEnhancer $botEnhancer;
    protected SignalExecutionService $signalExecutionService;

    public function executeBotSignal($bot, Signal $signal): array
    {
        $connection = $bot->connection; // Assuming bot has connection
        $user = $bot->user; // Assuming bot has user

        // Check if preset allows execution
        $check = $this->botEnhancer->canExecuteSignal($bot, $connection, $signal, $user);
        if (!$check['allowed']) {
            return [
                'success' => false,
                'message' => $check['reason'],
            ];
        }

        // Enhance order options
        $orderOptions = $this->botEnhancer->enhanceOrderOptions(
            $bot,
            $connection,
            $signal,
            $user,
            []
        );

        // Execute signal
        $result = $this->signalExecutionService->executeSignal(
            $signal,
            $connection->id,
            $orderOptions
        );

        return $result;
    }
}
```

## Features

### Position Sizing

- **RISK_PERCENT mode**: Calculates position size based on risk percentage of connection's equity
- **FIXED mode**: Uses fixed lot size from preset
- Automatically fetches balance from connection adapter

### Trading Rules

- **Symbol Filter**: Only execute signals matching preset's symbol (or '*' for all)
- **Timeframe Filter**: Only execute signals matching preset's timeframe (or '*' for all)
- **Trading Schedule**: Checks if trading is allowed at current time
- **Weekly Target**: Prevents trading if weekly target is reached
- **Max Positions**: Enforces maximum positions per symbol/connection

### Stop Loss & Take Profit

- **SL Calculation**: Uses preset SL mode (PIPS, R_MULTIPLE, STRUCTURE)
- **Multi-TP**: Supports multiple take profit levels if configured
- **Structure SL**: Supports structure-based SL from signal

## Preset Configuration

When creating or editing a bot, you can assign a preset:

```php
$bot = TradingBot::find($botId);
$bot->preset_id = $presetId;
$bot->save();
```

The preset will be automatically used for all signals executed by this bot.

## Notes

- The integration is designed to be non-breaking. If preset addon is not available, bots work as before.
- Preset rules are applied in addition to bot settings, not replacing them.
- Bot preset takes highest priority in the resolution hierarchy.
- If no preset is assigned to bot, it falls back to connection preset, then user default, then system default.

