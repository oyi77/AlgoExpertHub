# Design: Multi-Perp DEX Analytics Addon

## Context

AlgoExpertHub needs a dedicated, read-only analytics addon for perpetual DEX platforms to provide verified trader intelligence without execution. The addon must align with existing addon patterns (service providers, routes, views, migrations) and integrate with the AI Connection addon for insights.

**Stakeholders**:
- Admins: manage watchlists and analytics dashboards
- Users: view-only analytics and leaderboards
- Engineering: maintainable addon architecture

**Constraints**:
- No trading or order signing
- Batch polling only (no real-time streaming)
- Schedule entries must be added in `Kernel.php`
- Use existing Blade + jQuery + DataTables + Chart.js stack

## Goals / Non-Goals

### Goals
1. Multi-platform ingestion for GMX, Hyperliquid, Aster, Lighter, dYdX v4
2. Normalized storage with provenance and audit trail
3. Admin analytics UI and watchlist management
4. User view-only analytics UI
5. AI-driven clustering and insights
6. Reliable polling and refresh scheduling

### Non-Goals
1. Trade execution or wallet management
2. WebSocket streaming
3. Historical backfill beyond forward polling
4. Automated alerts/trading actions from AI

## Architecture Overview

### Core Components
- **Platform Clients**: One per DEX to fetch positions, PnL, funding, liquidations
- **Normalization Layer**: Converts platform data to unified schema
- **Storage Layer**: Tables for positions, PnL, funding, liquidations, labels, provenance
- **Analytics Engine**: Computes metrics, leaderboards, copy suitability
- **AI Insights**: Behavior clustering via AI Connection addon
- **UI Layer**: Admin + User dashboards, tables, charts

### Data Flow
1. Polling job fetches platform data per watchlist trader
2. Normalization service standardizes records
3. Storage services persist snapshots and logs
4. Analytics engine computes metrics and leaderboards
5. UI renders charts and tables from computed data

## Key Decisions

### Decision 1: UI Pattern
**Choice**: Multi-page dashboard with tabbed analytics pages (Trading Management style)

**Rationale**:
- Complex feature set requires separation of concerns
- Existing admin UX already follows this pattern

### Decision 2: Update Strategy
**Choice**: Manual refresh + scheduled background jobs

**Rationale**:
- Adequate for analytics workflows
- Avoids WebSocket complexity

### Decision 3: Access Model
**Choice**: Admin has full CRUD, users have view-only filtered data

**Rationale**:
- Aligns with subscription model
- Reduces risk of unintended changes

## UI Structure

### Admin Pages
- Dashboard
- Watchlist
- Analytics (tabs: Performance, PnL, Positions, Funding, Liquidations)
- Leaderboards (tabs: Top Traders, Smart Money, Copy-Suitable)
- AI Insights
- Settings

### User Pages
- Dashboard
- Watchlist (view-only)
- Analytics (filtered)
- Leaderboards (public)
- AI Insights (subscription-based)

## Data Model (Tables)

- `dex_trader_watchlist`
- `dex_position_snapshots`
- `dex_pnl_records`
- `dex_funding_logs`
- `dex_liquidation_events`
- `dex_analytics_cache`
- `dex_provenance_logs`
- `dex_trader_labels`
- `dex_copy_suitability`

## Risks / Trade-offs

- **Platform API stability**: mitigate with rate limiting and fallback handling
- **Polling scale**: mitigate with caching and pagination
- **AI latency**: degrade gracefully if AI is unavailable

## Validation

- `openspec validate dex-analytics-addon --strict`
- `php artisan test --filter=DexAnalytics`
- `php artisan schedule:list` includes polling commands

## Open Questions

- Subscription tiers for user AI insights (basic vs premium)
- Maximum watchlist size for admin accounts
