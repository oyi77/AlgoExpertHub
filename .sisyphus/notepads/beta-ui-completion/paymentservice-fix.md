# PaymentService Fix - Additional Issues Found

## [2026-01-17] PaymentService Syntax Error Fix

### Issue Discovered
Running `docker exec 1Panel-php8-mrTy php artisan route:list --name=user.` failed with:

```
ParseError: syntax error, unexpected token "as", expecting "," or ";" at PaymentService.php:6
```

### Root Causes

#### 1. Invalid Import Syntax (Line 6)
```php
// BROKEN
use Addons\MultiChannelSignalAddon\App\Services as PaymentService as AddonsPaymentService;
```

This is invalid PHP syntax. You cannot alias a namespace then alias it again in the same statement.

#### 2. Duplicate Import (Line 14)
```php
use Illuminate\Support\Str;  // Line 7 - valid
use Illuminate\Support\Str;  // Line 14 - duplicate
```

#### 3. Broken processRenewal() Implementation (Lines 123-126)
```php
// BROKEN
public function processRenewal(UserPlan $plan): void
{
    $result = $paymentService->processRenewal($plan);
}
```

Issues:
- Uses `UserPlan` model which doesn't exist (should be `PlanSubscription`)
- References undefined `$paymentService` variable
- No actual implementation - just calls non-existent service

### Fixes Applied

#### 1. Fixed Imports (Lines 6, 14)
```php
// FIXED
use App\Helpers\Helper\Helper;
use Illuminate\Support\Str;
use App\Models\Gateway;
use App\Models\Deposit;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanSubscription;  // Changed from UserPlan
use App\Models\Wallet;
```

Changes:
- Removed invalid line 6 import completely (not used)
- Removed duplicate `use Illuminate\Support\Str;` on line 14
- Changed `use App\Models\UserPlan;` to `use App\Models\PlanSubscription;`

#### 2. Implemented processRenewal() Method (Lines 123-172)
```php
public function processRenewal(PlanSubscription $planSubscription): array
{
    // Validate subscription is active
    if (!$planSubscription->is_current) {
        return [
            'success' => false,
            'message' => 'Subscription is not active'
        ];
    }

    // Load plan data
    $plan = Plan::find($planSubscription->plan_id);
    if (!$plan) {
        return [
            'success' => false,
            'message' => 'Plan not found'
        ];
    }

    // Get default gateway for renewal
    $gateway = Gateway::where('status', 1)->first();
    if (!$gateway) {
        return [
            'success' => false,
            'message' => 'No active gateway available'
        ];
    }

    // Calculate expiry date based on plan type
    $planExpiredAt = $plan->plan_type === 'limited'
        ? now()->addDays($plan->duration)
        : now()->addYear(50);

    // Create renewal payment record
    $payment = Payment::create([
        'plan_id' => $plan->id,
        'gateway_id' => $gateway->id,
        'user_id' => $planSubscription->user_id,
        'trx' => Str::upper(Str::random(16)),
        'amount' => $plan->price,
        'rate' => $gateway->rate,
        'charge' => $gateway->charge,
        'total' => ($plan->price * $gateway->rate) + $gateway->charge,
        'status' => 0, // Pending
        'type' => $gateway->type,
        'plan_expired_at' => $planExpiredAt
    ]);

    return [
        'success' => true,
        'message' => 'Renewal payment created successfully',
        'payment_id' => $payment->id,
        'trx' => $payment->trx
    ];
}
```

### Verification

Route registration now works:
```bash
docker exec 1Panel-php8-mrTy php /www/sites/aitradepulse.com/index/main/artisan route:list --name=user.
# Returns list of routes without errors
```

### Notes

1. **Job Still Outdated**: `ProcessSubscriptionRenewalsJob` still references `UserPlan` model and columns that don't exist
   - This is a separate issue not in scope of Task 1
   - Job needs refactoring to use `PlanSubscription` model
   - Job references columns: `expire_date`, `status`, `auto_renewal`, `duration_days` (all don't exist)

2. **processRenewal() Contract**: Changed return type from `void` to `array` to match job's expectations
   - Job expects: `$result['success']`, `$result['message']`

3. **Model Names**:
   - ✅ `PlanSubscription` (correct - exists in codebase)
   - ❌ `UserPlan` (incorrect - does not exist)

### Convention Compliance
- Fixed syntax error ✅
- Fixed model reference (UserPlan → PlanSubscription) ✅
- Implemented processRenewal() with proper logic ✅
- Followed existing payment creation pattern ✅
- Return format matches job expectations ✅
