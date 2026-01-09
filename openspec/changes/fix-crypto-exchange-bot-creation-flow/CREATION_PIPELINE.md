# Trading Bot Creation Pipeline Documentation

## Current Flow

### 1. Controller Entry Point
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Controllers/User/TradingBotController.php`
**Method**: `store(Request $request)`

**Current Validation**:
- Basic validation via `$request->validate()` (lines 121-139)
- Validates `exchange_connection_id` exists in `execution_connections` table
- Does NOT validate connection status or type compatibility
- Does NOT validate credentials are present/valid
- Does NOT encrypt credentials (credentials are stored in ExchangeConnection, not TradingBot)

**Flow**:
1. Validates request data
2. Auto-fills `data_connection_id` if MARKET_STREAM_BASED
3. Processes streaming symbols/timeframes
4. Calls `$this->botService->create($validated)` (line 173)

### 2. Service Layer
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Services/TradingBotService.php`
**Method**: `create(array $data): TradingBot`

**Current Validation**:
- Calls `validateRelationships($data)` (line 37)
- Validates exchange connection exists and is active (lines 314-321)
- Validates connection status === 'active' (line 319)
- Does NOT validate credentials are encrypted
- Does NOT normalize credential payload
- Does NOT check for crypto-specific requirements (passphrase, etc.)

**Flow**:
1. Validates relationships (exchange connection, preset, etc.)
2. Sets ownership (user_id or admin_id)
3. Creates bot in transaction
4. Logs creation (line 50-55) - **ISSUE: May log credentials if passed**

### 3. Exchange Connection Model
**File**: `main/addons/trading-management-addon/Modules/ExchangeConnection/Models/ExchangeConnection.php`

**Credentials Handling**:
- Uses `HasEncryptedCredentials` trait (line 22)
- Credentials automatically encrypted on save via trait
- Credentials decrypted on read via trait
- No validation of credential structure

**Current Issues**:
1. **Validation Gaps**:
   - Controller doesn't validate connection status before allowing bot creation
   - No validation for exchange-specific requirements (passphrase for OKX, Kucoin, etc.)
   - No validation that credentials are present in connection

2. **Security Issues**:
   - Logging may expose credentials (if credentials passed in data array)
   - No redaction helper for logs
   - No validation that credentials are encrypted before persistence

3. **UX Issues**:
   - No guidance when connection is inactive
   - No exchange-specific field hints (passphrase requirement)
   - No connection health badges
   - No inline connection creation option

4. **Error Handling**:
   - Generic error messages
   - No actionable guidance for credential failures
   - Silent failures in some cases

## Required Payloads for Crypto Exchanges

### Binance
```php
[
    'api_key' => 'string',
    'api_secret' => 'string',
    // No passphrase required
]
```

### Bybit
```php
[
    'api_key' => 'string',
    'api_secret' => 'string',
    // No passphrase required
]
```

### OKX
```php
[
    'api_key' => 'string',
    'api_secret' => 'string',
    'api_passphrase' => 'string', // REQUIRED
]
```

### Kucoin
```php
[
    'api_key' => 'string',
    'api_secret' => 'string',
    'api_passphrase' => 'string', // REQUIRED
]
```

### Coinbase Pro
```php
[
    'api_key' => 'string',
    'api_secret' => 'string',
    'api_passphrase' => 'string', // REQUIRED
]
```

## Pain Points Identified

1. **Validation Gaps**:
   - Missing passphrase validation for OKX/Kucoin/Coinbase Pro
   - No check that connection status is 'active'
   - No validation that connection type matches bot requirements

2. **Missing Encryption**:
   - Credentials are encrypted in ExchangeConnection model (via trait)
   - But no validation that encryption happened
   - No check for credential completeness before bot creation

3. **Broken Redirects**:
   - No redirect guidance when connection is missing
   - No inline connection creation flow
   - No clear error messages for credential failures

4. **Logging Issues**:
   - Potential credential leakage in logs
   - No redaction helper
   - Logs may contain sensitive data

