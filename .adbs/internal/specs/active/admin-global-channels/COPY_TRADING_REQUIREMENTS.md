# Requirements untuk Copy Trading System (TelegramFxCopier-like)

**Tanggal:** 2025-11-11
**Status:** Requirements Document

## Overview

Dokumen ini merinci requirements untuk mengembangkan fitur copy trading yang mirip dengan TelegramFxCopier, dimana signals dari Telegram channels dieksekusi langsung ke MetaTrader (MT4/MT5).

## Functional Requirements

### FR-1: MetaTrader Integration

#### FR-1.1: MT4/MT5 API Connection
- User dapat connect MT4/MT5 account
- Store connection credentials securely (encrypted)
- Support multiple accounts per user
- Connection status monitoring
- Auto-reconnect on disconnect

#### FR-1.2: Account Management
- List all connected accounts
- View account balance, equity, margin
- Account status (connected/disconnected/error)
- Remove account connection

### FR-2: Trade Execution Engine

#### FR-2.1: Order Placement
- Execute buy/sell orders dari signals
- Support market orders
- Support pending orders (buy limit, sell limit, buy stop, sell stop)
- Handle order execution errors
- Retry logic untuk failed orders

#### FR-2.2: Order Management
- View all open positions
- Close positions manually
- Modify SL/TP
- Cancel pending orders
- Bulk operations

### FR-3: Advanced Signal Parsing

#### FR-3.1: LLM-Based Parser
- Parse signals menggunakan AI (GPT-4/Claude)
- Handle berbagai format signal
- Multi-language support
- Extract: symbol, direction, entry, SL, TP(s), timeframe

#### FR-3.2: Image Recognition
- OCR untuk screenshot signals
- Extract text dari images
- Parse chart screenshots
- Support berbagai image formats

#### FR-3.3: Format Detection
- Detect price format (absolute price, points, percentage)
- Convert antara formats
- Handle provider-specific formats

### FR-4: Multiple Take Profit Support

#### FR-4.1: Multiple TP Configuration
- Support multiple TP levels (TP1, TP2, TP3, etc.)
- Configure TP price untuk setiap level
- Configure lot percentage untuk setiap TP
- Partial close pada setiap TP

#### FR-4.2: TP Management
- Track TP hits
- Auto close partial position pada TP hit
- Update remaining position SL/TP

### FR-5: Risk Management

#### FR-5.1: Trailing Stop Loss
- Enable/disable trailing SL
- Configure trailing distance (points/percentage)
- Trail setelah TP tertentu hit
- Multiple trailing strategies

#### FR-5.2: Move SL to Breakeven
- Auto move SL to entry price
- Trigger setelah TP tertentu hit
- Configurable trigger level

#### FR-5.3: Custom Trailing Stop
- Dynamic SL adjustment
- Multiple trailing algorithms
- Custom rules per channel

### FR-6: Money Management

#### FR-6.1: Lot Size Calculation
- Fixed lot size
- Percentage of balance
- Risk-based lot (risk % per trade)
- Percentage of TP lot size
- Min/max lot constraints

#### FR-6.2: Risk Per Trade
- Configure max risk percentage
- Calculate lot berdasarkan SL distance
- Risk calculator
- Risk warning jika exceed limit

### FR-7: Trade Execution Settings

#### FR-7.1: Entry Price Settings
- Entry at market price
- Entry at provider price
- Entry dengan offset (points)

#### FR-7.2: Execution Filters
- Max spread untuk execution
- Max slippage tolerance
- Symbol exceptions (blacklist/whitelist)
- Allow/deny same symbol multiple trades
- Pending order expiration

### FR-8: Channel-Specific Strategy

#### FR-8.1: Per-Channel Settings
- Custom SL/TP per channel
- Points offset per channel
- Strategy template per channel
- Override global settings

#### FR-8.2: Strategy Templates
- Create reusable strategy templates
- Apply template ke channels
- Template inheritance

### FR-9: Trade Analytics

