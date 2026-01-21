# Proposal: Multi-Perp DEX Analytics Addon

## Why

The platform lacks a unified, read-only analytics layer for perpetual DEX activity across GMX, Hyperliquid, Aster, Lighter, and dYdX v4. Administrators currently have no dedicated tooling to track trader performance, funding costs, liquidations, or behavioral patterns across these venues. Users also lack a view-only analytics interface and leaderboards derived from verified platform data.

This addon provides an audit-grade, research-first analytics engine that aggregates positions, funding, PnL, and liquidations, then computes standardized metrics and AI-driven insights without executing any trades.

## What Changes

### New Addon
- Add new addon at `main/addons/dex-analytics-addon/` with its own namespace, configuration, migrations, jobs, and views.

### Features
1. **Multi-Platform Data Ingestion**: API clients for GMX, Hyperliquid, Aster, Lighter, and dYdX v4.
2. **Normalization & Storage**: Unified schema for positions, PnL, funding, liquidations, and provenance logs.
3. **Analytics Engine**: PnL, win rate, drawdown, profit factor, and rankings.
4. **AI Insights**: Behavior clustering, crowded trade detection, regime analysis via AI Connection addon.
5. **Admin UI**: Dashboard, watchlist management, analytics views, leaderboards, AI insights, settings.
6. **User UI**: View-only dashboard, analytics, and leaderboards filtered by permissions/subscription.
7. **Scheduling**: Polling and refresh commands scheduled via `Kernel.php`.

### Guardrails
- Read-only only: no trading or order signing.
- No mock data or synthetic metrics.
- Batch polling only, no WebSocket streaming.

## Impact

### Affected Specs
- **NEW**: `dex-analytics` capability (multi-platform analytics addon)
- **MODIFIED**: `ai-connection` integration (AI insights consumption)

### Affected Code
- **New Addon Files**:
  - `main/addons/dex-analytics-addon/*`
- **Core Modifications**:
  - `main/composer.json` (PSR-4 autoload)
  - `main/app/Providers/AppServiceProvider.php` (addon registration)
  - `main/app/Console/Kernel.php` (schedule entries)

### User Experience Impact
- Admins gain full analytics and watchlist management UI.
- Users gain view-only analytics and leaderboards.
- No impact on existing trading or signal flows.

## Success Criteria

1. Addon registers correctly via `AddonRegistry::active('dex-analytics-addon')`.
2. All five platform clients return normalized data without mutations.
3. Admin dashboard renders analytics, watchlist, leaderboards, and AI insights.
4. User dashboard renders filtered view-only analytics and leaderboards.
5. Scheduled jobs run reliably and respect rate limits.
6. Tests pass for API clients, normalization, analytics, and UI routes.

## Timeline Estimate

- Phase 1 (Foundation + Clients): 1.5 weeks
- Phase 2 (Normalization + Analytics): 1 week
- Phase 3 (UI + Jobs): 1 week
- Phase 4 (Testing + Docs): 0.5 week

## Risks

- Platform API changes or rate limits.
- High polling volume for large watchlists.
- AI dependency failures or latency.

## Dependencies

- AI Connection addon (for AI insights)
- Existing admin/user layout templates
- Scheduler integration in `Kernel.php`
