# Spec Delta: Code Quality Capability

## Capability: `code-quality`

**Status**: MODIFIED

### Changes to Existing Capability

#### What's Being Fixed
1. **Fat Controller Anti-Pattern** - `TradingTerminalController` (676 lines) being refactored
2. **Placeholder Routes** - 5 routes returning raw HTML being removed/implemented
3. **Inconsistent Service Adoption** - Standardizing service layer usage

### Current State
- `TradingTerminalController`: 676 lines (too large)
- Placeholder routes returning HTML strings instead of proper views
- Mixed patterns: some controllers use services, others don't
- Business logic scattered between controllers and services

### Target State
- `TradingTerminalController`: <300 lines (refactored to services)
- All routes return proper views or use controllers
- Consistent service layer pattern across all controllers
- Business logic centralized in services

### Implementation Details

#### Fat Controller Refactoring
**Before**:
```
TradingTerminalController (676 lines)
├── Complex business logic in controller methods
├── Direct exchange API calls
├── Risk calculations inline
└── Database operations mixed with HTTP logic
```

**After**:
```
TradingTerminalController (~150 lines) - HTTP layer only
├── Uses TradingTerminalService
├── Uses TradingPairProviderService  
└── Uses PositionManagementService

Services (NEW):
├── TradingTerminalService - Order placement logic
├── TradingPairProviderService - Market data formatting
└── PositionManagementService - Position operations
```

#### Placeholder Route Cleanup
**Removed Routes** (Lines 22-40 in `addons/trading-management-addon/routes/user.php`):
```php
// DELETE these placeholder routes:
Route::get('/config', fn() => '<h1>...');
Route::get('/operations', fn() => '<h1>...');
Route::get('/strategy', fn() => '<h1>...');
Route::get('/copy-trading', fn() => '<h1>...');
Route::get('/test', fn() => '<h1>...');
```

**Action**: Delete if unused, implement properly if referenced

### Code Quality Metrics

**Before**:
- Average controller size: ~107 lines
- Largest controller: 676 lines
- Service adoption: ~60%
- Placeholder routes: 5

**After**:
- Average controller size: ~100 lines (improved)
- Largest controller: <300 lines (improved)
- Service adoption: 100% (standardized)
- Placeholder routes: 0 (removed)

### Best Practices Enforced
1. **Controllers**: Thin HTTP layer only, delegate to services
2. **Services**: All business logic lives here
3. **Repositories**: All data access goes through repositories
4. **Routes**: Must use controllers or proper view responses

### Testing Requirements
- Existing controller tests must pass after refactoring
- New service tests for extracted logic
- Integration tests to verify functionality unchanged

### Performance Impact
- **Neutral**: Same logic, just reorganized
- **Positive**: Easier to cache and optimize services
- **Positive**: Better code organization improves maintainability

### Migration Strategy
1. Create new services alongside controller
2. Move logic method by method
3. Update controller to use services
4. Test each change incrementally
5. Remove old inline logic after verification

### Rollback Plan
- Keep controller backup before changes
- Revert service injection if issues
- Git revert available as fallback
