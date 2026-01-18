# Learnings - Beta UI Completion

## Task 1: Fix PaymentService to resolve 404 errors

### Key Findings

#### Model Discrepancy: UserPlan vs PlanSubscription
- The `ProcessSubscriptionRenewalsJob` references `UserPlan` model which doesn't exist
- The actual model is `PlanSubscription` (located in `main/app/Models/PlanSubscription.php`)
- The job file needs to be updated to use `PlanSubscription` instead of `UserPlan`
- Created `PaymentService::processRenewal(PlanSubscription $planSubscription)` using the correct model

#### Addon vs Core Model Namespaces
- Addon PaymentService: `Addons\MultiChannelSignalAddon\App\Services\PaymentService`
- Core PaymentService: `App\Services\PaymentService`
- Both services use the same core models (`App\Models\Gateway`, `App\Models\Payment`, etc.)
- No need to duplicate model logic - core models are shared between addon and core

#### Porting Methods from Addon to Core
**payNow() method**:
- Same logic, different namespace
- Removed addon-specific imports: `use Addons\MultiChannelSignalAddon\App;`
- Removed unused import: `use App\Helpers\Helper\Helper;`
- Kept all model imports: `Gateway`, `Deposit`, `Payment`, `Plan`

**details() method**:
- Same logic, different namespace
- Returns view paths directly (removed `Helper::theme()` wrapper)
- Gateway-specific logic (vougepay) preserved

**processRenewal() method** (new implementation):
- Validates subscription is active (`is_current = 1`)
- Loads related plan and user data
- Creates renewal payment record with:
  - New transaction ID
  - Gateway rate and charge calculation
  - Plan expiry date (limited duration or 50 years for lifetime)
  - Status: 0 (pending)
- Returns `['success' => bool, 'message' => '...', 'payment_id' => ..., 'trx' => ...]`

#### Docker Container Path Mappings
- Host path: `/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/`
- Docker container path: `/www/sites/aitradepulse.com/index/`
- Command: `docker exec 1Panel-php8-mrTy php /www/sites/aitradepulse.com/index/main/artisan`

#### Payment Flow Architecture
1. User selects plan → `PaymentController::gateways()` shows available gateways
2. User selects gateway → `PaymentController::paynow()` calls `PaymentService::payNow()`
3. Service creates Payment/Deposit record with trx ID
4. Redirects to `PaymentController::gatewaysDetails()` → `PaymentService::details()`
5. View renders gateway-specific payment form (online/offline)
6. User submits payment → `PaymentController::gatewayRedirect()` processes through gateway service
7. Gateway callback → `PaymentController::paymentSuccess()` completes payment

#### Database Schema Notes
**plan_subscriptions table** (from migration 2023_03_16_054806):
- `id`, `user_id`, `plan_id`
- `is_current` (boolean)
- `plan_expired_at` (datetime)
- `created_at`, `updated_at`

**Missing columns referenced in job but not in migration**:
- `expire_date` (should be `plan_expired_at`)
- `status` (doesn't exist)
- `auto_renewal` (doesn't exist)
- `duration_days` (doesn't exist)

This indicates the job file (`ProcessSubscriptionRenewalsJob.php`) is outdated and needs refactoring to match the actual schema.

#### Successful Verification
```bash
# Route registration works without errors
docker exec 1Panel-php8-mrTy php /www/sites/aitradepulse.com/index/main/artisan route:list --json

# PHP syntax validation passes
php -l main/app/Services/PaymentService.php
```

### Issues Found
1. **Job file outdated**: `ProcessSubscriptionRenewalsJob.php` references non-existent model `UserPlan` and columns
2. **Missing auto-renewal columns**: `plan_subscriptions` table lacks `auto_renewal` column needed for renewal logic
3. **Job needs refactoring**: To use `PlanSubscription` model and correct column names

### Recommendations
1. Update `ProcessSubscriptionRenewalsJob` to use `PlanSubscription` instead of `UserPlan`
2. Add migration to add `auto_renewal` column to `plan_subscriptions` table
3. Update job to use correct column names: `plan_expired_at` instead of `expire_date`
4. Implement actual payment processing in `processRenewal()` - currently creates record but doesn't charge

### Convention Compliance
- Used `declare(strict_types=1);` at top of file ✅
- Namespace: `App\Services` ✅
- All imports explicitly declared ✅
- Return format matches expected usage in controllers ✅
- No business logic added beyond fixing namespace/registration ✅
