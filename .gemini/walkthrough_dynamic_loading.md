# Walkthrough - User Panel Dynamic Exchange Loading

## Problem
The user reported that the provider list in the user panel was not loading dynamically, unlike the admin panel. Additionally, a previous bug caused the credentials form to be hidden for Forex Brokers.

## Solution Implemented

### 1. Added Backend Route
Added a new route `user.exchange-connections.ccxt-exchanges` in `web.php` that exposes the `CcxtExchangeService::getCryptoExchanges()` method to the user panel. This allows the frontend to fetch the up-to-date list of supported exchanges.

**File**: `routes/web.php`
```php
Route::get('/ccxt-exchanges', function () {
    // ... logic to return JSON of exchanges ...
})->name('ccxt-exchanges');
```

### 2. Implemented Dynamic Frontend Logic
Updated the `create.blade.php` view for the user panel to include the same dynamic loading logic as the admin panel.

**File**: `resources/views/user/exchange-connections/create.blade.php`
- Added `loadCcxtExchanges()` function to fetch data from the new route.
- Added `populateCryptoProviders()` to render the dropdown with popular/other sections.
- Updated `updateFormBasedOnExchangeType()` to trigger the load when "Crypto Exchange" is selected.
- Updated `updateFormBasedOnProvider()` to handle dynamic passphrase requirements (e.g., OKX needs passphrase, Binance converts doesn't).

## Verification Steps

1. **Navigate** to User Panel -> Trading Configuration -> Data Connections -> **Create Connection**.
2. **Select Connection Type**: "Both Data & Execution" (or any other).
3. **Select Exchange Type**: "Crypto Exchange (CCXT)".
4. **Observe**: The Provider dropdown should initially show "Loading exchanges..." and then populate with a sorted list of exchanges (Binance, Coinbase, etc. at the top).
5. **Select Exchange**: Choose "OKX".
6. **Verify**: The **API Passphrase** field should be marked as **Required (*)**.
7. **Select Exchange**: Choose "Binance".
8. **Verify**: The **API Passphrase** field should be marked as **(Optional)**.
9. **Select Exchange Type**: "Forex Broker".
10. **Verify**: The Provider list switches to Forex providers (MetaApi, etc.) and credentials fields are correctly shown/hidden based on selection (MetaApi -> Account ID, mtapi -> API Key/Secret).

## Artifacts Updated
- `routes/web.php`
- `main/addons/trading-management-addon/resources/views/user/exchange-connections/create.blade.php`
