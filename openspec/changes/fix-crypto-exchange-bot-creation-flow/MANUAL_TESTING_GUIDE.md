# Manual Testing Guide: Crypto Exchange Bot Creation Flow

**Version**: 1.0  
**Date**: 2025-12-22  
**Purpose**: Manual smoke tests for Binance and Bybit bot creation paths

---

## Prerequisites

1. **Test Environment Setup**
   - Access to user account with active subscription
   - Test API credentials for Binance and Bybit (testnet recommended)
   - Browser with developer tools enabled

2. **Test Data**
   - Valid Binance API Key + Secret
   - Valid Bybit API Key + Secret
   - Invalid/expired API credentials (for error testing)
   - At least one Trading Preset created

---

## Test Case 1: Binance Bot Creation (New Connection)

### Objective
Verify that a user can create a Binance trading bot using the inline connection creation modal.

### Steps

1. **Navigate to Bot Creation**
   - Log in as a user
   - Go to **Trading → Trading Bots**
   - Click **Create Bot**

2. **Verify Form Loads**
   - ✅ Progress stepper shows 7 steps
   - ✅ Step 1 (Basic Info) is active
   - ✅ Exchange connection dropdown is visible

3. **Create Connection Inline**
   - Click **"+"** button next to exchange connection dropdown
   - Modal opens: "Create New Exchange Connection"
   - Fill in:
     - **Connection Name**: "Test Binance Connection"
     - **Exchange Type**: Select "Cryptocurrency Exchange"
     - **Exchange/Provider**: Select "BINANCE"
     - **Connection Purpose**: Select "Both (Data + Execution)"
     - **API Key**: Enter valid Binance API key
     - **API Secret**: Enter valid Binance API secret
     - **API Passphrase**: Field should be hidden (not required for Binance)
   - Click **"Create & Test Connection"**

4. **Verify Connection Created**
   - ✅ Modal closes
   - ✅ Page refreshes (or connection appears in dropdown)
   - ✅ New connection is selected in dropdown
   - ✅ Connection health badge shows "Testing" or "Active"
   - ✅ Success message displayed

5. **Complete Bot Creation**
   - Fill in **Bot Name**: "My Binance Bot"
   - Select **Trading Preset**: Choose a preset
   - Select **Trading Mode**: "Signal-Based"
   - Enable **Paper Trading**: Checked
   - Click **"Create Trading Bot"**

6. **Verify Bot Created**
   - ✅ Redirected to bot list or bot detail page
   - ✅ Success message: "Trading bot created successfully"
   - ✅ Bot appears in bot list
   - ✅ Bot status is "Active" or "Ready"

### Expected Results
- ✅ Connection created successfully via inline modal
- ✅ Connection appears in dropdown immediately
- ✅ Bot created with new connection
- ✅ No validation errors
- ✅ No orphaned records in database

---

## Test Case 2: Bybit Bot Creation (Existing Connection)

### Objective
Verify that a user can create a Bybit trading bot using an existing, active connection.

### Prerequisites
- Create a Bybit connection beforehand (via Exchange Connections page)
- Ensure connection status is "active" and `is_active = true`

### Steps

1. **Navigate to Bot Creation**
   - Log in as a user
   - Go to **Trading → Trading Bots**
   - Click **Create Bot**

2. **Select Existing Connection**
   - In **Exchange Connection** dropdown, select existing Bybit connection
   - ✅ Connection health badge shows "Active & Ready" (green badge)
   - ✅ Requirements info shows: "Bybit requires API Key and Secret. Enable trading permissions."

3. **Complete Bot Form**
   - Fill in **Bot Name**: "My Bybit Bot"
   - Select **Trading Preset**: Choose a preset
   - Select **Trading Mode**: "Signal-Based"
   - Enable **Paper Trading**: Checked
   - Click **"Create Trading Bot"**

4. **Verify Bot Created**
   - ✅ Redirected to bot list
   - ✅ Success message displayed
   - ✅ Bot appears in list with correct connection

### Expected Results
- ✅ Existing connection selected successfully
- ✅ Health badge displays correctly
- ✅ Bot created with existing connection
- ✅ No validation errors

---

## Test Case 3: Invalid Credentials Path (Error Handling)

### Objective
Verify that invalid credentials return actionable error messages and no bot is persisted.

### Steps

1. **Navigate to Bot Creation**
   - Log in as a user
   - Go to **Trading → Trading Bots**
   - Click **Create Bot**

