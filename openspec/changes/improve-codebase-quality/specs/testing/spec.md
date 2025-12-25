## ADDED Requirements

### Requirement: Comprehensive Test Coverage
The system SHALL achieve 80%+ test coverage for all code, with 100% coverage for critical trading logic.

#### Scenario: Service has unit tests
- **WHEN** a service class is created or modified
- **THEN** the service SHALL have unit tests covering all public methods
- **AND** tests SHALL cover both success and error cases
- **AND** tests SHALL use mocks for external dependencies

#### Scenario: Workflow has feature tests
- **WHEN** a critical workflow is implemented
- **THEN** the workflow SHALL have feature tests
- **AND** tests SHALL cover the complete user journey
- **AND** tests SHALL verify database state changes

### Requirement: Test Structure and Conventions
Tests SHALL follow a consistent structure and naming conventions.

#### Scenario: Test file follows conventions
- **WHEN** a test file is created
- **THEN** the file SHALL be in `tests/Unit/` or `tests/Feature/`
- **AND** the file SHALL be named `{Class}Test.php`
- **AND** test methods SHALL be named `test_{description}`

#### Scenario: Test uses factories
- **WHEN** test data is needed
- **THEN** tests SHALL use model factories
- **AND** factories SHALL be defined for all models
- **AND** test data SHALL be isolated per test

### Requirement: Property-Based Testing for Financial Logic
Financial calculations SHALL use property-based tests to ensure correctness.

#### Scenario: Financial calculation is tested
- **WHEN** a financial calculation method is implemented
- **THEN** the method SHALL have property-based tests
- **AND** tests SHALL verify mathematical properties (commutativity, associativity)
- **AND** tests SHALL check edge cases (zero, negative, large numbers)

#### Scenario: Position sizing calculation is correct
- **WHEN** position sizing is calculated
- **THEN** the calculation SHALL be tested with various inputs
- **AND** tests SHALL verify risk limits are respected
- **AND** tests SHALL check for rounding errors

### Requirement: Integration Testing
External API integrations SHALL have integration tests with mocked responses.

#### Scenario: Payment gateway is tested
- **WHEN** a payment gateway integration is implemented
- **THEN** the integration SHALL have tests with mocked API responses
- **AND** tests SHALL cover success, failure, and timeout scenarios
- **AND** tests SHALL verify error handling

#### Scenario: External API is mocked
- **WHEN** tests interact with external APIs
- **THEN** external API calls SHALL be mocked
- **AND** mock responses SHALL cover various scenarios
- **AND** tests SHALL verify request parameters

### Requirement: Test Coverage Reporting
Test coverage SHALL be tracked and reported.

#### Scenario: Coverage is measured
- **WHEN** tests are run
- **THEN** coverage SHALL be measured and reported
- **AND** coverage reports SHALL identify uncovered code
- **AND** coverage SHALL be tracked over time

#### Scenario: Coverage threshold is enforced
- **WHEN** code coverage falls below 80%
- **THEN** the CI/CD pipeline SHALL fail
- **AND** developers SHALL be notified to add tests
- **AND** critical trading logic SHALL require 100% coverage

## Test-Driven Development (TDD) Strategy

### TDD Methodology

Test-Driven Development (TDD) is a software development approach where tests are written before the implementation code. The TDD cycle follows three phases: Red, Green, Refactor.

**Red-Green-Refactor Cycle**:
1. **RED**: Write a failing test that describes the desired behavior
2. **GREEN**: Write the minimal code necessary to make the test pass
3. **REFACTOR**: Improve the code while keeping tests green
4. **REPEAT**: Continue the cycle for the next feature or improvement

### TDD Workflow

#### Phase 1: RED - Write Failing Test
- Write a test that describes the expected behavior
- The test MUST fail initially (no implementation exists)
- Test failure confirms the test is valid and the feature is missing
- Example:
```php
public function test_can_create_signal(): void
{
    // Given: Valid signal data
    $data = [
        'title' => 'Test Signal',
        'currency_pair_id' => 1,
        'direction' => 'buy',
        // ... more fields
    ];
    
    // When: Creating signal
    $result = $this->signalService->create($data);
    
    // Then: Signal should be created successfully
    $this->assertServiceSuccess($result);
    $this->assertDatabaseHas('signals', ['title' => 'Test Signal']);
}
```

