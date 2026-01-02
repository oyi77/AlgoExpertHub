# Coding Standards & Best Practices

## Architecture Patterns

### 1. Repository Pattern
**Do not** access Eloquent models directly in Controllers for complex queries. Use Repositories.
- **Interfaces**: Define methods in `app/Repositories/Contracts`.
- **Implementation**: Extend `BaseRepository` in `app/Repositories`.
- **Naming**: `ModelNameRepository`.
- **Requirements**:
    - All complex queries (joins, subqueries) must live here.
    - Return type hints must be strict (`Collection`, `Model`, `?Model`).
    - Use `findByCriteria` for common filtering.

**Example:**
```php
// Bad
$users = User::where('active', 1)->get();

// Good
$users = $this->userRepository->getActiveUsers();
```

### 2. Service Layer
**Do not** put business logic in Controllers. Use Services.
- **Purpose**: Encapsulate complex business rules, multiple repository calls, or 3rd party integrations.
- **Injection**: Inject Services into Controllers via constructor.
- **Requirements**:
    - Services should be stateless where possible.
    - Transactions should be handled within Services, not Controllers.
    - Throw custom Exceptions, do not return generic false/null for errors.

**Example:**
```php
public function placeOrder(Request $request) {
    // Controller validates
    $validated = $request->validate([...]);
    
    // Service executes
    try {
        $result = $this->tradingService->executeOrder($validated);
    } catch (OrderValidationException $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
    
    // Controller responds
    return response()->json($result);
}
```

### 3. Code Review Checklist
- [ ] Logic moved from Controller to Service?
- [ ] Database queries moved to Repository?
- [ ] Unit tests added for new Service/Repository methods?
- [ ] No `env()` calls outside config files?
- [ ] No N+1 queries (eager load relationships)?

### 3. Fat Models vs Fat Controllers
- **Avoid** Fat Controllers (HTTP layer only).
- **Avoid** Fat Models (Data structure only).
- **Use** Services for logic.

## Testing
- **Unit Tests**: Test Services and Repositories in isolation (mock dependencies).
- **Feature Tests**: Test full HTTP flows (Controller -> Service -> DB).
- **Database**: Use `RefreshDatabase` trait for tests interacting with DB.

## Formatting
- Follow PSR-12 coding style.
- Use strict types where possible (`declare(strict_types=1);`).
