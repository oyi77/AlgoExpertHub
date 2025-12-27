# Design: Optimize Trading Performance and UX

## Context
The platform's trading system is active across multiple adapters (CCXT, MetaApi, MTApi). High-frequency operations like connection health checks and signal processing are causing log bloat and memory pressure. Additionally, some background jobs lack user feedback.

## Goals
- Reduce log volume by 90% in production.
- Improve memory efficiency of connection tests.
- Provide better visual feedback on signal status.
- Complete missing notification loops.

## Decisions

### 1. Static Adapter Caching
- **Problem**: Every time `ExchangeConnectionService::getAdapter()` is called, a new adapter object is instantiated. For MetaApi, this involves SDK initialization and REST client setup.
- **Decision**: Implement a static `$adapters` array in `ExchangeConnectionService` to cache instances by connection ID for the duration of the request lifecycle.
- **Pros**: Reduced CPU/Memory, single SDK connection per request.
- **Cons**: Minor memory increase to hold the object (mitigated by request lifecycle).

### 2. Signal Outcome Badges
- **Decision**: Move the signal status logic into `Helper::formatSignalOutcome` to centralize UI presentation and ensure consistent badges across the dashboard and details pages.

### 3. Backtesting Notifications
- **Decision**: Implement the `RunBacktestJob` notification using Laravel's notification system (database + email) to close the loop on background trading operations.

## Risks / Trade-offs

### Stale Adapters
- **Risk**: A cached adapter might hold a stale connection state if used across long-running queue jobs.
- **Mitigation**: Caching is static and bound to the current process. For Octane/Swoole, we must ensure the cache is cleared at the end of each request to prevent memory leaks or stale state if the process is reused.
- **Action**: Add a `clearCache()` method to `ExchangeConnectionService` and call it in a termination listener if Octane is used.

## Open Questions
- Should we implement persistent caching (Redis) for adapters? (Non-goal for now, stick to static runtime cache).
