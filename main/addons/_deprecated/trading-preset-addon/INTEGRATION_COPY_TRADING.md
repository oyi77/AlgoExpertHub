# Copy Trading Integration Guide

This document explains how to integrate Trading Preset addon with Copy Trading addon.

## Overview

The Trading Preset addon provides enhanced position sizing, risk management, and trading rules for copy trading subscriptions. When a subscription has a preset assigned, the preset's configuration will be used to calculate position sizes and apply trading rules.

## Integration Points

### 1. Enhanced Quantity Calculation

The `CopyTradingEnhancer` service can enhance the quantity calculation in `TradeCopyService::calculateCopiedQuantity()`.

**Example Integration:**

```php
// In TradeCopyService::calculateCopiedQuantity()

protected function calculateCopiedQuantity(ExecutionPosition $traderPosition, CopyTradingSubscription $subscription): array
{
    // ... existing base calculation logic ...
    $baseCalculation = [
        'quantity' => $quantity,
        'risk_multiplier' => $subscription->risk_multiplier ?? null,
        'details' => $details,
    ];

    // Enhance with preset if available
    if (class_exists(\Addons\TradingPresetAddon\App\Services\CopyTradingEnhancer::class)) {
        try {
            $enhancer = app(\Addons\TradingPresetAddon\App\Services\CopyTradingEnhancer::class);
            $baseCalculation['trader_position'] = $traderPosition; // Add context
            $baseCalculation = $enhancer->enhanceCopiedQuantity(
                $traderPosition,
                $subscription,
                $baseCalculation
            );
        } catch (\Exception $e) {
            // Fallback to base calculation if enhancement fails
            Log::warning("Preset enhancement failed", ['error' => $e->getMessage()]);
        }
    }

    return $baseCalculation;
}
```

### 2. Execution Checks

Before executing a copied trade, you can check preset rules:

```php
// In TradeCopyService::copyToFollower()

protected function copyToFollower(ExecutionPosition $traderPosition, CopyTradingSubscription $subscription): void
{
    $followerConnection = $subscription->connection;
    
    // Check preset rules before execution
    if (class_exists(\Addons\TradingPresetAddon\App\Services\CopyTradingEnhancer::class)) {
        try {
            $enhancer = app(\Addons\TradingPresetAddon\App\Services\CopyTradingEnhancer::class);
            $check = $enhancer->enhanceCopyExecution($traderPosition, $subscription, $followerConnection);
            
            if (!$check['allowed']) {
                $this->createFailedExecution(
                    $traderPosition,
                    $subscription,
                    $check['reason'] ?? 'Preset rules prevented execution'
                );
                return;
            }
        } catch (\Exception $e) {
            Log::warning("Preset check failed", ['error' => $e->getMessage()]);
        }
    }

    // ... continue with existing logic ...
}
```

### 3. Order Options Enhancement

Enhance order options (SL/TP) with preset configurations:

```php
// In TradeCopyService::executeCopiedTrade()

protected function executeCopiedTrade(ExecutionPosition $traderPosition, ExecutionConnection $followerConnection, float $quantity): array
{
    $signal = $traderPosition->signal;
    
    $orderOptions = ['quantity' => $quantity];
    
    // Enhance with preset SL/TP
    if (class_exists(\Addons\TradingPresetAddon\App\Services\CopyTradingEnhancer::class)) {
        try {
            $enhancer = app(\Addons\TradingPresetAddon\App\Services\CopyTradingEnhancer::class);
            $subscription = CopyTradingSubscription::where('connection_id', $followerConnection->id)
                ->where('follower_id', $followerConnection->user_id)
                ->first();
            
            if ($subscription) {
                $orderOptions = $enhancer->enhanceCopiedOrderOptions(
                    $traderPosition,
                    $subscription,
                    $followerConnection,
                    $orderOptions
                );
            }
        } catch (\Exception $e) {
            Log::warning("Preset order enhancement failed", ['error' => $e->getMessage()]);
        }
    }

    // Execute with enhanced options
    $result = $this->signalExecutionService->executeSignal(
        $signal,
        $followerConnection->id,
        $orderOptions
    );

    return $result;
}
```

## Preset Resolution Priority

For copy trading, the preset resolution follows this priority:

1. **Subscription preset** (`subscription.preset_id`) - Highest priority
2. **Connection preset** (`connection.preset_id`)
3. **User default preset** (`user.default_preset_id`)
4. **System default preset** - Lowest priority

## Features

### Position Sizing

- **RISK_PERCENT mode**: Calculates position size based on risk percentage of follower's equity
- **FIXED mode**: Uses fixed lot size from preset
- Still respects subscription's risk multiplier and max position size limits

### Trading Rules

- **Trading Schedule**: Checks if trading is allowed at current time
- **Weekly Target**: Prevents trading if weekly target is reached
- **Max Positions**: Enforces maximum positions per symbol/connection

### Stop Loss & Take Profit

- **SL Calculation**: Uses preset SL mode (PIPS, R_MULTIPLE, STRUCTURE)
- **Multi-TP**: Supports multiple take profit levels if configured

## Notes

- The integration is designed to be non-breaking. If preset addon is not available, copy trading works as before.
- Preset rules are applied in addition to subscription settings, not replacing them.
- Risk multiplier and max position size from subscription are still respected.

