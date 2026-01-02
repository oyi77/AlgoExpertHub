# Spec Delta: Testing Capability

## Capability: `testing`

**Status**: MODIFIED

### Changes to Existing Capability

#### What's Being Added
1. **Integration Tests** for critical trading flows (6 new tests)
2. **Repository Tests** for new repository layer (5 new tests)
3. **Service Tests** for refactored services (3 new tests)

### Current State
- **Total Tests**: 53
  - Feature: 9
  - Unit: 43
  - Property: 5
- **Integration Tests**: 0 (MISSING)
- **Coverage**: Unknown (no integration testing)

### Target State
- **Total Tests**: 73+
  - Feature: 15 (added 6 integration tests)
  - Unit: 53 (added 10 repository/service tests)
  - Property: 5 (unchanged)
- **Integration Tests**: 6 (comprehensive trading flows)
- **Coverage**: Critical paths fully tested

### New Test Files

#### Integration Tests (Feature)
1. **TradingBotExecutionFlowTest.php** - End-to-end bot execution
   - Create bot → Start → Process signal → Execute trade
   
2. **SignalProcessingPipelineTest.php** - Signal flow
   - Receive signal → Filter → Execute → Monitor outcome
   
3. **OrderPlacementTest.php** - Order execution
   - Place order → Verify exchange API call → Confirm execution
   
4. **RiskManagementTest.php** - Risk controls
   - Position sizing → Drawdown limits → Stop loss triggers
   
5. **BacktestingAccuracyTest.php** - Backtest validation
   - Run backtest → Calculate metrics → Verify accuracy

6. **RepositoryIntegrationTest.php** - Repository layer
   - Test repository CRUD → Verify database state

#### Unit Tests
1. **UserRepositoryTest.php** - User data access
2. **SignalRepositoryTest.php** - Signal queries
3. **TradingBotRepositoryTest.php** - Bot operations
4. **BacktestRepositoryTest.php** - Backtest data
5. **ExchangeConnectionRepositoryTest.php** - Connection management
6. **TradingTerminalServiceTest.php** - Order placement service
7. **TradingPairProviderServiceTest.php** - Market data service
8. **PositionManagementServiceTest.php** - Position service

### Test Requirements

#### Integration Test Pattern
```php
class TradingBotExecutionFlowTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_executes_complete_bot_flow()
    {
        // Given: Setup test data
        $user = User::factory()->create();
        $connection = ExchangeConnection::factory()->create();
        
        // When: Perform action
        $bot = TradingBot::create([...]);
        $bot->start();
        
        // Then: Assert outcomes
        $this->assertDatabaseHas('positions', [...]);
        $this->assertEquals('running', $bot->status);
    }
}
```

#### Repository Test Pattern
```php
class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;
    
    protected $repository;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UserRepositoryInterface::class);
    }
    
    /** @test */
    public function it_gets_user_with_subscriptions()
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        
        $result = $this->repository->getWithSubscriptions($user->id);
        
        $this->assertTrue($result->subscriptions->contains($subscription));
    }
}
```

### Testing Commands

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage (requires Xdebug)
php artisan test --coverage

# Run specific test
php artisan test --filter=TradingBotExecutionFlowTest
```

### CI/CD Integration
```yaml
# .github/workflows/tests.yml
- name: Run Tests
  run: php artisan test --parallel
  
- name: Check Coverage
  run: php artisan test --coverage --min=70
```

### Performance Impact
- **Test Execution Time**: ~30 seconds → ~60 seconds (acceptable)
- **CI/CD Pipeline**: Minimal impact (~30s increase)

### Success Criteria
✅ All new tests pass
✅ Existing 53 tests still pass (no regressions)
✅ Integration tests cover critical trading flows
✅ Repository tests verify all CRUD operations
✅ Service tests verify refactored logic