#### Phase 2: GREEN - Write Minimal Implementation
- Write the simplest code that makes the test pass
- Avoid over-engineering at this stage
- Focus on making the test green, not perfect code
- Example:
```php
public function create(array $data): array
{
    $signal = Signal::create($data);
    return $this->success('Signal created', $signal);
}
```

#### Phase 3: REFACTOR - Improve Code
- Once tests pass, refactor the code for better design
- Extract methods, improve naming, reduce duplication
- Keep all tests green during refactoring
- Tests provide safety net for refactoring
- Example:
```php
public function create(array $data): array
{
    $this->validateInput($this->getValidationRules(), $data);
    
    $signal = $this->transaction(function () use ($data) {
        return Signal::create($data);
    });
    
    return $this->success('Signal created', $signal);
}
```

### TDD Best Practices

#### Test Naming Conventions

**Given-When-Then Structure**:
- Test method names should clearly describe the scenario
- Format: `test_{given}_{when}_{then}`
- Example: `test_user_with_valid_credentials_can_login()`

**Descriptive Names**:
- Use descriptive names that explain what is being tested
- Avoid generic names like `test1()`, `test_signal()`
- Good: `test_signal_creation_requires_currency_pair()`
- Bad: `test_create()`

#### Test Isolation

**Independent Tests**:
- Each test MUST be independent and runnable in isolation
- Tests MUST NOT depend on execution order
- Tests MUST NOT share state between runs
- Use `RefreshDatabase` trait to ensure clean database state

**Test Data Isolation**:
- Each test creates its own test data
- Use factories to create test data
- Clean up test data after each test (automatic with `RefreshDatabase`)

#### Test Data Management

**Factories**:
- Use model factories for creating test data
- Factories provide consistent, valid test data
- Factories support relationships and variations
- Example:
```php
$user = User::factory()->create();
$signal = Signal::factory()->for($user)->create();
```

**Seeders** (for integration tests):
- Use seeders for complex test scenarios
- Seeders provide realistic test data
- Use `RefreshDatabase` to reset between tests

**Fixtures** (for complex data):
- Use fixtures for complex, multi-model scenarios
- Fixtures provide reusable test data setups
- Store fixtures in `tests/Fixtures/`

#### Mocking Strategy

**When to Mock**:
- External APIs (payment gateways, Telegram, AI providers)
- External services (file storage, email services)
- Slow operations (database queries in unit tests)
- Non-deterministic behavior (random numbers, timestamps)

**What to Mock**:
- HTTP clients for external APIs
- Queue jobs (use `Queue::fake()`)
- Events (use `Event::fake()`)
- Mail (use `Mail::fake()`)
- Cache (use `Cache::fake()`)

**Mocking Guidelines**:
- Mock at service boundaries, not internal implementation
- Use Laravel's built-in fakes when available
- Verify mock interactions when necessary
- Keep mocks simple and focused

**Example Mocking**:
```php
public function test_payment_processing_calls_gateway_api(): void
{
    Http::fake([
        'gateway.com/api/payment' => Http::response(['status' => 'success'], 200),
    ]);
    
    $result = $this->paymentService->process($payment);
    
    Http::assertSent(function ($request) {
        return $request->url() === 'gateway.com/api/payment';
    });
}
```

### TDD Scenarios

#### Scenario: New Service Method Development

**Step 1: Write Failing Test**:
```php
public function test_can_calculate_position_size(): void
{
    $accountBalance = 10000;
    $riskPercentage = 2;
    $entryPrice = 100;
    $stopLoss = 95;
    
    $positionSize = $this->riskService->calculatePositionSize(
        $accountBalance,
        $riskPercentage,
        $entryPrice,
        $stopLoss
    );
    
    $this->assertEquals(200, $positionSize);
}
```

**Step 2: Write Minimal Implementation**:
```php
public function calculatePositionSize(
    float $accountBalance,
    float $riskPercentage,
    float $entryPrice,
    float $stopLoss
): float {
    $riskAmount = $accountBalance * ($riskPercentage / 100);
    $priceDifference = abs($entryPrice - $stopLoss);
    return $riskAmount / $priceDifference;
}
```

