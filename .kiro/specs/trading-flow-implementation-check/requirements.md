# Trading Flow Implementation Check - Requirements

## Introduction

This document defines the requirements for verifying and documenting the complete trading flow implementation in the AlgoExpertHub platform. The trading flow encompasses all phases from signal creation through automated execution, position monitoring, and closure.

The verification process ensures that:
- All components are properly implemented and integrated
- Data flows correctly between phases
- Error handling is robust
- Performance meets acceptable standards
- Edge cases are handled appropriately

## Glossary

- **Signal**: Trading recommendation (buy/sell) created manually by admin or automatically from channel messages
- **ExecutionPosition**: Database record tracking an open or closed trading position from signal execution
- **TradingBotPosition**: Database record tracking bot-specific position data, linked to ExecutionPosition
- **ExecutionJob**: Queue job that executes trades on exchanges/brokers
- **MonitorPositionsJob**: Scheduled job that monitors open positions, updates prices, and checks SL/TP
- **BotSignalObserver**: Laravel observer that detects signal publication and triggers bot execution
- **ExecutionConnection**: Connection configuration for exchange/broker API access
- **Adapter**: Exchange interface implementation (CCXT for crypto, MT4/MT5 for FX)
- **SL**: Stop Loss - price level at which position closes to limit losses
- **TP**: Take Profit - price level at which position closes to secure profits
- **PnL**: Profit and Loss - calculated profit or loss for a position

## Requirements

### Requirement 1: Signal Creation Verification

**User Story**: As a system verifier, I need to verify that signals can be created both manually and automatically, so that the trading flow has valid input data.

#### Acceptance Criteria

1. WHEN a signal is created manually by admin, THE system SHALL:
   - Create Signal record with `is_published = 0` (draft status)
   - Store all required fields (currency_pair_id, time_frame_id, market_id, open_price, sl, tp, direction)
   - Allow admin to assign signal to plans
   - Allow admin to publish signal (set `is_published = 1`)

2. WHEN a channel message is processed, THE system SHALL:
   - Create ChannelMessage record with raw message
   - Parse message using configured parsers (Regex, AI, Pattern templates)
   - Create Signal record with `auto_created = 1` and `is_published = 0`
   - Link signal to channel_source_id
   - Store message_hash for duplicate detection

3. WHEN duplicate message is detected, THE system SHALL:
   - Skip signal creation
   - Mark ChannelMessage as 'duplicate'
   - Log duplicate detection event

**Verification Points**:
- Signal model creation works correctly
- Auto-created signals have correct flags
- Duplicate detection prevents duplicate signals
- Signal-to-plan assignment works

---

### Requirement 2: Signal Publication Detection Verification

**User Story**: As a system verifier, I need to verify that signal publication triggers execution flow, so that published signals are automatically executed.

#### Acceptance Criteria

1. WHEN admin publishes a signal (`is_published` changes from 0 to 1), THE system SHALL:
   - Set `published_date` timestamp
   - Trigger `SignalService::sent()` to distribute to users
   - Trigger `BotSignalObserver::updated()` to detect publication
   - Dispatch execution jobs for eligible bots/connections

2. WHEN BotSignalObserver detects publication, THE system SHALL:
   - Get all active bots for the signal
   - Filter bots by status, connection, and symbol/timeframe match
   - Evaluate bot filter strategies (if configured)
   - Perform AI analysis (if configured)
   - Dispatch ExecutionJob for each eligible bot

3. WHEN signal is not published, THE system SHALL:
   - NOT trigger execution flow
   - NOT dispatch execution jobs
   - Keep signal in draft status

**Verification Points**:
- Observer is properly registered
- Publication detection works correctly
- Bot filtering logic works
- AI analysis integration works (if enabled)
- Execution jobs are dispatched correctly

---

### Requirement 3: Bot Filtering and AI Analysis Verification

**User Story**: As a system verifier, I need to verify that bot filtering and AI analysis work correctly, so that only appropriate signals are executed.

#### Acceptance Criteria

1. WHEN bot has filter strategy configured, THE system SHALL:
   - Evaluate technical indicators (EMA, RSI, PSAR)
   - Check if signal passes filter rules
   - Reject signal if filter fails
   - Log filter rejection reason

2. WHEN bot has AI model profile enabled, THE system SHALL:
   - Perform market analysis via AI service
   - Get AI decision (approve/reject/reduce size)
   - Skip execution if AI rejects
   - Apply size multiplier if AI reduces risk
   - Log AI decision and reason

