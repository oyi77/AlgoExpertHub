# Investor Demo Plan - Coinrule-Like Trading Bot Platform

**Date**: 2025-12-04  
**Purpose**: Demo for potential investor  
**Target**: Coinrule-like automated trading bot platform

---

## Demo Requirements

### Core Features to Demonstrate

1. **Automated Trading Bot Control**
   - Create trading bots with technical indicators
   - Configure risk management presets
   - Connect to exchanges (crypto/FX)
   - Real-time monitoring

2. **Technical Indicators**
   - EMA (Fast/Slow)
   - Stochastic Oscillator
   - Parabolic SAR
   - Custom rule combinations

3. **Risk Management**
   - Position sizing (fixed/risk-based)
   - Stop Loss (PIPS/R-Multiple/Structure)
   - Take Profit (Single/Multi-TP)
   - Advanced features (Break-Even, Trailing Stop)

4. **Exchange Integration**
   - Crypto exchanges (Binance, Coinbase, Kraken via CCXT)
   - FX brokers (MT4/MT5 via mtapi.io)
   - Paper trading mode for demo

---

## Feature Mapping: Coinrule vs Our Platform

| Coinrule Feature | Our Platform Equivalent | Status |
|-----------------|------------------------|--------|
| Create Trading Bots | Trading Bot Builder (Preset + Filter Strategy) | ⚠️ Needs UI |
| Technical Indicators | Filter Strategies (EMA, Stochastic, PSAR) | ✅ Ready |
| Risk Management | Trading Presets (Position sizing, SL/TP) | ✅ Ready |
| Exchange Connections | Execution Connections (CCXT/MT4) | ✅ Ready |
| Automated Execution | Signal Execution Engine | ✅ Ready |
| Position Monitoring | Execution Positions (Real-time) | ✅ Ready |
| Analytics | Execution Analytics (Win rate, Profit factor) | ✅ Ready |
| Backtesting | Trading Test Module | ⚠️ Partial |
| No-Code Setup | Admin/User UI | ⚠️ Needs consolidation |

---

## Demo Architecture

### 1. Trading Bot Builder (NEW - Main Feature)

**Purpose**: Unified interface to create trading bots (like Coinrule)

**Components**:
- **Bot Configuration**: Name, description, enabled/disabled
- **Exchange Connection**: Select connected exchange/broker
- **Trading Preset**: Select or create risk management preset
- **Filter Strategy**: Select or create technical indicator filter
- **AI Confirmation** (optional): Enable AI market analysis

**Flow**:
```
User creates bot
  → Selects connection (Binance, MT4, etc.)
  → Selects preset (risk management)
  → Selects filter strategy (technical indicators)
  → Bot is active
  → Signals published → Filter evaluated → Preset applied → Trade executed
```

