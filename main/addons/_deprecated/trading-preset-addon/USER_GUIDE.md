# Trading Preset User Guide

Complete guide for using Trading Presets in your trading system.

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Understanding Presets](#understanding-presets)
4. [Creating Your First Preset](#creating-your-first-preset)
5. [Preset Configuration](#preset-configuration)
6. [Applying Presets](#applying-presets)
7. [Advanced Features](#advanced-features)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

## Introduction

Trading Presets are reusable configurations that control how your trades are executed. They allow you to:
- Set position sizing rules
- Configure stop loss and take profit levels
- Enable advanced features like break-even and trailing stops
- Apply consistent risk management across all trades

## Getting Started

### For New Users

When you first create an account, the system automatically assigns you a **Conservative Scalper** preset. This preset is designed for beginners with:
- Low risk (0.5% per trade)
- Quick profit targets
- Simple configuration

### Your First Connection

When you create your first execution connection, it automatically uses your default preset. You can change this later in the connection settings.

## Understanding Presets

### Preset Hierarchy

Presets are applied in this order (highest to lowest priority):

1. **Bot Preset** - If you're using a trading bot
2. **Subscription Preset** - If you're copy trading
3. **Connection Preset** - Assigned to your execution connection
4. **Your Default Preset** - Your personal default
5. **System Default** - Fallback preset

### Preset Types

- **Private**: Only you can see and use
- **Public**: Visible to all users (read-only, can be cloned)
- **Default Template**: System presets (read-only)

## Creating Your First Preset

### Step 1: Navigate to Presets

1. Go to **Trading** → **My Presets**
2. Click **Create New Preset**

### Step 2: Basic Information

- **Name**: Give your preset a descriptive name (e.g., "EURUSD Scalper")
- **Description**: Optional description
- **Symbol**: Optional symbol filter (leave empty for all symbols)
- **Timeframe**: Optional timeframe filter
- **Tags**: Add tags for organization

### Step 3: Position & Risk

**Position Size Mode:**
- **FIXED**: Use a fixed lot size (e.g., 0.1 lot)
- **RISK_PERCENT**: Risk a percentage of your equity (recommended)

**Risk Settings:**
- **Risk Per Trade**: Percentage of equity to risk (1-5% recommended)
- **Max Positions**: Maximum open positions at once
- **Max Positions Per Symbol**: Maximum positions per symbol

### Step 4: Stop Loss & Take Profit

**Stop Loss Mode:**
- **PIPS**: Fixed distance in pips
- **R_MULTIPLE**: Based on risk-reward ratio
- **STRUCTURE**: Use structure-based price (manual)

**Take Profit:**
- **SINGLE**: One take profit level
- **MULTI**: Up to 3 take profit levels with partial closes

### Step 5: Advanced Features (Optional)

- **Break-Even**: Automatically move SL to break-even
- **Trailing Stop**: Trail stop loss as price moves favorably
- **Trading Schedule**: Restrict trading to specific hours/days
- **Weekly Target**: Set weekly profit targets

### Step 6: Save

Click **Save** to create your preset. You can now assign it to connections, bots, or subscriptions.

## Preset Configuration

### Position Sizing

#### Fixed Lot Size

Use when you want consistent position sizes:

```
Position Size Mode: FIXED
Fixed Lot: 0.1
```

#### Risk-Based Sizing (Recommended)

Risk a percentage of your equity:

```
Position Size Mode: RISK_PERCENT
Risk Per Trade: 1.0%
```

The system calculates position size based on:
- Your account equity
- Entry price
- Stop loss distance

### Stop Loss Configuration

#### PIPS Mode

Fixed distance in pips:

```
SL Mode: PIPS
SL Pips: 50
```

#### R-Multiple Mode

Based on risk-reward:

```
SL Mode: R_MULTIPLE
SL R Multiple: 1.5
```

This means: If you risk 1R, your SL is 1.5R away.

#### Structure Mode

Use structure-based price (requires manual price input):

```
SL Mode: STRUCTURE
```

The structure price comes from your signal or manual entry.

### Take Profit Configuration

#### Single TP

```
TP Mode: SINGLE
TP1 Enabled: Yes
TP1 R:R: 2.0
```

#### Multi-TP

```
TP Mode: MULTI
TP1 Enabled: Yes, TP1 R:R: 1.5, TP1 Close %: 50%
TP2 Enabled: Yes, TP2 R:R: 2.5, TP2 Close %: 30%
TP3 Enabled: Yes, TP3 R:R: 3.5, TP3 Close %: 20%
```

This closes:
- 50% at 1.5R
- 30% at 2.5R
- 20% at 3.5R

### Break-Even

Automatically move stop loss to break-even when profit target is reached:

```
Break-Even Enabled: Yes
BE Trigger R:R: 1.0
BE Offset Pips: 5
```

This moves SL to entry + 5 pips when price reaches 1R profit.

### Trailing Stop

Trail stop loss as price moves favorably:

```
Trailing Stop Enabled: Yes
TS Trigger R:R: 1.5
TS Mode: STEP_PIPS
TS Step Pips: 20
```

**Modes:**
- **STEP_PIPS**: Fixed pips distance
- **STEP_ATR**: ATR-based distance (requires price history)
- **CHANDELIER**: Volatility-based (requires price history)

### Trading Schedule

Restrict trading to specific times:

```
Only Trade In Session: Yes
Trading Hours Start: 08:00
Trading Hours End: 17:00
Trading Timezone: UTC
Trading Days: Monday-Friday
Session Profile: LONDON
```

### Weekly Target

Set weekly profit targets:

```
Weekly Target Enabled: Yes
Weekly Target Profit %: 5.0%
Weekly Reset Day: Monday
Auto Stop On Weekly Target: Yes
```

This stops new trades when weekly target is reached.

## Applying Presets

### To Execution Connection

1. Go to **Trading** → **My Connections**
2. Edit your connection
3. Select a preset from **Preset** dropdown
4. Save

### To Copy Trading Subscription

1. Go to **Copy Trading** → **My Subscriptions**
2. Edit your subscription
3. Select a preset from **Preset** dropdown
4. Save

### To Trading Bot

1. Go to **Trading Bots** → **My Bots**
2. Edit your bot
3. Select a preset from **Preset** dropdown
4. Save

### Set as Default

1. Go to **Trading** → **My Presets**
2. Find your preset
3. Click **Set as Default**

This preset will be used for new connections automatically.

## Advanced Features

### Dynamic Equity

Adjust position sizing based on account performance:

**LINEAR Mode:**
- Position size scales linearly with equity changes
- Example: If equity doubles, position size doubles

**STEP Mode:**
- Position size increases in steps
- Example: Every $1000 increase = 10% position size increase

### ATR-Based Calculations

Requires price history data from your broker:

**ATR Trailing Stop:**
- Uses ATR to determine trailing distance
- More adaptive to market volatility

**Chandelier Stop:**
- Uses highest high/lowest low + ATR
- Good for trend-following strategies

### Candle-Based Exit

Automatically close positions on candle close:

```
Auto Close On Candle Close: Yes
Auto Close Timeframe: 1h
Hold Max Candles: 24
```

This closes positions after 24 hours (24 x 1h candles).

## Best Practices

### Risk Management

1. **Never risk more than 2% per trade** (1% recommended for beginners)
2. **Use RISK_PERCENT mode** instead of fixed lots
3. **Set max positions** to limit exposure
4. **Use weekly targets** to prevent overtrading

### Position Sizing

1. **Calculate based on stop loss distance**
2. **Use dynamic equity** for compounding strategies
3. **Adjust for different symbols** (create symbol-specific presets)

### Stop Loss

1. **Always use stop loss** (never trade without SL)
2. **Use structure-based SL** when possible (more accurate)
3. **Enable break-even** to protect profits
4. **Use trailing stop** for trend-following strategies

### Take Profit

1. **Use multi-TP** to lock in profits gradually
2. **Set realistic R:R ratios** (1.5-3.0 recommended)
3. **Don't be too greedy** (take profits at reasonable levels)

### Testing

1. **Test presets on demo account first**
2. **Start with conservative settings**
3. **Gradually increase risk** as you gain experience
4. **Monitor performance** and adjust accordingly

## Troubleshooting

### Preset Not Applied

**Problem**: Preset settings not being used

**Solutions**:
1. Check preset hierarchy (bot > subscription > connection > user default)
2. Verify preset is enabled
3. Check connection settings for overrides
4. Ensure preset is assigned to connection

### Position Size Too Large/Small

**Problem**: Position sizes don't match expectations

**Solutions**:
1. Check equity amount (may be different from balance)
2. Verify risk percentage setting
3. Check stop loss distance (affects position size calculation)
4. Review dynamic equity settings

### Trailing Stop Not Working

**Problem**: Trailing stop not updating

**Solutions**:
1. Verify trailing stop is enabled
2. Check trigger R:R is reached
3. For ATR/Chandelier: Ensure price history is available
4. Check update interval setting

### Trading Schedule Not Working

**Problem**: Trades still executing outside schedule

**Solutions**:
1. Verify "Only Trade In Session" is enabled
2. Check timezone setting
3. Verify trading hours are correct
4. Check trading days mask

### Weekly Target Not Tracking

**Problem**: Weekly target not being enforced

**Solutions**:
1. Verify weekly target is enabled
2. Check reset day setting
3. Ensure "Auto Stop On Weekly Target" is enabled
4. Check connection has closed positions for tracking

## Support

For additional help:
- Check documentation in `ADVANCED_FEATURES.md`
- Review integration guides for specific features
- Contact support if issues persist

