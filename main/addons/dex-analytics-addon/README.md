# DEX Analytics Addon

Multi-perp DEX analytics addon for AlgoExpertHub - track and analyze trader performance across GMX, Hyperliquid, Aster, Lighter, and dYdX v4 perpetual DEX platforms.

## Overview

This addon provides comprehensive analytics for perpetual DEX traders by:
- Polling positions from multiple DEX platforms via their public APIs
- Computing performance metrics (PnL, win rate, profit factor, drawdown, etc.)
- Generating leaderboards ranked by various metrics
- Providing AI-powered insights and behavior clustering
- Enabling watchlist-based tracking with user/admin access control

## Supported Platforms

| Platform | API Endpoint | Features |
|----------|-------------|----------|
| **GMX** | `https://api.gmx.io` | Position tracking, PnL, funding |
| **Hyperliquid** | `https://api.hyperliquid.xyz` | Position tracking, PnL, funding |
| **Aster** | `https://api.aster.finance` | Position tracking, PnL, funding |
| **Lighter** | `https://api.lighter.xyz` | Position tracking, PnL, funding |
| **dYdX v4** | `https://indexer.dydx.trade` | Position tracking, PnL, funding |

## Features

### Admin Features
- **Watchlist Management**: Add/remove trader wallets to track
- **Analytics Dashboard**: System-wide statistics and platform health
- **Leaderboards**: View top performers across all metrics
- **AI Insights**: Access AI-generated trading insights
- **Settings**: Configure polling intervals, retention, AI connections

### User Features
- **Assigned Watchlists**: View traders assigned to your account
- **Analytics**: Performance metrics filtered to your subscriptions
- **Leaderboards**: Public leaderboards for all users
- **AI Insights**: Subscription-based AI intelligence access

## Installation

### 1. Enable the Addon

```bash
# Via Admin Panel
Navigate to: Admin > Addons > DEX Analytics > Enable

# Via Database
UPDATE addons SET status = 'active' WHERE slug = 'dex-analytics-addon';
```

### 2. Run Migrations

```bash
cd /var/www/main
php artisan migrate
```

This creates the following tables:
- `dex_trader_watchlist` - Tracked traders
- `dex_position_snapshots` - Position history
- `dex_pnl_records` - Realized PnL tracking
- `dex_funding_records` - Funding payments
- `dex_liquidation_events` - Liquidation tracking
- `dex_analytics_cache` - Computed metrics cache
- `dex_leaderboard_cache` - Leaderboard cache
- `dex_ai_insights` - AI-generated insights
- `dex_provenance_logs` - Data lineage tracking

### 3. Configure Environment

Add to `.env`:

```env
# DEX Analytics Configuration
DEX_ANALYTICS_ENABLED=true

# Platform API Endpoints (defaults shown)
GMX_API_URL=https://api.gmx.io
HYPERLIQUID_API_URL=https://api.hyperliquid.xyz
ASTER_API_URL=https://api.aster.finance
LIGHTER_API_URL=https://api.lighter.xyz
DYDX_V4_API_URL=https://indexer.dydx.trade

# Polling Configuration
DEX_POLLING_INTERVAL=60
DEX_REFRESH_INTERVAL=300
DEX_RETENTION_DAYS=365

# AI Configuration
DEX_AI_ENABLED=true
DEX_AI_CONNECTION_ID=1
DEX_AI_MODEL=gpt-4
```

### 4. Enable Modules

Configure which modules are active:

```php
// In addon.json or via admin panel
{
  "modules": {
    "admin_ui": true,
    "user_ui": true,
    "processing": true,
    "api": false,
    "scheduling": true
  }
}
```

### 5. Start Queue Workers

```bash
# Ensure queue workers are running
php artisan queue:work --queue=default

# Or use Horizon (recommended)
php artisan horizon
```

## Usage

### Adding Traders to Watchlist

#### Via Admin Panel
1. Navigate to **Admin > DEX Analytics > Watchlist**
2. Click **Add Trader**
3. Enter wallet address and select platform
4. Optionally assign to a user
5. Click **Save**

#### Via Artisan Command
```bash
php artisan tinker

>>> use Addons\DexAnalyticsAddon\App\Models\DexTraderWatchlist;
>>> DexTraderWatchlist::create([
    'wallet_address' => '0x1234...',
    'platform' => 'hyperliquid',
    'status' => 'active',
    'is_active' => true,
    'assigned_user_id' => 123,
]);
```

### Manual Data Polling

```bash
# Poll positions from all platforms
php artisan dex-analytics:poll

# Refresh analytics metrics
php artisan dex-analytics:refresh
```

### Viewing Analytics

#### Admin Access
- Dashboard: `/admin/dex-analytics/dashboard`
- Watchlist: `/admin/dex-analytics/watchlist`
- Analytics: `/admin/dex-analytics/analytics`
- Leaderboards: `/admin/dex-analytics/leaderboards`
- AI Insights: `/admin/dex-analytics/ai-insights`
- Settings: `/admin/dex-analytics/settings`

