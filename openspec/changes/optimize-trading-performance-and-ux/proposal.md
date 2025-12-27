# Change: Optimize Trading Performance and UX

## Why
The current trading system suffers from excessive logging (300MB+ logs/day) and high memory usage (160MB+ per connection test), impacting stability and storage. Additionally, key user-facing features on the dashboard and exchange connection forms are either missing formatting or have confusing UX.

## What Changes
- **Performance**: Disable redundant database query logging in production by default.
- **Performance**: Reduce log verbosity in MetaApi services (info -> debug).
- **Performance**: Implement a runtime adapter cache in `ExchangeConnectionService` to prevent redundant object creation and SDK connections.
- **UX**: Format signal prices on the dashboard with appropriate decimals based on asset class.
- **UX**: Add a "Status" badge to the dashboard signals table using automated outcome tracking.
- **UX**: Ensure API credentials fields are visible and well-labeled in the exchange connection form.
- **Feature**: Implement the missing user notification in `RunBacktestJob`.

## Impact
- Affected specs: `trading-bot`
- Affected code: `AppServiceProvider.php`, `MetaApiAdapter.php`, `ExchangeConnectionService.php`, `dashboard.blade.php`, `create.blade.php`, `RunBacktestJob.php`.
