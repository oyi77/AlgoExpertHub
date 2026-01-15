# Problems - AI Bot Trading Completion

## Unresolved Questions

None currently.

## Technical Debt Identified

### Legacy AI Addon References
- **Area**: AI Routing
- **Issue**: BotSignalObserver and FilterAnalysisJob may reference deprecated `AiTradingAddon`
- **Cleanup Needed**: After Tasks 4, 5, verify no `Addons\AiTradingAddon\` references remain

### Migration Backwards Compatibility
- **Area**: Database
- **Issue**: New columns added to existing tables may affect existing code
- **Investigation Needed**: Verify no assumptions about column existence in existing queries

### Paper Trading User ID Propagation
- **Area**: Job Execution
- **Issue**: ExecutionJob::createVirtualPosition() needs user_id from executionData
- **Requirement**: Ensure FilterAnalysisJob and downstream jobs include bot->user_id in payload
- **Status**: Documented in plan, verify implementation

## Future Considerations

### Market Data Cache Invalidation
- **Area**: Landing Page Data
- **Consideration**: When to invalidate cache on errors?
- **Current Plan**: 15-minute TTL, scheduled refresh
- **Future Enhancement**: Dynamic cache TTL based on API response time

### Circuit Breaker Configuration
- **Area**: Risk Management
- **Consideration**: Make cooldown period configurable vs hardcoded 15 minutes
- **Current Plan**: Hardcoded 15 minutes
- **Future Enhancement**: Add to settings table for admin configuration

### AI Decision Audit Retention
- **Area**: Audit Trail
- **Consideration**: How long to keep AI decision records?
- **Current Plan**: No retention policy specified
- **Future Enhancement**: Add cron job to archive old decisions

## Integration Points

### MetaAPI Redis Stream
- **Status**: MarketStatusChecker uses existing Redis stream
- **Integration Needed**: Unify Redis prefix config key
- **Config Key**: `config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream')`

### CCXT Database Integration
- **Status**: MarketDataService stores OHLCV data
- **Integration Needed**: MarketStatusChecker queries market_data table for freshness
- **Implementation**: Check last candle timestamp for symbol/timeframe

## Performance Optimizations

### Market Data Refresh Strategy
- **Current**: Scheduled every 15 minutes
- **Optimization**: Could use queue jobs with staggered delays
- **Benefit**: Reduce load spikes on Twelve Data API

### AI Connection Pooling
- **Current**: Per-request AI calls
- **Optimization**: Could implement connection pooling for reuse
- **Benefit**: Reduce latency, improve reliability