3. WHEN bot has no filters or AI, THE system SHALL:
   - Execute signal without additional checks
   - Use default position sizing

**Verification Points**:
- Filter strategy evaluation works
- AI analysis service integration works
- Position size adjustments are applied correctly
- Rejection reasons are logged

---

### Requirement 4: Trade Execution Verification

**User Story**: As a system verifier, I need to verify that trades are executed correctly on exchanges, so that positions are opened as expected.

#### Acceptance Criteria

1. WHEN ExecutionJob is dispatched, THE system SHALL:
   - Get ExecutionConnection from connection_id
   - Verify connection is active and can execute trades
   - Create appropriate adapter (CCXT, MT4/MT5, MetaAPI)
   - Validate execution data (symbol, direction, quantity, prices)

2. WHEN order is placed, THE system SHALL:
   - Place market order OR limit order (based on entry_price)
   - Include SL/TP in order parameters
   - Get order_id from exchange response
   - Handle order placement errors gracefully

3. WHEN order is successful, THE system SHALL:
   - Create ExecutionLog record with execution details
   - Create ExecutionPosition record with status='open'
   - Create TradingBotPosition record (if bot_id present)
   - Link positions correctly
   - Update bot statistics

4. WHEN order fails, THE system SHALL:
   - Log error with context
   - Create ExecutionLog with status='failed'
   - NOT create position records
   - Send error notification

**Verification Points**:
- Adapter creation works for all exchange types
- Order placement succeeds on valid data
- Position records are created correctly
- Error handling prevents data corruption
- Bot statistics are updated

---

### Requirement 5: Position Creation Verification

**User Story**: As a system verifier, I need to verify that positions are created correctly with all required data, so that monitoring can track them properly.

#### Acceptance Criteria

1. WHEN ExecutionPosition is created, THE system SHALL:
   - Store all required fields (signal_id, connection_id, symbol, direction, quantity, entry_price, sl_price, tp_price)
   - Set status='open'
   - Set current_price = entry_price initially
   - Link to ExecutionLog
   - Set order_id from exchange

2. WHEN TradingBotPosition is created, THE system SHALL:
   - Link to ExecutionPosition via execution_position_id
   - Store bot_id and signal_id
   - Store all position data (prices, quantity, direction)
   - Set status='open'
   - Set opened_at timestamp

3. WHEN position creation fails, THE system SHALL:
   - Rollback transaction if possible
   - Log error with full context
   - NOT leave orphaned records

**Verification Points**:
- Position records have all required fields
- Relationships are established correctly
- Data integrity is maintained
- Transaction rollback works on failure

---

### Requirement 6: Position Monitoring Verification

**User Story**: As a system verifier, I need to verify that positions are monitored correctly, so that SL/TP are detected and positions are updated in real-time.

#### Acceptance Criteria

1. WHEN MonitorPositionsJob runs (every minute), THE system SHALL:
   - Get all active trading bots
   - Get all open ExecutionPositions
   - Update current price for each position
   - Calculate and update PnL
   - Check stop loss conditions
   - Check take profit conditions

2. WHEN current price is updated, THE system SHALL:
   - Fetch price from exchange via adapter
   - Update position.current_price
   - Update position.last_price_update_at
   - Calculate PnL and PnL percentage
   - Broadcast update via WebSocket (if enabled)

3. WHEN SL/TP check is performed, THE system SHALL:
   - Compare current_price to sl_price (direction-aware)
   - Compare current_price to tp_price (direction-aware)
   - Close position if SL/TP hit
   - Log closure reason

**Verification Points**:
- Job runs on schedule (every minute)
- Price updates work for all exchange types
- PnL calculation is accurate
- SL/TP detection works correctly
- WebSocket broadcasting works (if enabled)

---

### Requirement 7: Position Closure Verification

**User Story**: As a system verifier, I need to verify that positions are closed correctly when SL/TP is hit, so that profits/losses are realized.

#### Acceptance Criteria

1. WHEN SL is hit, THE system SHALL:
   - Close position on exchange via adapter
   - Update ExecutionPosition: status='closed', closed_reason='stop_loss_hit', closed_at=now()
   - Update TradingBotPosition: status='closed', close_reason='stop_loss_hit', closed_at=now()
   - Calculate final PnL
   - Sync both position records
   - Send closure notification