2. **Create Connection with Invalid Credentials**
   - Click **"+"** button to open connection modal
   - Fill in:
     - **Connection Name**: "Invalid Binance"
     - **Exchange Type**: "Cryptocurrency Exchange"
     - **Exchange**: "BINANCE"
     - **API Key**: "invalid_key_12345"
     - **API Secret**: "invalid_secret_67890"
   - Click **"Create & Test Connection"**

3. **Verify Error Handling**
   - ✅ Modal shows error message
   - ✅ Error is specific: "Failed to connect: [specific error from exchange]"
   - ✅ Connection is NOT created
   - ✅ No bot record created
   - ✅ User can correct credentials and retry

4. **Attempt Bot Creation with Inactive Connection**
   - Create a connection but don't activate it (or deactivate existing)
   - Try to select inactive connection in bot creation form
   - ✅ Validation error: "The selected exchange connection is invalid or not active"
   - ✅ Form submission prevented
   - ✅ No bot record created

### Expected Results
- ✅ Clear, actionable error messages
- ✅ No orphaned bot records
- ✅ No orphaned connection records
- ✅ User can retry after fixing credentials

---

## Test Case 4: OKX Bot Creation (Passphrase Required)

### Objective
Verify that OKX bot creation requires passphrase and validates correctly.

### Steps

1. **Create OKX Connection**
   - Use inline modal or existing connection page
   - Fill in:
     - **Exchange**: "OKX"
     - **API Key**: Valid OKX key
     - **API Secret**: Valid OKX secret
     - **API Passphrase**: Valid OKX passphrase
   - Create connection

2. **Create Bot with OKX Connection**
   - Select OKX connection in bot form
   - ✅ Requirements info shows: "OKX requires API Key, Secret, and Passphrase. Create API key with trading permissions."
   - Complete bot form
   - Submit

3. **Verify Bot Created**
   - ✅ Bot created successfully
   - ✅ Connection credentials validated

4. **Test Missing Passphrase**
   - Create OKX connection without passphrase
   - Try to create bot
   - ✅ Validation error: "Connection missing required credentials (passphrase)"
   - ✅ Bot creation prevented

### Expected Results
- ✅ Passphrase field shown for OKX
- ✅ Passphrase validation works
- ✅ Clear error if passphrase missing

---

## Test Case 5: Connection Health Badges

### Objective
Verify that connection health badges display correctly for different connection states.

### Steps

1. **Test Active Connection**
   - Select connection with `status = 'active'` and `is_active = true`
   - ✅ Badge shows: "Connection Active & Ready" (green)

2. **Test Inactive Connection**
   - Select connection with `is_active = false`
   - ✅ Badge shows: "Connection Inactive" (gray)
   - ✅ Validation error if trying to submit

3. **Test Error Connection**
   - Select connection with `status = 'error'`
   - ✅ Badge shows: "Connection Has Errors" (red)
   - ✅ Validation error if trying to submit

4. **Test Testing Connection**
   - Select connection with `status = 'testing'`
   - ✅ Badge shows: "Connection Testing" (yellow)

### Expected Results
- ✅ All health states display correct badges
- ✅ Badge colors match status
- ✅ Validation prevents using unhealthy connections

---

## Test Case 6: Progress Stepper

### Objective
Verify that progress stepper updates as form is filled.

### Steps

1. **Initial State**
   - Open bot creation form
   - ✅ Step 1 (Basic Info) is active (blue)
   - ✅ Steps 2-7 are inactive (gray)

2. **Fill Basic Info**
   - Enter bot name
   - ✅ Step 1 becomes completed (green)
   - ✅ Step 2 (Connection) becomes active (blue)

3. **Select Connection**
   - Select exchange connection
   - ✅ Step 2 becomes completed (green)
   - ✅ Step 3 (Preset) becomes active (blue)

4. **Complete All Required Fields**
   - Select preset
   - Select trading mode
   - Enable paper trading
   - ✅ All steps show as completed (green)
   - ✅ Form ready to submit

### Expected Results
- ✅ Stepper updates in real-time
- ✅ Visual feedback matches form completion
- ✅ Helps users understand progress

---

## Test Case 7: Dynamic Field Rendering

### Objective
Verify that exchange-specific fields (passphrase) show/hide correctly.

### Steps

1. **Binance Connection**
   - Select Binance connection
   - ✅ Passphrase field NOT shown
   - ✅ Requirements: "Binance requires API Key and Secret. Enable spot trading permissions."

2. **OKX Connection**
   - Select OKX connection
   - ✅ Passphrase field IS shown
   - ✅ Passphrase marked as required
   - ✅ Requirements: "OKX requires API Key, Secret, and Passphrase..."

