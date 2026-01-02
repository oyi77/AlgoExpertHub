# Prebuilt Trading Bots - Feature Brief

**Status**: Planning  
**Priority**: High  
**Created**: 2025-01-30

## Concept

**PRIMARY GOAL**: Create demo-ready trading bot templates for **investor demonstrations**.

Trading bots should have **prebuilt templates** (similar to Trading Presets) that:
1. Showcase automated trading with technical indicators (MA100, MA10, Parabolic SAR)
2. Demonstrate multiple risk management strategies (trading presets)
3. Are ready for investor demos - impressive, professional, working bots
4. Users can browse, clone, and customize with their own connections

**Similar to Coinrule**: Users select a prebuilt bot template, then customize it with their exchange connection.

**DEMO FOCUS**: These bots are designed to impress investors by showing:
- ✅ Professional technical analysis (MA100, MA10, PSAR)
- ✅ Automated trade execution
- ✅ Multiple risk management strategies
- ✅ Real-time market filtering

## Current State

✅ **Trading Bots exist** (`TradingBot` model)  
✅ **Trading Presets have prebuilt templates** (6 default presets via `TradingPresetSeeder`)  
✅ **Filter Strategies have visibility/clonable system**  
✅ **Clone pattern exists** (`cloneForUser()` method in presets/filters)  
❌ **Trading Bots have NO prebuilt templates**  
❌ **TradingBot model missing visibility/clonable fields**  
❌ **No bot template seeder**  
❌ **No bot marketplace UI**

## Bot Structure

A Trading Bot combines:
- **Exchange Connection** (required)
- **Trading Preset** (required) 
- **Filter Strategy** (optional)
- **AI Model Profile** (optional)

## Prebuilt Bot Templates Needed (Investor Demo Focus)

**Goal**: Create impressive, demo-ready bots for investors showcasing:
- Automated trading with technical indicators (MA100, MA10, Parabolic SAR)
- Multiple trading presets/risk management strategies
- Professional bot configurations

### 1. MA Trend Confirmation Bot (Forex) ⭐ DEMO FOCUS
- **Preset**: Conservative Scalper
- **Filter**: MA100/MA10/PSAR Uptrend Filter
  - MA10 > MA100 (uptrend)
  - PSAR below price (bullish)
  - Only executes BUY signals when both conditions met
- **AI**: None
- **Market**: Forex
- **Description**: Professional forex bot using Moving Average crossover and Parabolic SAR for trend confirmation. Perfect for demo showcasing technical analysis.

### 2. MA10/MA100 Crossover Bot (Forex)
- **Preset**: Moderate Swing Trader
- **Filter**: MA Crossover Filter
  - MA10 crosses above MA100 (bullish crossover)
  - PSAR below price
- **AI**: None
- **Market**: Forex
- **Description**: Swing trading bot that enters on MA crossover signals with PSAR confirmation.

### 3. MA100 + PSAR Trend Follower (Crypto)
- **Preset**: Aggressive Day Trader
- **Filter**: Strong Trend Filter
  - Price above MA100 (long-term uptrend)
  - PSAR below price (current trend bullish)
  - MA10 > MA100 (short-term confirms long-term)
- **AI**: None
- **Market**: Crypto
- **Description**: Crypto bot for strong trending markets using multiple MA levels and PSAR.

### 4. MA100 Support/Resistance Bot (Forex)
- **Preset**: Swing Trader
- **Filter**: MA100 Support Filter
  - Price bounces off MA100 (support/resistance)
  - PSAR confirms direction
  - MA10 confirms momentum
- **AI**: None
- **Market**: Forex
- **Description**: Bot that trades bounces off MA100 support/resistance levels.

### 5. Conservative MA Trend Bot (Multi-Market)
- **Preset**: Conservative Scalper
- **Filter**: Basic MA Filter
  - MA10 > MA100
  - PSAR below price (for buys)
- **AI**: None
- **Market**: Both (Forex + Crypto)
- **Description**: Simple, conservative bot using basic MA trend confirmation. Safe for demo.

### 6. Advanced MA + PSAR Multi-Strategy (Forex) ⭐ DEMO FOCUS
- **Preset**: Moderate Swing Trader (with break-even & trailing stop)
- **Filter**: Comprehensive MA/PSAR Filter
  - MA10 > MA100 (trend)
  - PSAR below price (momentum)
  - Price above MA100 (strength)
- **AI**: None
- **Market**: Forex
- **Description**: Advanced bot showcasing multiple indicators and sophisticated risk management (break-even, trailing stops, multi-TP).

## Implementation Plan

### Phase 1: Database Schema Updates
- Add `visibility` field (PRIVATE, PUBLIC_MARKETPLACE)
- Add `clonable` field (boolean)
- Add `is_default_template` field (boolean)
- Add `created_by_user_id` (nullable, null for system templates)
- Add `admin_id` (nullable, for admin-created templates)

### Phase 2: Model Updates
- Add scopes: `defaultTemplates()`, `public()`, `clonable()`
- Add helper methods: `isPublic()`, `isClonable()`, `canBeClonedBy()`
- Add `cloneForUser()` method

### Phase 3: Seeder
- Create `PrebuiltTradingBotSeeder`
- Seed 6+ prebuilt bot templates
- Templates reference prebuilt presets/filters/AI profiles (by name, lookup IDs)

### Phase 4: Service Updates
- Update `TradingBotService` to handle cloning
- Add `getPrebuiltTemplates()` method
- Update `getBots()` to filter templates vs user bots

### Phase 5: UI Updates
- Bot marketplace page (browse prebuilt templates)
- Clone bot functionality
- "Create from Template" option in bot creation flow

## Technical Considerations

1. **Connection Dependency**: Prebuilt bots can't have fixed connections (users must select their own)
   - **Solution**: Store connection type/suggestions in bot template, user selects during clone

2. **Preset/Filter/AI Lookup**: Templates reference by name, lookup during clone
   - **Solution**: Use preset/filter/AI profile names, lookup IDs during seeding/cloning

3. **Template vs User Bot**: Distinguish between templates and user bots
   - **Solution**: `is_default_template = true` OR `created_by_user_id = null` = template

4. **Admin Templates**: Admins can create template bots
   - **Solution**: `admin_id` set + `visibility = PUBLIC_MARKETPLACE` = admin template

5. **Demo-Ready Filters**: All filters MUST use MA100, MA10, and/or Parabolic SAR
   - **Solution**: Create filter strategies with these indicators, reference in bot templates
   - **Indicator Names**: 
     - `ema_fast` or `ema10` = MA10 (10-period EMA)
     - `ema_slow` or `ema100` = MA100 (100-period EMA)
     - `psar` or `parabolic_sar` = Parabolic SAR

6. **Demo User**: Consider keeping `DemoTradingBotSeeder` separate for demo account, but also create public templates
   - **Solution**: `DemoTradingBotSeeder` = demo user account (private)
   - `PrebuiltTradingBotSeeder` = public templates (marketplace)

## Success Criteria

✅ Users can browse prebuilt bot templates  
✅ Users can clone a template to their account  
✅ Cloned bot references user's connections (not template's)  
✅ Cloned bot references cloned presets/filters/AI (if public)  
✅ Bot creation flow offers "Start from Template" option  
✅ Templates are clearly marked in UI  