#### User Access
- Dashboard: `/user/dex-analytics/dashboard`
- Watchlist: `/user/dex-analytics/watchlist` (view-only, assigned only)
- Analytics: `/user/dex-analytics/analytics` (filtered by assignments)
- Leaderboards: `/user/dex-analytics/leaderboards` (public)
- AI Insights: `/user/dex-analytics/ai-insights` (subscription-based)

## Architecture

### Data Flow

```
Platform APIs → API Clients → Normalization → Snapshot Storage
                                                      ↓
                                            Analytics Computation
                                                      ↓
                                    ┌─────────────────┴─────────────────┐
                                    ↓                                   ↓
                            Leaderboards                          AI Insights
```

### Service Layer

- **Platform API Clients**: `App/Services/Platform/*ApiClientService.php`
- **Normalization**: `DexAnalyticsNormalizationService.php`
- **Storage**: `DexPositionSnapshotService.php`, `DexPnLTrackingService.php`
- **Analytics**: `DexAnalyticsComputationService.php`
- **Leaderboards**: `DexLeaderboardService.php`
- **AI**: `DexAiIntelligenceService.php`

### Jobs & Scheduling

| Job | Frequency | Purpose |
|-----|-----------|---------|
| `PollDexPositionsJob` | Every minute | Fetch positions from all platforms |
| `RefreshDexAnalyticsJob` | Every 5 minutes | Recompute metrics and leaderboards |

## Metrics Computed

- **Total PnL**: Cumulative realized profit/loss
- **Win Rate**: Percentage of profitable trades
- **Profit Factor**: Ratio of gross profit to gross loss
- **Max Drawdown**: Largest peak-to-trough decline
- **Average Trade Size**: Mean position size in USD
- **Average Holding Time**: Mean duration of positions
- **Funding Cost Ratio**: Funding payments relative to PnL
- **Liquidation Rate**: Percentage of trades ending in liquidation
- **Total Exposure**: Current aggregate position size

## AI Intelligence Features

### Insights Generated
- Trading style classification (scalper, swing, position)
- Risk behavior analysis (conservative, moderate, aggressive)
- Pattern recognition (entry/exit timing, market conditions)
- Crowded trade detection
- Market regime analysis
- Trader behavior clustering

### Requirements
- AI Connection Addon must be active
- Valid AI connection configured (OpenAI, Gemini, or OpenRouter)
- AI module enabled in addon settings

## API Reference

### Endpoints (when `api` module enabled)

```
GET  /api/dex-analytics/watchlist
POST /api/dex-analytics/watchlist
GET  /api/dex-analytics/analytics/{wallet}
GET  /api/dex-analytics/leaderboards/{metric}
```

## Troubleshooting

### Position polling not working

**Symptoms**: No position snapshots being created

**Checks**:
1. Verify queue workers are running: `php artisan queue:work`
2. Check scheduler is active: `php artisan schedule:list`
3. Check addon is active: `AddonRegistry::active('dex-analytics-addon')`
4. Check module enabled: `AddonRegistry::moduleEnabled('dex-analytics-addon', 'processing')`
5. View logs: `storage/logs/laravel.log`

### API rate limiting errors

**Symptoms**: `Failed to poll trader positions` errors in logs

**Solution**: Adjust rate limits in `config/dex-analytics.php`:
```php
'platforms' => [
    'hyperliquid' => [
        'rate_limit_per_minute' => 30, // Reduce if hitting limits
    ],
],
```

### AI insights not generating

**Symptoms**: No insights in AI Insights page

**Checks**:
1. AI Connection Addon is active
2. Valid AI connection configured in `.env`: `DEX_AI_CONNECTION_ID=1`
3. Check AI connection status in Admin > AI Connections
4. Verify sufficient API credits

## Performance Optimization

### Database Indexes
All critical queries use composite indexes:
- `(wallet_address, platform)` on watchlist
- `(watchlist_id, snapshot_at)` on position_snapshots
- `(watchlist_id, closed_at)` on pnl_records

### Caching Strategy
- Computed metrics cached in `dex_analytics_cache`
- Leaderboards cached in `dex_leaderboard_cache`
- Cache invalidation on each analytics refresh

### Queue Optimization
- Use Redis for queue backend (recommended)
- Enable Horizon for better queue management
- Set appropriate worker count based on load

## Contributing

### Adding New DEX Platforms

1. Create API client: `App/Services/Platform/NewPlatformApiClientService.php`
2. Implement `fetchPositions()`, `fetchPnL()`, `fetchFunding()` methods
3. Add platform config to `config/dex-analytics.php`
4. Register in job's `platformServices` array
5. Add to normalization service

### Adding New Metrics

1. Add computation logic to `DexAnalyticsComputationService::computeMetricsForWatchlist()`
2. Return metric in array with descriptive key
3. Metric automatically cached and available in leaderboards

## Security

- All admin routes protected by `admin` middleware
- User routes filtered by `assigned_user_id`
- API routes require authentication token
- Wallet addresses never exposed to unauthorized users
- Rate limiting on all API endpoints

## License

Proprietary - Part of AlgoExpertHub Addon Ecosystem

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review OpenSpec docs: `openspec/changes/dex-analytics-addon/`
- Contact: AlgoExpertHub Support

---

**Version**: 1.0.0  
**Last Updated**: 2026-01-21  
**Maintainer**: AlgoExpertHub Team
