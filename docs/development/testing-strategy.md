# Testing Strategy Guide

## Overview

This document outlines the testing strategy for the AlgoExpertHub trading platform, covering unit tests, feature tests, integration tests, and property-based tests.

## Test Structure

```
tests/
├── Unit/              # Unit tests for individual classes
│   ├── Services/      # Service layer tests
│   ├── Models/        # Model tests
│   └── Helpers/       # Helper function tests
├── Feature/           # Feature/integration tests
│   ├── Auth/          # Authentication flows
│   ├── Trading/       # Trading workflows
│   └── Payment/       # Payment processing
└── Integration/       # External API integration tests
    └── Gateways/      # Payment gateway tests
```

## Testing Conventions

### Unit Tests
- Test individual methods in isolation
- Mock external dependencies
- Focus on business logic
- Aim for 90%+ coverage on service layer

### Feature Tests
- Test complete user workflows
- Use database transactions for isolation
- Test authentication and authorization
- Verify API responses and status codes

### Integration Tests
- Test external API integrations
- Mock HTTP responses where possible
- Test error handling and retries
- Verify webhook processing

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Unit/Services/SignalServiceTest.php
```

## Test Factories

All models have factories for easy test data generation:

```php
// Create single instance
$user = User::factory()->create();

// Create multiple instances
$signals = Signal::factory()->count(10)->create();

// Create with specific attributes
$plan = Plan::factory()->create(['price' => 100]);

// Use factory states
$activeSignal = Signal::factory()->active()->create();
```

## Best Practices

1. **Arrange-Act-Assert Pattern**: Structure tests clearly
2. **Descriptive Test Names**: Use `it_does_something` naming
3. **One Assertion Per Test**: Keep tests focused
4. **Mock External Services**: Don't make real API calls
5. **Use Factories**: Generate test data consistently
6. **Clean Up**: Use database transactions or tearDown
7. **Test Edge Cases**: Cover error conditions
8. **Fast Tests**: Keep unit tests under 100ms

## Coverage Goals

- Overall: 80%+
- Service Layer: 90%+
- Critical Trading Logic: 100%
- Payment Processing: 100%
- Financial Calculations: 100%
