## Context

The trading bot system is part of the Trading Management Addon and provides automated trading capabilities. Users can create bots that combine exchange connections, trading presets, filter strategies, and AI model profiles to execute trades automatically.

## Goals / Non-Goals

### Goals
- Reliable bot lifecycle management (create, edit, run, pause, stop)
- Comprehensive analysis and performance tracking
- Multi-filter support with priority execution
- Reliable data fetching with retry and rate limiting
- Complete execution tracking and management
- Real-time monitoring and health checks

### Non-Goals
- Changing core bot execution logic (already implemented)
- Modifying exchange connection or trading preset systems
- Adding new exchange adapters

## Decisions

- **Service Layer Pattern**: All business logic in service classes, controllers remain thin
- **Multi-Filter Priority**: JSON array in trading_bots.filter_priority with priority ordering
- **Analytics Aggregation**: Daily aggregation in separate table for performance
- **Filter Result Tracking**: Separate table for audit trail and statistics
- **Health Status**: Enum field (healthy, warning, error) with automatic checks

## Risks / Trade-offs

- **Database Growth**: Filter results table may grow large → Mitigation: Add cleanup job for old records
- **Performance**: Analytics calculation may be slow → Mitigation: Use daily aggregation, cache results
- **Complexity**: Multi-filter logic adds complexity → Mitigation: Clear documentation, comprehensive tests

## Migration Plan

1. Run migrations to add new fields and tables
2. Existing bots will have default values (filter_priority = null, data_fetch_interval = 60)
3. No data migration needed for existing records
4. Views can be deployed incrementally

## Open Questions

- Should filter priority be required or optional? → Decision: Optional, falls back to single filter_strategy_id
- How often should health checks run? → Decision: On-demand via monitoring service, can be scheduled