**Step 3: Refactor**:
```php
public function calculatePositionSize(
    float $accountBalance,
    float $riskPercentage,
    float $entryPrice,
    float $stopLoss
): float {
    $this->validateRiskInputs($accountBalance, $riskPercentage, $entryPrice, $stopLoss);
    
    $riskAmount = $this->calculateRiskAmount($accountBalance, $riskPercentage);
    $priceDifference = $this->calculatePriceDifference($entryPrice, $stopLoss);
    
    return $this->calculateUnits($riskAmount, $priceDifference);
}
```

#### Scenario: Bug Fix with TDD

**Step 1: Write Failing Test** (reproduces the bug):
```php
public function test_signal_creation_handles_null_description(): void
{
    $data = [
        'title' => 'Test Signal',
        'description' => null, // Bug: null description causes error
        'currency_pair_id' => 1,
    ];
    
    $result = $this->signalService->create($data);
    
    $this->assertServiceSuccess($result);
}
```

**Step 2: Fix Implementation**:
```php
public function create(array $data): array
{
    // Fix: Handle null description
    $data['description'] = $data['description'] ?? '';
    
    $signal = Signal::create($data);
    return $this->success('Signal created', $signal);
}
```

**Step 3: Refactor** (if needed):
```php
public function create(array $data): array
{
    $data = $this->normalizeSignalData($data);
    $signal = Signal::create($data);
    return $this->success('Signal created', $signal);
}

protected function normalizeSignalData(array $data): array
{
    return array_merge([
        'description' => '',
    ], $data);
}
```

#### Scenario: Refactoring with Test Coverage

**Step 1: Ensure Test Coverage**:
- All methods have tests
- All edge cases are covered
- Tests are green

**Step 2: Refactor Code**:
- Extract methods
- Improve naming
- Reduce duplication
- Optimize performance

**Step 3: Verify Tests Still Pass**:
- Run test suite
- All tests should remain green
- If tests fail, fix refactoring issues

**Example**:
```php
// Before refactoring
public function processPayment($payment) {
    // 50 lines of code
}

// After refactoring
public function processPayment($payment) {
    $this->validatePayment($payment);
    $gateway = $this->getGateway($payment);
    $result = $this->callGateway($gateway, $payment);
    return $this->handleGatewayResponse($result, $payment);
}

// Tests remain green, code is more maintainable
```

#### Scenario: Integration Test Development

**Step 1: Write Failing Integration Test**:
```php
public function test_payment_workflow_creates_subscription(): void
{
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    
    $payment = $this->postJson('/api/payments', [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'gateway_id' => 1,
    ]);
    
    $payment->assertStatus(201);
    $this->assertDatabaseHas('plan_subscriptions', [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
}
```

**Step 2: Implement Integration**:
- Implement payment endpoint
- Implement subscription creation
- Handle gateway callback

**Step 3: Refactor**:
- Extract common patterns
- Improve error handling
- Add logging

### Property-Based Testing Details

#### Framework Selection

**PHPUnit + Eris**:
- Eris is a property-based testing library for PHP
- Integrates with PHPUnit
- Provides generators for test data
- Supports shrinking for failure cases

**Installation**:
```bash
composer require --dev giorgiosironi/eris
```

**Alternative: Custom Property-Based Testing**:
- Create custom generators for specific domains
- Use PHPUnit data providers with random data
- Implement shrinking logic manually

#### Property Definitions for Financial Calculations

**Position Sizing Properties**:
```php
use Eris\Generator;

public function test_position_sizing_respects_risk_limits(): void
{
    $this->forAll(
        Generator\choose(1000, 100000),      // Account balance
        Generator\choose(1, 10),             // Risk percentage
        Generator\choose(10, 1000),          // Entry price
        Generator\choose(1, 100)             // Stop loss distance
    )->then(function ($balance, $riskPct, $entryPrice, $stopLossDist) {
        $stopLoss = $entryPrice - $stopLossDist;
        
        $positionSize = $this->riskService->calculatePositionSize(
            $balance,
            $riskPct,
            $entryPrice,
            $stopLoss
        );
        
        // Property: Position size should never exceed account balance
        $this->assertLessThanOrEqual($balance, $positionSize * $entryPrice);
        
        // Property: Risk amount should match risk percentage
        $riskAmount = abs($entryPrice - $stopLoss) * $positionSize;
        $expectedRisk = $balance * ($riskPct / 100);
        $this->assertEqualsWithDelta($expectedRisk, $riskAmount, 0.01);
    });
}
```

