# Investor Demo Requirements - Prebuilt Trading Bots

## Primary Goal

Create **impressive, demo-ready trading bot templates** for investor demonstrations that showcase:

1. ✅ **Automated Trading**: Bots execute trades automatically
2. ✅ **Technical Analysis**: Using MA100, MA10, Parabolic SAR indicators
3. ✅ **Risk Management**: Multiple trading presets (conservative, moderate, aggressive)
4. ✅ **Professional Configuration**: Production-ready bot setups

## Demo Showcase Points

### What Investors Need to See:

1. **Technical Indicator Integration**
   - Moving Average 100 (MA100 / ema_slow)
   - Moving Average 10 (MA10 / ema_fast)
   - Parabolic SAR (PSAR)
   - How these indicators filter signals

2. **Automated Execution**
   - Bots automatically execute trades based on signals
   - Real-time market data fetching
   - Position monitoring and SL/TP management

3. **Multiple Risk Strategies**
   - Conservative (low risk, small positions)
   - Moderate (balanced risk, multi-TP)
   - Aggressive (high risk, layering, trailing stops)

4. **Professional Setup**
   - Pre-configured bots ready to use
   - Easy cloning/activation
   - Paper trading mode for safe demo

## Bot Template Requirements

### MUST HAVE:
- ✅ All bots use MA100, MA10, and/or Parabolic SAR in filters
- ✅ Multiple preset strategies (conservative, moderate, aggressive)
- ✅ Clear descriptions explaining the strategy
- ✅ Professional naming and categorization
- ✅ Demo-ready (paper trading enabled by default)

### SHOULD HAVE:
- ✅ Different market types (Forex, Crypto, Multi)
- ✅ Various filter complexities (simple to advanced)
- ✅ Showcase different features (break-even, trailing stops, multi-TP)

## Filter Strategy Requirements

All filter strategies MUST use these indicators:

### Indicator Names (as used in code):
- `ema_fast` or `ema10` = MA10 (10-period EMA)
- `ema_slow` or `ema100` = MA100 (100-period EMA)
- `psar` or `parabolic_sar` = Parabolic SAR

### Common Filter Patterns:
1. **Trend Confirmation**: MA10 > MA100 + PSAR below_price
2. **Crossover Entry**: MA10 crosses above MA100 + PSAR confirms
3. **Strong Trend**: Price > MA100 + MA10 > MA100 + PSAR below_price
4. **Support/Resistance**: Price near MA100 + PSAR confirms direction

## Demo User Account

**Separate from templates**:
- `DemoTradingBotSeeder` creates demo user account (`demo@investor.com`)
- This is for actual demo/testing with real bot instances
- Templates are public/clonable for all users

## Success Criteria for Investor Demo

✅ Investors can see 6+ professional bot templates  
✅ All bots use MA100, MA10, PSAR (clearly visible)  
✅ Easy to clone and activate a bot  
✅ Bots are production-ready (just need user's connection)  
✅ Multiple risk strategies showcased  
✅ Technical analysis is clearly demonstrated  
✅ Paper trading mode for safe demo  

## Key Differentiators for Demo

1. **Professional Setup**: Not just templates, but proven configurations
2. **Technical Sophistication**: MA100/MA10/PSAR shows advanced analysis
3. **Flexibility**: Multiple strategies for different risk appetites
4. **Automation**: Fully automated from signal to execution
5. **Risk Management**: Sophisticated presets with break-even, trailing stops
