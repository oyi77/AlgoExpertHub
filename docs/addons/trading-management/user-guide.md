# Trading Management Addon - User Guide

![diagram](../../images/README-5.svg)

## Overview

The **Trading Management Addon** is a comprehensive trading system that provides everything you need to automate your trading workflow—from data collection to trade execution and performance analysis.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Exchange Connections](#exchange-connections)
3. [Risk Management](#risk-management)
4. [Filter Strategies](#filter-strategies)
5. [AI Analysis](#ai-analysis)
6. [Trade Execution](#trade-execution)
7. [Position Monitoring](#position-monitoring)
8. [Backtesting](#backtesting)
9. [Copy Trading](#copy-trading)
10. [Trading Bots](#trading-bots)
11. [Marketplace](#marketplace)
12. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Accessing the Trading Management

1. Log in to your account
2. Navigate to **Trading** in the sidebar
3. You'll see the Trading Management dashboard with access to all modules

### Quick Setup (5 Minutes)

1. **Connect an Exchange** - Add your first exchange or broker connection
2. **Create a Risk Preset** - Define your risk management rules
3. **Enable Auto-Execution** - Turn on automatic trade execution for signals
4. **Monitor Positions** - Watch your trades in real-time

---

## Exchange Connections

### Supported Platforms

#### Cryptocurrency Exchanges (via CCXT)
- Binance, Coinbase, Kraken, Bitfinex, and 100+ more
- Spot and futures trading supported

#### Forex/CFD Brokers (via MT4/MT5)
- Any MT4/MT5 broker
- Integration via mtapi.io and metaapi.cloud

### Adding a Connection

#### For Crypto Exchanges

1. Go to **Trading → Exchange Connections**
2. Click **Add Connection**
3. Fill in the form:
   - **Name**: Give your connection a name (e.g., "Binance Main")
   - **Type**: Select "Crypto Exchange (CCXT)"
   - **Exchange**: Choose your exchange (e.g., Binance)
   - **API Key**: Your exchange API key
   - **API Secret**: Your exchange API secret
   - **Testnet**: Enable for testing (recommended first)
4. Click **Test Connection** to verify
5. Click **Save**

**Security Tips:**
- Use the **show/hide toggles** to verify your API keys before saving.
- Create API keys with **trading permissions only** (no withdrawal).
- Enable **IP whitelist** on your exchange.
- Use **testnet** first to verify everything works.

#### For MT4/MT5 Brokers

1. Go to **Trading → Exchange Connections**
2. Click **Add Connection**
3. Fill in the form:
   - **Name**: Connection name (e.g., "XM MT4")
   - **Type**: Select "MT4" or "MT5"
   - **Integration**: Choose "mtapi.io" or "metaapi.cloud"
   - **Account Number**: Your MT4/MT5 account number
   - **Password**: Your MT4/MT5 password
   - **Server**: Your broker's server (e.g., "XM-Real 1")
4. For mtapi.io:
   - **API Key**: Your mtapi.io API key
   - **Host**: mtapi.io host URL
5. For metaapi.cloud:
   - **Token**: Your metaapi.cloud token
   - **Account ID**: Your metaapi.cloud account ID
6. Click **Test Connection**
7. Click **Save**

### Managing Connections

- **Edit**: Update credentials or settings
- **Disable**: Temporarily disable a connection
- **Delete**: Remove a connection permanently
- **Test**: Verify connection is working
- **View Logs**: See connection activity

---

## Risk Management

Risk presets control how much you risk per trade and how you manage positions.

### Creating a Risk Preset

1. Go to **Trading → Risk Management**
2. Click **Create Preset**
3. Configure your preset:

#### Basic Settings
- **Preset Name**: e.g., "Conservative", "Aggressive"
- **Risk Type**: Fixed Amount, Percentage of Balance, or Custom
- **Risk Amount**: How much to risk per trade
  - Fixed: e.g., $100 per trade
  - Percentage: e.g., 2% of account balance

#### Position Sizing
- **Position Size Calculation**: 
  - Based on Stop Loss distance
  - Fixed lot size
  - Custom formula

#### Stop Loss & Take Profit
- **Stop Loss**: Required for all trades
  - Use signal's SL
  - Custom SL (pips/points)
  - Trailing stop
- **Take Profit**: 
  - Single TP
  - Multiple TPs (e.g., TP1: 50%, TP2: 30%, TP3: 20%)
  - Trailing TP

#### Advanced Features
- **Break-Even**: Move SL to entry after profit
- **Trailing Stop**: Follow price with SL
- **Partial Close**: Close portions at different levels
- **Hedging**: Open opposite positions

4. Click **Save Preset**

### Applying Presets

- **Default Preset**: Applied to all new connections
- **Per-Connection**: Assign different presets to different connections
- **Per-Signal**: Override preset for specific signals

### Pre-Built Presets

The addon comes with 6 ready-to-use presets:

1. **Scalper**: Small SL, quick profits, high frequency
2. **Swing**: Larger SL, bigger targets, lower frequency
3. **Aggressive**: High risk (5%), larger positions
4. **Safe**: Low risk (1%), smaller positions
5. **Grid**: Multiple entries, averaging strategy
6. **Breakout**: Momentum-based, tight SL

---

## Filter Strategies

Filter strategies use technical indicators to validate signals before execution.

### Creating a Filter Strategy

1. Go to **Trading → Filter Strategies**
2. Click **Create Strategy**
3. Configure filters:

#### Available Indicators
- **EMA (Exponential Moving Average)**: Trend direction
- **RSI (Relative Strength Index)**: Overbought/oversold
- **PSAR (Parabolic SAR)**: Trend reversal
- **MACD**: Momentum and trend
- **Bollinger Bands**: Volatility
- **Stochastic**: Momentum oscillator

#### Example: Trend-Following Strategy

```
Filter 1: EMA(50) > EMA(200) for BUY signals
Filter 2: RSI < 70 (not overbought)
Filter 3: PSAR below price (uptrend)

Action: Only execute BUY signals when all 3 conditions met
```

#### Configuration Options
- **Timeframe**: Which timeframe to check (1H, 4H, 1D)
- **Logic**: AND (all must pass) or OR (any can pass)
- **Action**: Execute, Skip, or Notify Only

4. Click **Save Strategy**

### Applying Filters

- Assign filter strategy to connections
- Signals that don't pass filters are skipped
- View filter results in execution logs

---

## AI Analysis

AI Analysis uses artificial intelligence to confirm market conditions before executing trades.

### Enabling AI Analysis

1. Go to **Trading → AI Analysis**
2. Click **Configure AI**
3. Select AI provider (managed via AI Connection Addon):
   - OpenAI (GPT-4, GPT-3.5)
   - Google Gemini
   - OpenRouter (400+ models)
4. Configure analysis:

#### Analysis Type
- **Market Confirmation**: Verify signal aligns with market conditions
- **Risk Adjustment**: AI suggests position size adjustments
- **Sentiment Analysis**: Analyze market sentiment

#### Prompts
- **System Prompt**: Instructions for AI (pre-configured)
- **Custom Prompts**: Add your own analysis criteria

#### Decision Making
- **Auto-Execute**: AI approves → execute automatically
- **Notify Only**: AI provides analysis, you decide
- **Reject on Negative**: AI rejects → skip signal

5. Click **Save Configuration**

### AI Analysis Results

View AI analysis in:
- **Execution Logs**: See AI decision for each signal
- **Analytics Dashboard**: AI approval rate, accuracy
- **Position Details**: AI reasoning for each trade

---

## Trade Execution

### How Execution Works

1. **Signal Published**: Admin publishes a trading signal
2. **Filter Check**: Signal passes through filter strategies (if enabled)
3. **AI Analysis**: AI confirms market conditions (if enabled)
4. **Risk Calculation**: Position size calculated based on risk preset
5. **Order Placement**: Trade executed on exchange/broker
6. **Position Created**: Position tracked in system

### Execution Settings

Go to **Trading → Execution Settings**:

#### Order Types
- **Market Order**: Execute immediately at current price
- **Limit Order**: Execute at specific price or better

#### Slippage Protection
- **Max Slippage**: Maximum price deviation allowed
- **Retry Logic**: Retry failed orders

#### Notifications
- **Execution Success**: Notify when trade executed
- **Execution Failure**: Notify when trade fails
- **Position Updates**: Notify on SL/TP hits

### Manual Execution

You can also manually execute signals:

1. Go to **Signals → Published Signals**
2. Click on a signal
3. Click **Execute Manually**
4. Select connection and preset
5. Review calculated position size
6. Click **Execute**

---

## Position Monitoring

All open positions are monitored in real-time.

### Viewing Positions

Go to **Trading → Positions**:

- **Open Positions**: Currently active trades
- **Closed Positions**: Trade history
- **Pending Orders**: Orders waiting to fill

### Position Details

Click on any position to see:

- **Entry Price**: Price you entered at
- **Current Price**: Real-time price
- **Profit/Loss**: Current P&L ($ and %)
- **Stop Loss**: Current SL level
- **Take Profit**: Current TP level(s)
- **Duration**: How long position has been open
- **Signal**: Original signal that triggered trade

### Managing Positions

#### Close Position
- **Full Close**: Close entire position
- **Partial Close**: Close percentage (e.g., 50%)

#### Modify Position
- **Move SL**: Adjust stop loss
- **Move TP**: Adjust take profit
- **Add TP**: Add additional TP levels

#### Auto-Management
- **SL/TP Monitoring**: System checks every minute
- **Auto-Close**: Closes when SL/TP hit
- **Notifications**: Alerts sent on close

---

## Backtesting

Test your strategies on historical data before risking real money.

### Creating a Backtest

1. Go to **Trading → Backtesting**
2. Click **New Backtest**
3. Configure backtest:

#### Basic Settings
- **Name**: Backtest name
- **Symbol**: Trading pair (e.g., EURUSD, BTCUSDT)
- **Timeframe**: Data timeframe (1H, 4H, 1D)
- **Date Range**: Historical period to test

#### Strategy Configuration
- **Filter Strategy**: Select filter to test
- **Risk Preset**: Select risk management
- **AI Analysis**: Enable/disable AI (uses historical data)

#### Data Source
- **Exchange Connection**: Use real historical data from exchange
- **Manual Upload**: Upload your own CSV data

4. Click **Run Backtest**

### Backtest Results

View comprehensive results:

#### Performance Metrics
- **Total Trades**: Number of trades executed
- **Win Rate**: Percentage of winning trades
- **Profit Factor**: Gross profit / Gross loss
- **Max Drawdown**: Largest peak-to-trough decline
- **Sharpe Ratio**: Risk-adjusted return
- **Average Win/Loss**: Average profit vs average loss

#### Equity Curve
- Visual chart showing account growth over time
- Drawdown periods highlighted

#### Trade List
- All individual trades with entry/exit
- P&L for each trade
- Filter/AI decisions

### Optimizing Strategies

Use backtest results to:
- Adjust filter parameters
- Optimize risk settings
- Compare different strategies
- Find best timeframes

---

## Copy Trading

Follow successful traders and automatically copy their trades.

### Finding Traders to Copy

1. Go to **Trading → Copy Trading → Marketplace**
2. Browse trader profiles:
   - **Performance**: Win rate, profit factor, drawdown
   - **Trading Style**: Scalper, swing, day trader
   - **Risk Level**: Conservative, moderate, aggressive
   - **Verified**: Platform-verified traders

### Following a Trader

1. Click on trader profile
2. Review their statistics and trade history
3. Click **Follow Trader**
4. Configure copy settings:

#### Copy Settings
- **Risk Preset**: Your risk management (can differ from trader)
- **Copy Ratio**: 
  - 1:1 - Same position size
  - 0.5:1 - Half their position size
  - 2:1 - Double their position size
- **Max Positions**: Limit concurrent copies
- **Symbols**: Copy all or specific symbols only

5. Click **Start Copying**

### Managing Followed Traders

- **Pause Copying**: Temporarily stop copying
- **Adjust Settings**: Change copy ratio or risk
- **Unfollow**: Stop copying permanently
- **View Performance**: See your results from copying

### Becoming a Trader (Share Your Trades)

1. Go to **Trading → Copy Trading → My Profile**
2. Enable **Allow Others to Copy**
3. Set your profile:
   - **Display Name**: Public name
   - **Description**: Your trading style
   - **Fee**: Optional fee for copiers (% of profit)
4. Your trades will be available for others to copy

---

## Trading Bots

Automate your trading with custom bots.

### Creating a Trading Bot

1. Go to **Trading → Trading Bots**
2. Click **Create Bot**
3. Follow the step-by-step form:

#### Step 1: Basic Information
- **Bot Name**: Give your bot a descriptive name (e.g., "Binance Scalper Bot")
- **Description**: Optional description of bot strategy

#### Step 2: Exchange Connection
- **Select Exchange/Broker**: Choose an active exchange connection
  - ✅ **Health Badge**: Shows connection status (Active, Error, Testing, Inactive)
  - ✅ **Requirements Info**: Displays exchange-specific credential requirements
  - ✅ **Create New Connection**: Click "+" button to create connection inline without leaving the page
- **Connection Requirements**:
  - Connection must be **active** (`status = 'active'` and `is_active = true`)
  - Connection must belong to you (not admin-owned or another user's)
  - Connection must have valid credentials (API key, secret, and passphrase if required)

**Exchange-Specific Requirements**:
- **Binance, Bybit**: Requires API Key and Secret only
- **OKX, KuCoin, Coinbase Pro**: Requires API Key, Secret, AND Passphrase
- **Other Exchanges**: Check requirements in connection health info

**Inline Connection Creation**:
- Click the **"+"** button next to the connection dropdown
- Fill in connection details in the modal:
  - Connection name
  - Exchange type (Crypto or FX)
  - Exchange/Provider selection
  - Connection purpose (Data, Execution, or Both)
  - API credentials (fields show/hide based on exchange)
- Click **"Create & Test Connection"**
- Connection is created and automatically selected

#### Step 3: Risk Management Preset
- **Select Trading Preset**: Choose a risk management preset
  - Defines position sizing, stop loss, take profit rules
  - Can be customized per bot

#### Step 4: Technical Indicator Filter (Optional)
- **Select Filter Strategy**: Apply technical indicators to filter signals
  - Examples: MA100, MA10, Parabolic SAR
  - Only signals passing filters will be executed
  - Leave empty to execute all signals

#### Step 5: AI Market Confirmation (Optional)
- **Select AI Model Profile**: Use AI to validate signals before execution
  - AI analyzes market conditions
  - Provides safety score
  - Can auto-reject risky signals

#### Step 6: Trading Mode
- **Signal-Based**: Execute only when signals are published
- **Market Stream-Based**: Continuously stream OHLCV data and apply technical indicators
  - Requires data connection
  - Configure streaming symbols and timeframes
  - Set market analysis interval

#### Step 7: Trading Settings
- **Paper Trading Mode**: Enable demo mode (no real money)
  - ✅ Recommended for testing
  - Toggle on/off with checkbox

#### Step 8: Advanced Configuration (Optional)
- **Data Fetch Interval**: How often to fetch market data
- **Filter Priority**: Configure multiple filters with priority order

4. Click **Create Trading Bot**

**Progress Indicator**: The form includes a progress stepper at the top showing your completion status through all 7 steps.

### Running Bots

- **Start Bot**: Begin automated trading
- **Pause Bot**: Temporarily stop
- **Stop Bot**: End bot and close positions
- **Backtest Bot**: Test bot on historical data first

### Monitoring Bots

View bot activity:
- **Active Bots**: Currently running
- **Bot Performance**: P&L, trades, win rate
- **Bot Logs**: All bot actions and decisions
- **Alerts**: Notifications on bot events

---

## Marketplace

Share and discover trading strategies, bots, and presets.

### Browsing Marketplace

1. Go to **Trading → Marketplace**
2. Browse categories:
   - **Bot Templates**: Pre-configured trading bots
   - **Risk Presets**: Community risk management setups
   - **Filter Strategies**: Technical indicator strategies
   - **Trader Profiles**: Traders to copy

### Using Marketplace Items

1. Click on item
2. View details and performance
3. Click **Use This** or **Clone**
4. Customize for your needs
5. Save to your account

### Sharing to Marketplace

1. Create a bot, preset, or strategy
2. Click **Share to Marketplace**
3. Set details:
   - **Title & Description**
   - **Category**
   - **Price**: Free or paid
   - **Visibility**: Public or private
4. Submit for review (admin approval required)

---

## Troubleshooting

### Bot Creation Issues

**Problem**: "The selected exchange connection is invalid or not active"

**Solutions**:
1. **Check Connection Status**:
   - Go to **Trading → Exchange Connections**
   - Verify connection status is "Active" (green badge)
   - Ensure `is_active` is enabled
   - If inactive, click "Activate" or fix connection errors

2. **Verify Connection Ownership**:
   - Connection must belong to you (not admin-owned)
   - If using admin connection, create your own connection

3. **Check Connection Credentials**:
   - Go to connection details
   - Click "Test Connection"
   - Fix any credential errors
   - Re-activate connection after fixing

**Problem**: "Connection missing required credentials"

**Solutions**:
1. **For Binance/Bybit**:
   - Ensure API Key is present
   - Ensure API Secret is present
   - Passphrase NOT required

2. **For OKX/KuCoin/Coinbase Pro**:
   - Ensure API Key is present
   - Ensure API Secret is present
   - ✅ **Ensure API Passphrase is present** (required for these exchanges)
   - Go to connection settings and add missing passphrase

3. **Verify Credentials in Exchange**:
   - Log into your exchange account
   - Go to API Management
   - Verify API key is active
   - Check API permissions (trading enabled)
   - For OKX/KuCoin: Verify passphrase is correct

**Problem**: Bot creation form shows validation errors

**Solutions**:
1. **Check Required Fields**:
   - Bot name is filled
   - Exchange connection is selected
   - Trading preset is selected
   - Trading mode is selected

2. **Check Connection Health**:
   - Look at connection health badge
   - Red badge = Connection has errors (fix before using)
   - Yellow badge = Connection testing (wait for completion)
   - Gray badge = Connection inactive (activate first)
   - Green badge = Connection ready ✅

3. **Review Error Messages**:
   - Read validation error messages carefully
   - They indicate exactly what's wrong
   - Fix the issue and retry

**Problem**: Inline connection creation fails

**Solutions**:
1. **Check Credentials**:
   - Verify API key format is correct
   - Verify API secret is correct
   - For OKX/KuCoin: Verify passphrase is correct
   - Check for typos or extra spaces

2. **Check Exchange Type**:
   - Ensure correct exchange type selected (Crypto vs FX)
   - Ensure correct exchange name selected

3. **Test Connection Separately**:
   - Create connection via main Exchange Connections page
   - Test connection there first
   - Then use in bot creation

4. **Check Network**:
   - Ensure internet connection is stable
   - Check if exchange API is accessible
   - Try again after a moment

### Connection Issues

**Problem**: "Connection failed" error

**Solutions**:
- Verify API credentials are correct
- Check IP whitelist on exchange
- Ensure API has trading permissions
- Test with testnet first
- Check exchange/broker status
- For OKX/KuCoin: Verify passphrase is correct

### Execution Failures

**Problem**: Trades not executing

**Solutions**:
- Check connection is active
- Verify sufficient balance
- Check filter strategy (might be blocking)
- Review AI analysis (might be rejecting)
- Check symbol availability on exchange
- Review execution logs for details
- Verify bot is active and running

### Position Not Closing

**Problem**: SL/TP not triggering

**Solutions**:
- Verify position monitoring is running
- Check connection is active
- Ensure SL/TP levels are valid
- Review position logs
- Manually close if needed

### Backtest Not Running

**Problem**: Backtest stuck or failing

**Solutions**:
- Check date range has sufficient data
- Verify symbol is available on exchange
- Reduce date range for faster processing
- Check system resources
- Review backtest logs

### AI Analysis Not Working

**Problem**: AI not providing analysis

**Solutions**:
- Verify AI Connection Addon is active
- Check AI provider has valid credentials
- Ensure sufficient API credits
- Review AI connection logs
- Try different AI provider

---

## Support

### Getting Help

- **Documentation**: This guide and API docs
- **Support Tickets**: Submit ticket in platform
- **Community Forum**: Ask questions, share strategies
- **Video Tutorials**: Step-by-step guides (coming soon)

### Best Practices

1. **Start Small**: Test with small amounts first
2. **Use Testnet**: Verify everything works before live trading
3. **Backtest First**: Test strategies on historical data
4. **Monitor Regularly**: Check positions and bot performance
5. **Set Limits**: Use max drawdown and position limits
6. **Diversify**: Don't put all capital in one strategy
7. **Keep Learning**: Review performance and optimize

### Safety Tips

- Never share API keys
- Use API keys with trading-only permissions
- Enable 2FA on exchanges
- Start with demo/testnet
- Set stop losses on all trades
- Don't risk more than you can afford to lose
- Regularly review and adjust strategies
- **Always verify connection health before creating bots**
- **Test connections in paper trading mode first**
- **Keep passphrases secure (required for OKX, KuCoin, Coinbase Pro)**

---

## Quick Reference

### Common Tasks

| Task | Location | Steps |
|------|----------|-------|
| Add Exchange | Trading → Connections | Click Add, fill form, test, save |
| Create Risk Preset | Trading → Risk Management | Click Create, configure, save |
| Enable Auto-Execution | Trading → Execution Settings | Toggle auto-execute ON |
| View Positions | Trading → Positions | See all open/closed trades |
| Run Backtest | Trading → Backtesting | New Backtest, configure, run |
| Follow Trader | Trading → Copy Trading | Browse, select, follow |
| Create Bot | Trading → Trading Bots | Create, configure, start |

### Keyboard Shortcuts

- `Ctrl+P`: Quick search positions
- `Ctrl+E`: Quick execute signal
- `Ctrl+B`: Open backtesting
- `Ctrl+M`: View marketplace

---

**Need more help?** Contact support or visit our community forum!