2. WHEN TP is hit, THE system SHALL:
   - Close position on exchange via adapter
   - Update ExecutionPosition: status='closed', closed_reason='take_profit_hit', closed_at=now()
   - Update TradingBotPosition: status='closed', close_reason='take_profit_hit', closed_at=now()
   - Calculate final PnL
   - Sync both position records
   - Send closure notification

3. WHEN position is closed manually, THE system SHALL:
   - Close position on exchange
   - Update both position records
   - Set closed_reason='manual'
   - Calculate final PnL

4. WHEN exchange closure fails, THE system SHALL:
   - Still update local position status
   - Mark as 'local_only' closure
   - Log warning
   - Retry closure on next monitoring cycle

**Verification Points**:
- Position closure on exchange works
- Both position records are updated
- PnL calculation is accurate
- Position sync works bidirectionally
- Notifications are sent
- Error handling prevents data loss

---

### Requirement 8: Analytics Updates Verification

**User Story**: As a system verifier, I need to verify that analytics are updated correctly, so that performance metrics are accurate.

#### Acceptance Criteria

1. WHEN UpdateAnalyticsJob runs (daily), THE system SHALL:
   - Calculate win rate (winning trades / total trades)
   - Calculate profit factor (gross profit / gross loss)
   - Calculate maximum drawdown
   - Calculate total profit/loss
   - Calculate average trade duration
   - Store in ExecutionAnalytic table

2. WHEN bot statistics are updated, THE system SHALL:
   - Recalculate total_executions, successful_executions, failed_executions
   - Recalculate win_rate
   - Recalculate total_profit from closed positions
   - Update bot record

**Verification Points**:
- Analytics job runs on schedule (daily at midnight)
- Calculations are accurate
- Data is stored correctly
- Bot statistics are updated after each execution

---

### Requirement 9: End-to-End Integration Verification

**User Story**: As a system verifier, I need to verify that all components work together correctly, so that the complete flow functions as designed.

#### Acceptance Criteria

1. WHEN a signal is created and published, THE system SHALL:
   - Complete entire flow: Signal → Publication → Bot Detection → Execution → Position → Monitoring → Closure
   - Maintain data consistency throughout
   - Handle errors gracefully without breaking flow
   - Log all major events

2. WHEN multiple bots execute same signal, THE system SHALL:
   - Create separate positions for each bot
   - Track each position independently
   - Monitor all positions correctly
   - Close positions independently

3. WHEN system components fail, THE system SHALL:
   - Log errors with context
   - Not corrupt data
   - Allow retry/recovery
   - Continue processing other signals/positions

**Verification Points**:
- Complete flow works end-to-end
- Multiple concurrent executions work
- Error recovery works
- Data consistency is maintained

---

### Requirement 10: Performance and Error Handling Verification

**User Story**: As a system verifier, I need to verify that the system performs well and handles errors gracefully, so that it operates reliably in production.

#### Acceptance Criteria

1. WHEN jobs are dispatched, THE system SHALL:
   - Process jobs within acceptable time limits
   - Not block signal publication
   - Retry failed jobs appropriately
   - Handle queue backlogs gracefully

2. WHEN monitoring runs, THE system SHALL:
   - Complete within timeout (5 minutes)
   - Process all positions efficiently
   - Not cause memory issues
   - Handle exchange API rate limits

3. WHEN errors occur, THE system SHALL:
   - Log errors with full context
   - Not crash or corrupt data
   - Send notifications for critical errors
   - Allow manual intervention

**Verification Points**:
- Job execution time is acceptable
- Monitoring completes on time
- Error logging is comprehensive
- System remains stable under load

---

## Success Metrics

- **Completeness**: All 10 requirements verified
- **Integration**: All components work together
- **Reliability**: Error handling prevents data loss
- **Performance**: Jobs complete within time limits
- **Accuracy**: PnL calculations are correct
- **Consistency**: Data remains consistent across components

---

## Edge Cases to Verify

1. **Duplicate Execution**: Same signal executed twice by same bot
2. **Missing Data**: Signal missing required fields (pair, price, direction)
3. **Exchange Unavailable**: Exchange API down during execution
4. **Price Stale**: Exchange price not updating
5. **Position Already Closed**: Position closed externally before monitoring
6. **Invalid SL/TP**: SL/TP prices invalid (e.g., SL > entry for buy)
7. **Connection Inactive**: Connection becomes inactive during execution
8. **Bot Deactivated**: Bot deactivated after signal published but before execution

---

## Change History

- 2025-12-12: Initial requirements document created

