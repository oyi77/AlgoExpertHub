# Trading Preset Addon - Integration Guide

## Overview

This guide explains how to integrate the Trading Preset Addon with the Execution Engine, Copy Trading, and Trading Bots.

## Integration with SignalExecutionService

### Option 1: Direct Integration (Recommended)

Modify `SignalExecutionService::executeSignal()` to use preset services:

```php
use Addons\TradingPresetAddon\App\Services\PresetExecutionService;
use Addons\TradingPresetAddon\App\Services\SignalExecutionEnhancer;

class SignalExecutionService
{
    protected PresetExecutionService $presetExecutionService;
    protected SignalExecutionEnhancer $enhancer;

    public function __construct(
        ConnectionService $connectionService,
        NotificationService $notificationService,
        PresetExecutionService $presetExecutionService,
        SignalExecutionEnhancer $enhancer
    ) {
        // ... existing code
        $this->presetExecutionService = $presetExecutionService;
        $this->enhancer = $enhancer;
    }

    public function executeSignal(Signal $signal, int $connectionId, array $options = []): array
    {
        $connection = ExecutionConnection::findOrFail($connectionId);

        // Enhanced canExecute check
        $canExecute = $this->canExecute($signal, $connectionId);
        $canExecute = $this->enhancer->enhanceCanExecute($signal, $connection, $canExecute);
        
        if (!$canExecute['can_execute']) {
            return [
                'success' => false,
                'message' => $canExecute['reason'],
            ];
        }

        // Enhanced position size calculation
        $baseQuantity = $this->calculatePositionSize($signal, $connection, $options);
        $quantity = $this->enhancer->enhancePositionSize($signal, $connection, $options, $baseQuantity);

        // Enhanced order options
        $orderOptions = [];
        if ($signal->sl > 0) {
            $orderOptions['sl_price'] = $signal->sl;
        }
        if ($signal->tp > 0) {
            $orderOptions['tp_price'] = $signal->tp;
        }
        $orderOptions = $this->enhancer->enhanceOrderOptions($signal, $connection, $orderOptions);

        // ... rest of execution logic

        // After position creation, enhance with multi-TP
        if ($position) {
            $this->enhancer->enhancePositionCreation($position, $signal, $connection);
        }
    }

    protected function calculatePositionSize(Signal $signal, ExecutionConnection $connection, array $options): float
    {
        // Enhanced calculation
        $baseQuantity = /* existing calculation */;
        return $this->enhancer->enhancePositionSize($signal, $connection, $options, $baseQuantity);
    }
}
```

### Option 2: Event-Based Integration

Use Laravel events to enhance execution:

```php
// In EventServiceProvider or AddonServiceProvider
Event::listen('signal.executing', function ($signal, $connection) {
    $enhancer = app(SignalExecutionEnhancer::class);
    // Enhance execution
});
```

## Integration with PositionService

Add preset monitoring to position updates:

```php
use Addons\TradingPresetAddon\App\Services\PresetPositionService;

class PositionService
{
    protected PresetPositionService $presetPositionService;

    public function monitorPositions(): void
    {
        $openPositions = ExecutionPosition::open()->get();

        foreach ($openPositions as $position) {
            // Existing monitoring
            $this->updatePosition($position);
            $this->checkSlTp($position);

            // Preset-based monitoring
            $this->presetPositionService->monitorPositions();
        }
    }
}
```

Or call preset monitoring separately:

```php
// In scheduled command or queue job
$presetPositionService = app(PresetPositionService::class);
$presetPositionService->monitorPositions();
```

## Integration with Copy Trading

Modify `TradeCopyService` to use presets:

```php
use Addons\TradingPresetAddon\App\Services\PresetResolverService;
use Addons\TradingPresetAddon\App\Services\PresetApplicatorService;

class TradeCopyService
{
    protected PresetResolverService $presetResolver;
    protected PresetApplicatorService $presetApplicator;

    protected function calculateCopiedQuantity(
        ExecutionPosition $traderPosition,
        CopyTradingSubscription $subscription,
        ExecutionConnection $followerConnection
    ): array {
        // Get preset for subscription
        $preset = $this->presetResolver->resolveForCopyTrading(
            $subscription,
            $followerConnection,
            $subscription->follower
        );

        if ($preset) {
            $config = $this->presetApplicator->applyAsDTO($preset, $followerConnection->settings ?? []);
            
            // Use preset position sizing
            $adapter = $this->connectionService->getAdapter($followerConnection);
            $balance = $adapter->getBalance();
            $equity = $balance['balance'] ?? 0;
            
            $quantity = $this->presetApplicator->calculatePositionSize(
                $config,
                $equity,
                $traderPosition->entry_price,
                $traderPosition->sl_price
            );

            return [
                'quantity' => $quantity,
                'risk_multiplier' => null,
                'details' => ['preset_id' => $preset->id, 'preset_name' => $preset->name],
            ];
        }

        // Fallback to existing logic
        return $this->calculateQuantityLegacy($traderPosition, $subscription, $followerConnection);
    }
}
```

## Scheduled Commands

Add scheduled commands for preset monitoring:

```php
// In app/Console/Kernel.php or addon service provider
protected function schedule(Schedule $schedule)
{
    // Monitor positions with preset features (every minute)
    $schedule->call(function () {
        $presetPositionService = app(\Addons\TradingPresetAddon\App\Services\PresetPositionService::class);
        $presetPositionService->monitorPositions();
    })->everyMinute();
}
```

## Testing Integration

1. **Test preset resolution**: Verify correct preset is selected
2. **Test position sizing**: Verify RISK_PERCENT and FIXED modes work
3. **Test multi-TP**: Verify multiple TP orders are created
4. **Test break-even**: Verify SL moves to BE when trigger RR reached
5. **Test trailing stop**: Verify SL trails price
6. **Test weekly target**: Verify trading stops when target reached
7. **Test trading schedule**: Verify trading only during allowed hours

## Notes

- All integrations are backward compatible
- If preset is not found, system falls back to existing logic
- Preset features are optional and can be enabled/disabled per preset
- Weekly target tracking is per connection, not per user