**Profit Calculation Properties**:
```php
public function test_profit_calculation_is_commutative(): void
{
    $this->forAll(
        Generator\choose(1, 1000),  // Quantity
        Generator\choose(10, 1000),  // Entry price
        Generator\choose(10, 1000)   // Exit price
    )->then(function ($quantity, $entryPrice, $exitPrice) {
        $profit1 = $this->calculateProfit($quantity, $entryPrice, $exitPrice);
        $profit2 = $this->calculateProfit($quantity, $exitPrice, $entryPrice);
        
        // Property: Profit calculation should be consistent
        $this->assertEquals(abs($profit1), abs($profit2));
    });
}
```

#### Generator Strategies for Test Data

**Financial Data Generators**:
```php
use Eris\Generator;

// Generate valid currency pairs
$currencyPair = Generator\elements('EUR/USD', 'GBP/USD', 'BTC/USDT');

// Generate valid prices (positive, reasonable range)
$price = Generator\choose(0.01, 100000)->map(function ($n) {
    return round($n, 2);
});

// Generate valid percentages (0-100)
$percentage = Generator\choose(0, 100)->map(function ($n) {
    return round($n, 2);
});

// Generate valid timestamps
$timestamp = Generator\choose(
    strtotime('2020-01-01'),
    strtotime('2030-12-31')
);
```

**Custom Generators**:
```php
use Eris\Generator;

class FinancialGenerators
{
    public static function accountBalance(): Generator
    {
        return Generator\choose(100, 1000000)->map(function ($n) {
            return round($n, 2);
        });
    }
    
    public static function riskPercentage(): Generator
    {
        return Generator\choose(0.1, 10)->map(function ($n) {
            return round($n, 2);
        });
    }
    
    public static function price(): Generator
    {
        return Generator\choose(0.01, 100000)->map(function ($n) {
            return round($n, 2);
        });
    }
}
```

#### Shrinking Strategies for Failure Cases

**Eris Automatic Shrinking**:
- Eris automatically shrinks failing test cases
- Finds minimal failing example
- Helps identify root cause of failure

**Example Shrinking**:
```php
// Original failing case
$balance = 12345.67;
$riskPct = 5.5;
$entryPrice = 987.65;
$stopLoss = 900.00;

// Shrunk failing case (minimal example)
$balance = 100.00;
$riskPct = 1.0;
$entryPrice = 100.00;
$stopLoss = 99.00;
```

**Custom Shrinking** (if needed):
```php
use Eris\Shrinker;

$shrinker = new Shrinker(
    function ($testCase) {
        return $this->runTest($testCase);
    },
    $failingTestCase
);

$minimalFailingCase = $shrinker->shrink();
```

### TDD Workflow Integration

#### Development Workflow

1. **Write Test First** (RED)
   - Write failing test
   - Run test to confirm failure
   - Commit test (optional: WIP commit)

2. **Implement Feature** (GREEN)
   - Write minimal code to pass test
   - Run test to confirm pass
   - Commit implementation

3. **Refactor** (REFACTOR)
   - Improve code quality
   - Run tests to ensure still green
   - Commit refactoring

4. **Repeat** for next feature

#### CI/CD Integration

**Pre-commit Hooks** (optional):
- Run tests before commit
- Prevent committing if tests fail
- Fast feedback loop

**Pull Request Checks**:
- Run full test suite
- Check test coverage
- Require tests for new features
- Block merge if tests fail or coverage drops

#### TDD Metrics

**Track TDD Adoption**:
- Percentage of features developed with TDD
- Test coverage for TDD-developed features
- Bug rate comparison (TDD vs non-TDD features)
- Refactoring confidence (developer surveys)

**Success Indicators**:
- 80%+ of new features developed with TDD
- Test coverage > 80% overall
- Reduced bug rate in TDD-developed features
- Increased developer confidence in refactoring