**Database**:
```sql
CREATE TABLE trading_bots (
  id BIGINT PRIMARY KEY,
  user_id BIGINT,
  name VARCHAR(255),
  description TEXT,
  exchange_connection_id BIGINT, -- FK to execution_connections
  trading_preset_id BIGINT,      -- FK to trading_presets
  filter_strategy_id BIGINT,     -- FK to filter_strategies
  ai_model_profile_id BIGINT,    -- FK to ai_model_profiles (optional)
  is_active BOOLEAN DEFAULT true,
  is_paper_trading BOOLEAN DEFAULT true, -- For demo
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

### 2. Demo Setup Checklist

#### A. Demo Account Setup
- [ ] Create demo admin account
- [ ] Create demo user account
- [ ] Set up paper trading mode (no real trades)
- [ ] Configure demo exchange connections (sandbox/testnet)

#### B. Sample Data
- [ ] Create 3-5 sample trading presets:
  - Conservative (0.5% risk, 30 pips SL)
  - Moderate (1% risk, 50 pips SL, multi-TP)
  - Aggressive (2% risk, 40 pips SL, layering)
- [ ] Create 3-5 sample filter strategies:
  - Uptrend Filter (EMA fast > slow, PSAR below price)
  - Oversold Buy (Stochastic < 20)
  - Trend + Momentum (EMA + Stochastic)
- [ ] Create sample trading bots (combinations)
- [ ] Create sample signals for testing

#### C. UI Enhancements
- [ ] Trading Bot Builder page (create/edit bots)
- [ ] Trading Bot Dashboard (list all bots, status, performance)
- [ ] Live Monitoring (positions, executions, analytics)
- [ ] Demo mode indicator (clear "DEMO MODE" badge)

---

## Demo Walkthrough Script

### Part 1: Overview (2 min)
1. **Platform Introduction**
   - "This is an automated trading bot platform similar to Coinrule"
   - "Users can create trading bots with technical indicators and risk management"
   - "Supports crypto exchanges (Binance, Coinbase) and FX brokers (MT4/MT5)"

### Part 2: Create Trading Bot (5 min)
2. **Step 1: Exchange Connection**
   - Show: "Connect to Binance (or MT4)"
   - Action: Select existing connection or create new
   - Highlight: Paper trading mode enabled (safe for demo)

3. **Step 2: Risk Management Preset**
   - Show: Preset selection dropdown
   - Options: Conservative, Moderate, Aggressive
   - Explain: Position sizing, SL/TP, advanced features
   - Action: Select "Moderate" preset

4. **Step 3: Technical Indicator Filter**
   - Show: Filter strategy selection
   - Options: Uptrend Filter, Oversold Buy, Trend + Momentum
   - Explain: Only execute trades when indicators align
   - Action: Select "Uptrend Filter" (EMA fast > slow, PSAR below price)

5. **Step 4: AI Confirmation (Optional)**
   - Show: AI market analysis toggle
   - Explain: AI confirms market conditions before execution
   - Action: Enable for demo

6. **Step 5: Activate Bot**
   - Show: Bot created successfully
   - Status: Active, Paper Trading Mode
   - Explain: Bot will automatically execute signals that pass filters

### Part 3: Live Monitoring (3 min)
7. **Dashboard View**
   - Show: All active bots
   - Metrics: Total bots, Active positions, Win rate, Profit
   - Action: Click on bot to see details

8. **Bot Details**
   - Show: Bot configuration (preset, filter, connection)
   - Show: Recent executions
   - Show: Open positions (with SL/TP)
   - Show: Performance analytics

9. **Real-time Updates**
   - Show: Live position monitoring
   - Show: Execution logs
   - Show: Filter evaluation results (pass/fail with reasons)

### Part 4: Advanced Features (2 min)
10. **Multiple Bots**
    - Show: Create multiple bots with different strategies
    - Example: Conservative bot for EUR/USD, Aggressive bot for BTC/USD
    - Explain: Diversification across pairs/strategies

11. **Custom Presets & Filters**
    - Show: Create custom preset (risk management)
    - Show: Create custom filter (technical indicators)
    - Explain: Full customization for advanced users

---

## Implementation Tasks

### Phase 1: Core Bot Builder (Priority 1)
1. Create `trading_bots` table migration
2. Create `TradingBot` model
3. Create `TradingBotService` (CRUD operations)
4. Create `TradingBotController` (user/admin)
5. Create Bot Builder UI (form: connection + preset + filter)
6. Create Bot Dashboard UI (list bots, status, performance)

### Phase 2: Bot Execution Integration (Priority 1)
1. Modify `SignalExecutionService` to check bot assignments
2. When signal published:
   - Find active bots for user/connection
   - Apply filter strategy
   - Apply trading preset
   - Execute if all pass
3. Link executions to bots (add `trading_bot_id` to execution_logs)

### Phase 3: Demo Mode (Priority 2)
1. Add `is_paper_trading` flag to connections
2. Modify execution to skip real API calls in paper mode
3. Simulate trades (log only, no real orders)
4. Add demo mode badge to UI

### Phase 4: UI Polish (Priority 2)
1. Bot dashboard with cards (like Coinrule)
2. Live monitoring page (positions, executions)
3. Analytics dashboard (win rate, profit factor)
4. Demo mode indicators

---

## Database Schema

### trading_bots Table
```sql
CREATE TABLE trading_bots (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NULL, -- NULL for admin bots
  admin_id BIGINT NULL, -- NULL for user bots
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  exchange_connection_id BIGINT NOT NULL,
  trading_preset_id BIGINT NOT NULL,
  filter_strategy_id BIGINT NULL, -- Optional
  ai_model_profile_id BIGINT NULL, -- Optional
  is_active BOOLEAN DEFAULT true,
  is_paper_trading BOOLEAN DEFAULT true,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  FOREIGN KEY (exchange_connection_id) REFERENCES execution_connections(id),
  FOREIGN KEY (trading_preset_id) REFERENCES trading_presets(id),
  FOREIGN KEY (filter_strategy_id) REFERENCES filter_strategies(id),
  INDEX (user_id, is_active),
  INDEX (admin_id, is_active)
);
```

### Update execution_logs
```sql
ALTER TABLE execution_logs 
ADD COLUMN trading_bot_id BIGINT NULL,
ADD FOREIGN KEY (trading_bot_id) REFERENCES trading_bots(id);
```

---

## Routes

### User Routes
```
GET  /user/trading-bots                    - List all bots
GET  /user/trading-bots/create             - Bot builder form
POST /user/trading-bots                    - Create bot
GET  /user/trading-bots/{id}               - Bot details
GET  /user/trading-bots/{id}/edit          - Edit form
PUT  /user/trading-bots/{id}               - Update bot
DELETE /user/trading-bots/{id}              - Delete bot
POST /user/trading-bots/{id}/toggle         - Enable/disable bot
```

### Admin Routes
```
GET  /admin/trading-bots                   - List all bots (admin + users)
GET  /admin/trading-bots/{id}               - View any bot
```

---

## Files to Create

### Models
- `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php`

### Services
- `main/addons/trading-management-addon/Modules/TradingBot/Services/TradingBotService.php`
- `main/addons/trading-management-addon/Modules/TradingBot/Services/BotExecutionService.php`

### Controllers
- `main/addons/trading-management-addon/Modules/TradingBot/Controllers/User/TradingBotController.php`
- `main/addons/trading-management-addon/Modules/TradingBot/Controllers/Backend/TradingBotController.php`

### Views
- `main/addons/trading-management-addon/Modules/TradingBot/resources/views/user/bots/index.blade.php`
- `main/addons/trading-management-addon/Modules/TradingBot/resources/views/user/bots/create.blade.php`
- `main/addons/trading-management-addon/Modules/TradingBot/resources/views/user/bots/show.blade.php`

### Migrations
- `main/addons/trading-management-addon/database/migrations/YYYY_MM_DD_HHMMSS_create_trading_bots_table.php`

---

## Demo Data Seeding

### Sample Presets
1. **Conservative Scalper**
   - Risk: 0.5% per trade
   - SL: 30 pips
   - TP: Single (60 pips = 2R)

2. **Moderate Swing**
   - Risk: 1% per trade
   - SL: 50 pips
   - TP: Multi (2R, 3R, 5R)
   - Break-Even: Enabled at 1.5R

3. **Aggressive Day Trader**
   - Risk: 2% per trade
   - SL: 40 pips
   - TP: Multi-TP
   - Layering: Enabled

### Sample Filter Strategies
1. **Uptrend Filter**
   - EMA Fast (10) > EMA Slow (100)
   - PSAR below price

2. **Oversold Buy**
   - Stochastic < 20

3. **Trend + Momentum**
   - EMA Fast > EMA Slow
   - Stochastic > 50

### Sample Bots
1. **EUR/USD Conservative Bot**
   - Connection: MT4 Demo
   - Preset: Conservative Scalper
   - Filter: Uptrend Filter

2. **BTC/USD Aggressive Bot**
   - Connection: Binance Testnet
   - Preset: Aggressive Day Trader
   - Filter: Trend + Momentum

---

## Success Criteria

✅ Investor can:
1. Create a trading bot in < 2 minutes
2. Understand how technical indicators filter trades
3. See risk management in action (position sizing, SL/TP)
4. Monitor live positions and executions
5. View performance analytics
6. Create multiple bots with different strategies

---

## Timeline

- **Day 1**: Database schema + Models + Services
- **Day 2**: Bot Builder UI + Controller
- **Day 3**: Bot execution integration + Demo mode
- **Day 4**: UI polish + Demo data seeding
- **Day 5**: Testing + Demo script preparation

---

## Notes

- **Paper Trading**: All demo connections must be in paper trading mode
- **No Real Money**: Ensure no real API keys are used
- **Clear Indicators**: Show "DEMO MODE" badges everywhere
- **Fast Setup**: Pre-seed data so demo is ready in 30 seconds
- **Visual Appeal**: Make UI look modern and professional (like Coinrule)
