# Spec Delta: Repository Layer Capability

## Capability: `repository-layer`

**Status**: NEW (Being Created)

### Overview
Implements the Repository Pattern to centralize data access logic and improve testability, maintainability, and separation of concerns.

### Current State
- Only 6/47 models have repositories (13% coverage)
- Database queries scattered across controllers and services
- Direct Eloquent model usage throughout codebase
- Difficult to test and mock data access

### Target State
- 11/47 models have repositories (23% coverage, focusing on critical models)
- All services use repositories instead of direct model access
- Centralized query logic with consistent patterns
- Easy to test with repository mocking

### Core Components

#### Repositories to Implement
1. **UserRepository** - User management and queries
2. **SignalRepository** - Trading signal operations
3. **BacktestRepository** - Backtesting data access
4. **TradingBotRepository** - Trading bot CRUD operations
5. **ExchangeConnectionRepository** - Exchange connection management

#### Repository Interface Pattern
```php
interface UserRepositoryInterface
{
    // Standard CRUD
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    
    // Custom queries
    public function getWithSubscriptions(int $userId);
    public function searchUsers(string $query, array $filters = []);
    public function getActiveUsers(int $limit = 100);
}
```

### API Changes
- **Breaking Changes**: None (additive only)
- **New APIs**: Repository interfaces and implementations
- **Deprecated APIs**: None (direct model access continues to work)

### Configuration
```php
// config/app.php - Add new provider
'providers' => [
    // ...
    App\Providers\RepositoryServiceProvider::class,
];
```

### Dependencies
- No new packages required
- Uses existing Laravel service container

### Testing Requirements
- Unit tests for each repository implementation
- Test all custom query methods
- Mock repositories in service tests

### Performance Impact
- **Neutral to Positive**: Same queries, better organized
- Easier to optimize (caching layer can be added to repositories)
- No additional database calls

### Migration Strategy
1. Create repositories alongside existing code
2. Update services to use repositories
3. Keep backward compatibility
4. No data migrations needed

### Rollback Plan
- Remove repository service provider binding
- Services fall back to direct model access
- Zero data loss risk
