# Refactor SoC Audit Findings

Generated: 2025-12-28

## Controller DB Call Audit

### Summary
Found **36+ direct DB:: calls** in controllers across the codebase. These should be moved to repository classes.

### Files with DB:: Calls

#### API Controllers
1. **app/Http/Controllers/Api/User/CopyTradingController.php**
   - Multiple `DB::table()` calls for copy trading subscriptions, trader profiles, executions
   - **Priority**: Medium
   - **Recommendation**: Create `CopyTradingRepository`

2. **app/Http/Controllers/Api/User/CryptoTradeController.php**
   - `DB::table('trades')` calls
   - **Priority**: Medium
   - **Recommendation**: Create `TradeRepository`

3. **app/Http/Controllers/Api/User/TradingOperationsController.php**
   - Multiple `DB::table()` calls for execution logs, connections, positions
   - **Priority**: High (similar to Backend TradingOperationsController)
   - **Recommendation**: Create repository or reuse ExecutionRepository

4. **app/Http/Controllers/Api/Admin/LanguageTranslationController.php**
   - `DB::table('language_translations')` calls
   - **Priority**: Low
   - **Recommendation**: Create `LanguageTranslationRepository`

5. **app/Http/Controllers/Api/DocumentationController.php**
   - `DB::connection()->getPdo()` (health check - acceptable)
   - **Priority**: None (infrastructure check)

### Completed Refactorings

1. **addons/trading-management-addon/Modules/Execution/Controllers/Backend/TradingOperationsController.php**
   - ✅ Refactored to use `ExecutionRepository` and `ExecutionOperationsService`
   - ✅ All read operations (executions, positions, analytics) moved to service/repository
   - ⚠️ `manualTrade()` method still contains business logic (complex adapter handling - can be refactored later)

### Follow-up Tasks

1. Create `CopyTradingRepository` for CopyTradingController
2. Create `TradeRepository` for CryptoTradeController  
3. Refactor API TradingOperationsController to use ExecutionRepository
4. Create `LanguageTranslationRepository` for LanguageTranslationController
5. Consider moving `manualTrade()` business logic to a dedicated service method

## View Inline Script/Style Audit

### Summary
Found **299 inline `<script>` tags** and **102 inline `<style>` tags** in views. Many are in sections that should use `@push('scripts')` or `@push('styles')`.

### High Priority Files (Backend)

1. **resources/views/backend/admins/create.blade.php** - Inline script
2. **resources/views/backend/admins/edit.blade.php** - Inline script
3. **resources/views/backend/admins/index.blade.php** - Inline script
4. **resources/views/backend/users/index.blade.php** - Inline script
5. **resources/views/backend/users/details.blade.php** - Inline script
6. **resources/views/backend/users/kyc_details.blade.php** - Inline script
7. **resources/views/backend/gateway/*.blade.php** - Multiple gateway views with inline scripts

### Completed Cleanups

1. **resources/views/frontend/trading-v1/user/trading_terminal.blade.php**
   - ✅ Already uses `@push('scripts')` and `@push('styles')`
   - ✅ No inline scripts/styles found

### Follow-up Tasks

1. Extract inline scripts from backend admin views to `resources/js/pages/admin/*.js`
2. Extract inline styles from backend views to `resources/css/pages/admin/*.css`
3. Update blade files to use `@push('scripts')` and `@push('styles')`
4. Extract inline scripts from gateway views to dedicated JS files
5. Review and extract inline scripts from user views

### Notes

- Some inline scripts may be acceptable (e.g., configuration/initialization that's page-specific)
- Priority should be on large/complex inline scripts that can be reused or tested independently
- Livewire components can replace some complex jQuery logic in the future

## Service Layer Pattern Status

### Already Using Service Layer
- ✅ `app/Http/Controllers/User/Trading/TradingOperationsController` - Uses `TradingService`
- ✅ `addons/trading-management-addon/Modules/Execution/Controllers/Backend/TradingOperationsController` - Now uses `ExecutionOperationsService`

### Needs Refactoring
- ⚠️ Multiple API controllers still have direct DB calls
- ⚠️ Some controllers mix business logic with HTTP handling

## Recommendations

### Immediate (High Priority)
1. Complete refactoring of API TradingOperationsController
2. Extract inline scripts from high-traffic admin views (users, admins)

### Short Term (Medium Priority)
1. Create repositories for CopyTrading and Trade controllers
2. Extract inline scripts from gateway views
3. Consider Livewire components for complex interactive forms

### Long Term (Low Priority)
1. Complete repository pattern for all controllers
2. Extract all inline scripts/styles
3. Introduce Livewire for modern reactive UI components