3. **KuCoin Connection**
   - Select KuCoin connection
   - ✅ Passphrase field IS shown
   - ✅ Requirements show KuCoin-specific help

### Expected Results
- ✅ Fields show/hide based on exchange
- ✅ Help text is exchange-specific
- ✅ Validation matches field visibility

---

## Test Case 8: Form Validation

### Objective
Verify all validation rules work correctly.

### Steps

1. **Empty Form Submission**
   - Try to submit without filling required fields
   - ✅ Validation errors shown for:
     - Bot name (required)
     - Exchange connection (required)
     - Trading preset (required)
     - Trading mode (required)

2. **Invalid Connection Selection**
   - Try to select connection owned by another user
   - ✅ Validation error: "The selected exchange connection is invalid or not active"

3. **Inactive Connection**
   - Try to select inactive connection
   - ✅ Validation error: "The selected exchange connection is invalid or not active"

4. **Missing Credentials**
   - Try to create bot with connection missing API key
   - ✅ Validation error: "Connection missing required credentials (api_key)"

### Expected Results
- ✅ All validation rules enforced
- ✅ Clear error messages
- ✅ No invalid data persisted

---

## Test Case 9: Inline Connection Creation Modal

### Objective
Verify inline connection creation works without page reload.

### Steps

1. **Open Modal**
   - Click "+" button next to connection dropdown
   - ✅ Modal opens smoothly
   - ✅ Form fields visible

2. **Fill Connection Form**
   - Select exchange type
   - ✅ Exchange list populates
   - ✅ Credential fields show/hide based on exchange
   - ✅ Passphrase field shows for OKX/KuCoin

3. **Submit Connection**
   - Fill valid credentials
   - Click "Create & Test Connection"
   - ✅ Loading state shown
   - ✅ Connection created
   - ✅ Modal closes
   - ✅ Page refreshes with new connection

4. **Error Handling**
   - Submit with invalid credentials
   - ✅ Error message in modal
   - ✅ Modal stays open
   - ✅ Form not reset
   - ✅ User can correct and retry

### Expected Results
- ✅ Modal works smoothly
- ✅ Connection created successfully
- ✅ Dropdown refreshes
- ✅ Error handling works

---

## Test Case 10: Edge Cases

### Objective
Test edge cases and error scenarios.

### Steps

1. **No Connections Available**
   - User with no connections
   - ✅ Alert shown: "No exchange connections available"
   - ✅ "Create New Exchange Connection" button visible
   - ✅ Inline modal works

2. **Connection Deleted During Form Fill**
   - Select connection
   - Delete connection in another tab
   - Try to submit bot
   - ✅ Validation error on submission
   - ✅ User prompted to select new connection

3. **Network Error During Connection Creation**
   - Disconnect network
   - Try to create connection via modal
   - ✅ Error message shown
   - ✅ Modal stays open
   - ✅ User can retry

### Expected Results
- ✅ All edge cases handled gracefully
- ✅ No crashes or broken states
- ✅ Clear error messages

---

## Test Checklist Summary

### Happy Path Tests
- [ ] Binance bot creation (new connection via modal)
- [ ] Bybit bot creation (existing connection)
- [ ] OKX bot creation (with passphrase)
- [ ] Progress stepper updates correctly
- [ ] Health badges display correctly

### Error Path Tests
- [ ] Invalid credentials return actionable errors
- [ ] Inactive connection blocked
- [ ] Missing credentials blocked
- [ ] Missing passphrase for OKX/KuCoin blocked
- [ ] No orphaned records created

### UI/UX Tests
- [ ] Dynamic fields show/hide correctly
- [ ] Inline modal works smoothly
- [ ] Connection dropdown refreshes
- [ ] Help text is exchange-specific
- [ ] Progress feedback is accurate

---

## Reporting Issues

When reporting issues, include:
1. **Test Case Number**: Which test case failed
2. **Steps to Reproduce**: Exact steps taken
3. **Expected Result**: What should have happened
4. **Actual Result**: What actually happened
5. **Screenshots**: If applicable
6. **Browser/OS**: Browser version and OS
7. **Console Errors**: Any JavaScript errors in console
8. **Network Logs**: Failed API requests (redact credentials)

---

## Notes

- All tests should be performed in a test/staging environment
- Use testnet API credentials when possible
- Never use production API keys in testing
- Document any deviations from expected behavior
- Report bugs immediately to development team

---

**Last Updated**: 2025-12-22  
**Tested By**: [Tester Name]  
**Status**: Ready for Testing

