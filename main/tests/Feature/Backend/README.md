# Backend Feature Tests

## Overview

Backend feature tests are designed to verify core functionality without requiring a fully migrated database or complex setup. Tests focus on service functionality and component integration.

## Running Tests

```bash
# All backend tests
docker exec 1Panel-php8-mrTy sh -c "cd /www/sites/aitradepulse.com/index/main && php artisan test tests/Feature/Backend"

# Specific test
docker exec 1Panel-php8-mrTy sh -c "cd /www/sites/aitradepulse.com/index/main && php artisan test --filter=SystemMonitoringTest"
```

## SystemMonitoring Tests

The `SystemMonitoringTest` suite verifies:

- ✅ Service instantiation and dependency injection
- ✅ Metric collection (CPU, memory, disk, database, cache)
- ✅ Worker status aggregation
- ✅ Alert generation
- ✅ Historical data recording

These tests **do not require**:
- Database migrations
- Admin authentication
- HTTP route setup
- Trading bot addon migrations

## Migration Issues (Trading Management Addon)

⚠️ **Known Issue**: The trading management addon has migration ordering conflicts:

- `2025_01_22_*` migrations (ALTER table) run before
- `2025_01_01_*` migration (CREATE table)

**Solution Applied**: Renamed `2025_12_04_100015_create_trading_bots_table.php` to `2025_01_01_100015_create_trading_bots_table.php` to ensure CREATE runs first.

**Why This Matters**: This prevents "table not found" errors during migrations in test environments.

## Testing Philosophy

Tests are designed to be:
- **Fast**: No database migrations per test
- **Isolated**: Each test is independent
- **Service-focused**: Test business logic, not HTTP layers
- **Maintainable**: Easy to understand and update

For full end-to-end HTTP testing, use browser testing or manual QA on staging/production environments.

