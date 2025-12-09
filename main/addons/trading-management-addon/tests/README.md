# Trading Management Addon Tests

## Test Structure

### Unit Tests
- `Unit/TradingBotServiceTest.php` - Service layer unit tests
- `Unit/PositionControlServiceTest.php` - Position control unit tests

### Feature Tests
- `Feature/EndToEndTradingBotTest.php` - Complete end-to-end flow tests
- `Feature/CopyTradingTest.php` - Copy trading flow tests

### Integration Tests
- `Integration/TradingBotIntegrationTest.php` - Database integration tests

## Running Tests

```bash
# Run all tests
php artisan test --testsuite=Feature

# Run specific test
php artisan test tests/Feature/EndToEndTradingBotTest.php

# Run with coverage
php artisan test --coverage
```

## Test Scenarios

### 1. Signal-Based Bot Flow
- Bot creation and configuration
- Bot start/stop/pause/resume
- Signal reception and processing
- Position creation
- Position monitoring
- SL/TP execution
- Position closure

### 2. Market Stream-Based Bot Flow
- Real-time market data consumption
- Technical indicator calculation
- Trade decision making
- Execution flow

### 3. Copy Trading Flow
- Subscription creation
- Position copying
- Quantity calculation
- Execution synchronization
- Position closure synchronization

### 4. Expert Advisor Integration
- EA execution
- Signal generation
- Parameter passing

### 5. Position Management
- Real-time updates
- TP/SL modification
- Manual closure
- Balance updates

## Mocking

For tests that require external APIs:
- Mock `AdapterFactory` for exchange adapters
- Mock `MetaApiAdapter` for MetaAPI calls
- Mock `CcxtAdapter` for CCXT calls

## Database

Tests use `RefreshDatabase` trait to ensure clean state between tests.
