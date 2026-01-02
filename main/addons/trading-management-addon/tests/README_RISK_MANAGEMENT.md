# Risk Management Testing Guide

This document describes the testing structure for the Risk Management Overhaul (v2.1.0).

## Test Structure

Tests are organized in `tests/Unit/RiskManagement/` for unit tests of individual services.

## Test Files

### Unit Tests

1. **SymbolSpecServiceTest.php**
   - Tests pip size calculation for different symbol types (FX, JPY, Crypto)
   - Tests contract size retrieval
   - Tests pip value calculation
   - Tests symbol specification retrieval

2. **MarginManagementServiceTest.php**
   - Tests required margin calculation with different leverage
   - Tests margin level checks
   - Tests margin call detection
   - Tests trade prevention based on margin requirements

3. **SlippageProtectionServiceTest.php**
   - Tests slippage calculation (expected vs executed price)
   - Tests slippage validation
   - Tests slippage prediction
   - Tests stop-loss adjustment for slippage

4. **CorrelationRiskServiceTest.php**
   - Tests correlation matrix retrieval
   - Tests correlated symbol identification
   - Tests exposure calculation
   - Tests trade prevention based on correlation limits

5. **PositionLimitServiceTest.php**
   - Tests open position counting
   - Tests position counting per symbol
   - Tests trade prevention when limits reached

## Running Tests

### Run All Risk Management Tests

```bash
cd main
php artisan test --filter=RiskManagement
```

### Run Specific Test File

```bash
# SymbolSpecService tests
php artisan test tests/Unit/RiskManagement/SymbolSpecServiceTest.php

# MarginManagementService tests
php artisan test tests/Unit/RiskManagement/MarginManagementServiceTest.php

# SlippageProtectionService tests
php artisan test tests/Unit/RiskManagement/SlippageProtectionServiceTest.php

# CorrelationRiskService tests
php artisan test tests/Unit/RiskManagement/CorrelationRiskServiceTest.php

# PositionLimitService tests
php artisan test tests/Unit/RiskManagement/PositionLimitServiceTest.php
```

### Run with PHPUnit Directly

```bash
cd main
./vendor/bin/phpunit addons/trading-management-addon/tests/Unit/RiskManagement/
```

## Additional Tests Needed

The following services need comprehensive tests:

1. **EnhancedMetricsServiceTest**
   - Test expectancy calculation
   - Test Sortino ratio calculation
   - Test MAE/MFE calculation
   - Test Recovery Factor calculation
   - Test Calmar Ratio calculation

2. **MarketHoursServiceTest**
   - Test market open/closed status for different markets
   - Test holiday detection
   - Test next trading day calculation

3. **BacktestSlippageModelTest**
   - Test slippage calculation in backtests
   - Test spread cost calculation
   - Test execution price modeling

4. **Integration Tests**
   - Test RiskManagementJob with all new services integrated
   - Test ExecutionService with circuit breaker and slippage
   - Test ExecutionJob with position limits and market hours
   - Test end-to-end trade execution flow with all validations

## Test Coverage Goals

- **Unit Tests**: 80%+ coverage for all new services
- **Integration Tests**: Key workflows tested end-to-end
- **Edge Cases**: Boundary conditions, error scenarios, null values

## Test Data

Tests use Laravel factories where possible:
- `ExecutionConnection::factory()`
- `ExecutionPosition::factory()`
- `User::factory()`

For services that don't require database, use direct instantiation:
```php
$service = app(SymbolSpecService::class);
```

## Mocking

For external dependencies or expensive operations:
- Mock exchange API responses
- Mock database queries (for complex tests)
- Use in-memory cache for test isolation

## Continuous Integration

These tests should be run:
- Before committing code changes
- In CI/CD pipeline
- Before deploying to production
- After any changes to risk management services

---

**Status**: Basic unit tests created for core services  
**Next Steps**: Add tests for remaining services and integration tests