#### FR-9.1: Trade Tracking
- Track semua executed trades
- Link trades ke signals
- Track P&L per trade
- Track P&L per channel

#### FR-9.2: Performance Metrics
- Win rate
- Average win/loss
- Net profit/loss
- Net pips
- Sharpe ratio
- Maximum drawdown

#### FR-9.3: Reporting
- Daily/weekly/monthly reports
- Channel performance comparison
- Account performance
- Export reports (PDF/Excel)

### FR-10: Signal Modification Handling

#### FR-10.1: Signal Update Detection
- Detect signal modifications
- Compare dengan existing signals
- Identify changes (SL/TP updates)

#### FR-10.2: Trade Modification
- Update existing trades berdasarkan signal changes
- Modify SL/TP
- Close trades early
- Handle signal cancellations

## Technical Requirements

### TR-1: MetaTrader API

**Required Libraries:**
- MQL5 API untuk MT5
- MQL4 API untuk MT4 (via bridge atau Expert Advisor)
- PHP wrapper untuk MetaTrader API

**Options:**
1. **MetaApi** (https://metaapi.cloud) - Cloud-based MT API
2. **Native MQL5 API** - Direct integration
3. **Expert Advisor** - MQL4/5 EA untuk bridge

**Recommended:** MetaApi (easiest integration)

### TR-2: AI/LLM Integration

**Required Services:**
- OpenAI GPT-4 API atau
- Anthropic Claude API atau
- Local LLM (Llama 3, Mistral)

**For Image Recognition:**
- Google Cloud Vision API atau
- Tesseract OCR atau
- AWS Textract

### TR-3: Database Schema Extensions

**New Tables Required:**
1. `mt_accounts` - MT4/MT5 account connections
2. `signal_take_profits` - Multiple TP support
3. `signal_risk_settings` - Risk management settings
4. `signal_execution_settings` - Execution settings
5. `trade_executions` - Trade tracking
6. `trade_modifications` - Trade modification history
7. `strategy_templates` - Strategy templates
8. `channel_strategies` - Channel-specific strategies

### TR-4: Queue Jobs

**New Jobs Required:**
1. `ExecuteTradeJob` - Execute trade ke MT4/MT5
2. `MonitorTradesJob` - Monitor open positions
3. `UpdateTrailingSLJob` - Update trailing SL
4. `CheckTPLevelsJob` - Check TP hits
5. `ProcessImageSignalJob` - Process image signals dengan OCR

### TR-5: Real-time Updates

**Required:**
- WebSocket connection untuk real-time price updates
- Real-time trade status updates
- Real-time account balance updates

## Non-Functional Requirements

### NFR-1: Performance
- Trade execution < 1 second dari signal received
- Support 100+ concurrent trades
- Handle 1000+ signals per hour

### NFR-2: Reliability
- 99.9% uptime untuk trade execution
- Auto-retry untuk failed executions
- Graceful degradation

### NFR-3: Security
- Encrypt MT account credentials
- Secure API connections
- Audit logging untuk semua trades

### NFR-4: Scalability
- Support 1000+ users
- Support 100+ channels per user
- Horizontal scaling capability

## Implementation Priority

### P0 (Critical - Must Have)
1. MT4/MT5 API Integration
2. Basic Trade Execution
3. Multiple TP Support
4. Basic Risk Management (Trailing SL, Move to Breakeven)

### P1 (High Priority)
5. Advanced Parsing (LLM)
6. Money Management
7. Trade Execution Settings
8. Trade Analytics

### P2 (Medium Priority)
9. Channel-Specific Strategy
10. Signal Modification Handling
11. Image Recognition

### P3 (Low Priority)
12. Advanced Analytics
13. Reporting
14. Strategy Templates

## Estimated Development Timeline

- **Phase 1 (P0):** 2-3 months
- **Phase 2 (P1):** 2-3 months
- **Phase 3 (P2):** 1-2 months
- **Phase 4 (P3):** 1-2 months

**Total: 6-10 months**

