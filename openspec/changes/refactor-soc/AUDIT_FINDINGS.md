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

1. ✅ **COMPLETED**: Create `CopyTradingRepository` for CopyTradingController
   - Created `CopyTradingRepositoryInterface` and `CopyTradingRepository`
   - Created `CopyTradingService` for business logic
   - Refactored `CopyTradingController` to use service layer
   - Registered in `AppServiceProvider`

2. ✅ **COMPLETED**: Create `TradeRepository` for CryptoTradeController
   - Created `TradeRepositoryInterface` and `TradeRepository`
   - Refactored `CryptoTradeController` to use repository
   - Registered in `AppServiceProvider`

3. ✅ **COMPLETED**: Refactor API TradingOperationsController to use ExecutionRepository
   - Created `ApiTradingOperationsService` that encapsulates DB operations
   - Refactored `TradingOperationsController` to use service
   - Registered in `AppServiceProvider`

4. ✅ **COMPLETED**: Create `LanguageTranslationRepository` for LanguageTranslationController
   - Created `LanguageTranslationRepositoryInterface` and `LanguageTranslationRepository`
   - Created `LanguageTranslationService` for business logic
   - Refactored `LanguageTranslationController` to use service layer
   - Registered in `AppServiceProvider`

5. ⚠️ **DEFERRED**: Consider moving `manualTrade()` business logic to a dedicated service method
   - The `manualTrade()` method in `ExecutionOperationsService` contains complex adapter handling
   - This is acceptable for now as it's already in a service class
   - Can be further refactored in a future iteration if needed

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

#### ✅ **COMPLETED**: High-Priority Admin View Script Extraction
1. ✅ **admins/index.blade.php** - Extracted to `js/pages/admin/admins-index.js`
2. ✅ **admins/create.blade.php** - Extracted to `js/pages/admin/admins-form.js`
3. ✅ **admins/edit.blade.php** - Extracted to `js/pages/admin/admins-form.js`
4. ✅ **users/index.blade.php** - Extracted to `js/pages/admin/users-index.js`
5. ✅ **users/details.blade.php** - Extracted to `js/pages/admin/users-details.js`
6. ✅ **users/kyc_details.blade.php** - Extracted to `js/pages/admin/users-kyc-details.js`

#### ✅ **COMPLETED**: Standard Gateway View Script Extraction
1. ✅ **gateway/paypal.blade.php** - Using shared `gateway-form.js`
2. ✅ **gateway/stripe.blade.php** - Using shared `gateway-form.js`
3. ✅ **gateway/paystack.blade.php** - Using shared `gateway-form.js`
4. ✅ **gateway/perfectmoney.blade.php** - Using shared `gateway-form.js`
5. ✅ **gateway/razorpay.blade.php** - Using shared `gateway-form.js`
6. ✅ **gateway/paytm.blade.php** - Using shared `gateway-form.js`
7. ✅ **gateway/vougepay.blade.php** - Using shared `gateway-form.js`
8. ✅ **gateway/mollie.blade.php** - Using shared `gateway-form.js`
9. ✅ **gateway/nowpayments.blade.php** - Using shared `gateway-form.js`
10. ✅ **gateway/gourl.blade.php** - Needs custom script (complex currency management)
11. ✅ **gateway/coinpayments.blade.php** - Using shared `gateway-form.js`
12. ✅ **gateway/paghiper.blade.php** - Using shared `gateway-form.js`
13. ✅ **gateway/mercadopago.blade.php** - Using shared `gateway-form.js`
14. ✅ **gateway/flutterwave.blade.php** - Using shared `gateway-form.js`

#### ⚠️ **REMAINING**: Complex Gateway Views (Require Manual Extraction)
These files have complex Blade template dependencies and dynamic HTML generation that need careful extraction:

1. **gateway/create_bank.blade.php** - Dynamic bank field addition + payment proof fields
2. **gateway/bank.blade.php** - Dynamic bank field management with Blade conditionals
3. **gateway/create.blade.php** - Dynamic user proof parameter field addition
4. **gateway/edit.blade.php** - Dynamic user proof parameter field management
5. **gateway/index.blade.php** - Gateway status toggle with alert system detection
6. **gateway/gourl.blade.php** - Complex currency management with dynamic HTML templates

**Recommendation**: These should be extracted manually, passing Blade-generated templates via `window` variables or data attributes.

#### 📋 **DEFERRED**: Remaining View Cleanup
1. Extract inline styles from backend views to `resources/css/pages/admin/*.css`
   - Use `@push('styles')` pattern
   - Consider using Laravel Mix for compilation
   - **Status**: ~102 inline styles remaining

2. Review and extract inline scripts from user views
   - Lower priority but should be addressed for consistency
   - **Status**: ~280+ inline scripts remaining in user-facing views

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
3. ✅ **COMPLETED**: Introduce Livewire for modern reactive UI components
   - Integrated Livewire v3
   - Implemented pilot components (DataTable, Modal, Notifications, ToggleSwitch, FormWizard)
   - Migrated Admin Users Table and Gateway Management to Livewire
   - Added Exchange Connection Wizard feature


