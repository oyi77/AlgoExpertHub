# User Guide - Trading Management Platform

**Version**: 2.0  
**Last Updated**: 2025-12-05

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Trading Management Overview](#trading-management-overview)
3. [Setting Up Connections](#setting-up-connections)
4. [Trading Bots](#trading-bots)
5. [Copy Trading](#copy-trading)
6. [Risk Management](#risk-management)
7. [Analytics](#analytics)
8. [Troubleshooting](#troubleshooting)

---

## Getting Started

### 1. Account Setup

1. Register an account
2. Verify your email
3. Complete KYC (if required)
4. Subscribe to a plan

### 2. First Steps

1. **Create Exchange Connection**
   - Go to Trading Management → Operations → Connections
   - Click "Create Connection"
   - Select exchange type (Crypto or FX)
   - Enter API credentials
   - Test connection

2. **Set Up Risk Preset**
   - Go to Trading Management → Configuration → Risk Presets
   - Choose a preset or create custom
   - Configure position sizing, SL/TP rules

3. **Create Trading Bot** (Optional)
   - Go to Trading Management → Strategy → Trading Bots
   - Select template or create custom
   - Configure filters and AI models

---

## Trading Management Overview

The platform is organized into 5 main sections:

### 1. Trading Configuration
- **Risk Presets**: Configure position sizing and risk rules
- **Smart Risk Settings**: AI-powered adaptive risk management
- **Trading Presets**: Pre-configured trading strategies

### 2. Trading Strategy
- **Filter Strategies**: Technical indicator filters
- **AI Model Profiles**: AI models for signal analysis
- **Trading Bots**: Automated trading bots

### 3. Trading Operations
- **Connections**: Exchange/broker connections
- **Executions**: Trade execution history
- **Positions**: Open and closed positions
- **Analytics**: Performance metrics

### 4. Copy Trading
- **Traders**: Browse and follow traders
- **Subscriptions**: Manage copy trading subscriptions
- **History**: Copy trading history

### 5. Backtesting
- **Create Backtest**: Test strategies on historical data
- **Results**: View backtest performance

---

## Setting Up Connections

### Crypto Exchange (CCXT)

1. Go to **Trading Operations → Connections**
2. Click **Create Connection**
3. Select **Crypto Exchange (CCXT)**
4. Choose exchange (Binance, Coinbase, etc.)
5. Enter API credentials:
   - API Key
   - API Secret
   - API Passphrase (if required)
6. Test connection
7. Activate connection

### MT4/MT5 (Forex)

1. Go to **Trading Operations → Connections**
2. Click **Create Connection**
3. Select **Forex Broker (MT4/MT5)**
4. Choose **MT4** or **MT5**
5. Enter mtapi.io credentials:
   - mtapi.io API Key
   - mtapi.io Account ID
   - MT4/MT5 Account Number
   - MT4/MT5 Server Name
6. Test connection
7. Activate connection

---

## Trading Bots

### Creating a Bot

1. Go to **Trading Strategy → Trading Bots**
2. Click **Create Bot**
3. Fill in:
   - Bot name
   - Exchange connection
   - Trading preset
   - Filter strategy (optional)
   - AI model profile (optional)
4. Save and activate

### Bot Templates

Browse prebuilt templates in **Marketplace**:
- Conservative Scalper
- Swing Trader
- Aggressive Day Trader
- Breakout Trader
- Grid Trading Bot
- Trend Following Bot

---

## Copy Trading

### Following a Trader

1. Go to **Copy Trading → Traders**
2. Browse available traders
3. Click **Subscribe**
4. Configure:
   - Risk multiplier
   - Trading preset
   - Copy settings
5. Confirm subscription

### Managing Subscriptions

- View active subscriptions
- Adjust risk multiplier
- Pause/resume copying
- View copy history

---

## Risk Management

### Trading Presets

Choose from 6 default presets or create custom:

1. **Scalper**: Fast entries/exits, tight SL
2. **Swing Trader**: Longer holds, wider SL
3. **Aggressive**: Higher risk, larger positions
4. **Safe**: Lower risk, smaller positions
5. **Grid Trading**: Multiple TP levels
6. **Breakout**: Breakout-based entries

### Smart Risk Management

Enable AI Smart Risk in preset settings:
- Automatically adjusts lot size based on signal provider performance
- Adds slippage buffer to SL
- Filters low-quality signals

---

## Analytics

### Viewing Analytics

1. Go to **Trading Operations → Analytics**
2. Select connection
3. Choose time period (7/30/90/365 days)
4. View metrics:
   - Total trades
   - Win rate
   - Profit factor
   - Sharpe ratio
   - Max drawdown

### Exporting Reports

- Click **Export CSV** or **Export JSON**
- Reports include:
  - Summary metrics
  - Individual trade details
  - Performance charts

### Channel Comparison

1. Go to **Trading Operations → Analytics → Compare**
2. Select multiple connections
3. Compare performance side-by-side
4. Identify best performers

---

## Troubleshooting

### Connection Issues

**Problem**: Connection test fails
- **Solution**: Verify API credentials
- Check API permissions (trading enabled)
- Check IP whitelist (if required)

### Execution Failures

**Problem**: Trades not executing
- **Solution**: 
  - Check connection is active
  - Verify account has sufficient balance
  - Check exchange status

### Position Not Updating

**Problem**: Positions show old prices
- **Solution**: 
  - Check connection status
  - Verify adapter is working
  - Check queue workers running

---

## Support

For issues:
1. Check this guide
2. Review troubleshooting section
3. Create support ticket
4. Contact admin

---

**Last Updated**: 2025-12-05
