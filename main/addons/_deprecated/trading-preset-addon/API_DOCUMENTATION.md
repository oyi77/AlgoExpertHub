# Trading Preset API Documentation

API documentation for developers integrating with Trading Preset addon.

## Table of Contents

1. [Services](#services)
2. [Models](#models)
3. [DTOs](#dtos)
4. [Usage Examples](#usage-examples)

## Services

### PresetService

Main service for managing trading presets.

```php
use Addons\TradingPresetAddon\App\Services\PresetService;

$presetService = app(PresetService::class);
```

#### Methods

**create(array $data, User $user): TradingPreset**
- Create a new preset
- Returns: TradingPreset instance

**update(TradingPreset $preset, array $data, User $user): TradingPreset**
- Update existing preset
- Returns: Updated TradingPreset instance

**delete(TradingPreset $preset, User $user): bool**
- Delete preset (soft delete)
- Returns: true on success

**clone(TradingPreset $preset, User $user, ?string $newName = null): TradingPreset**
- Clone preset to user's account
- Returns: New TradingPreset instance

**getUserPresets(User $user, array $filters = []): Collection**
- Get user's presets
- Returns: Collection of TradingPreset

**getPublicPresets(array $filters = []): Collection**
- Get public presets
- Returns: Collection of TradingPreset

**getDefaultPresets(): Collection**
- Get system default presets
- Returns: Collection of TradingPreset

### PresetResolverService

Resolves which preset to use for a given context.

```php
use Addons\TradingPresetAddon\App\Services\PresetResolverService;

$resolver = app(PresetResolverService::class);
```

#### Methods

**resolve(array $context): ?TradingPreset**
- Resolve preset from context
- Context keys: bot, subscription, connection, user, signal
- Returns: TradingPreset or null

**resolveForBot($bot, $connection, ?User $user = null): ?TradingPreset**
- Resolve preset for bot execution
- Returns: TradingPreset or null

**resolveForCopyTrading($subscription, $connection, ?User $user = null): ?TradingPreset**
- Resolve preset for copy trading
- Returns: TradingPreset or null

**resolveForSignal($connection, ?User $user = null, $signal = null): ?TradingPreset**
- Resolve preset for signal execution
- Returns: TradingPreset or null

### PresetApplicatorService

Applies preset configuration to execution context.

```php
use Addons\TradingPresetAddon\App\Services\PresetApplicatorService;

$applicator = app(PresetApplicatorService::class);
```

#### Methods

**applyAsDTO(TradingPreset $preset, array $connectionSettings = []): PresetConfigurationDTO**
- Apply preset and return as DTO
- Returns: PresetConfigurationDTO

**calculatePositionSize($config, float $equity, float $entryPrice, ?float $slPrice = null, ?ExecutionConnection $connection = null): float**
- Calculate position size based on preset
- Returns: Position size (quantity)

**calculateSlPrice($config, float $entryPrice, string $direction, ?float $structureSlPrice = null): ?float**
- Calculate stop loss price
- Returns: SL price or null

**calculateTpPrices($config, float $entryPrice, float $slPrice, string $direction): array**
- Calculate take profit prices
- Returns: Array with tp1_price, tp2_price, tp3_price

### PresetExecutionService

Service for executing trades with preset configurations.

```php
use Addons\TradingPresetAddon\App\Services\PresetExecutionService;

$executionService = app(PresetExecutionService::class);
```

#### Methods

**getPresetConfig(ExecutionConnection $connection, ?Signal $signal = null): ?PresetConfigurationDTO**
- Get preset configuration for execution
- Returns: PresetConfigurationDTO or null

**checkTradingSchedule(PresetConfigurationDTO $config): array**
- Check if trading is allowed based on schedule
- Returns: ['allowed' => bool, 'reason' => string|null]

**checkWeeklyTarget(ExecutionConnection $connection, PresetConfigurationDTO $config): array**
- Check if weekly target is reached
- Returns: ['allowed' => bool, 'reason' => string|null]

**checkMaxPositions(ExecutionConnection $connection, PresetConfigurationDTO $config, ?string $symbol = null): array**
- Check if max positions limit is reached
- Returns: ['allowed' => bool, 'reason' => string|null]

**calculatePositionSize(PresetConfigurationDTO $config, float $equity, float $entryPrice, ?float $slPrice = null): float**
- Calculate position size
- Returns: Position size (quantity)

**calculateSlPrice(PresetConfigurationDTO $config, float $entryPrice, string $direction, ?float $structureSlPrice = null): ?float**
- Calculate SL price
- Returns: SL price or null

**calculateTpPrices(PresetConfigurationDTO $config, float $entryPrice, float $slPrice, string $direction): array**
- Calculate TP prices
- Returns: Array with tp prices

### AdvancedTradingService

Service for advanced trading features.

```php
use Addons\TradingPresetAddon\App\Services\AdvancedTradingService;

$advancedService = app(AdvancedTradingService::class);
```

#### Methods

**calculateDynamicEquity(PresetConfigurationDTO $config, ExecutionConnection $connection, float $baseEquity): float**
- Calculate dynamic equity
- Returns: Adjusted equity

**calculateATR(array $candles, int $period = 14): ?float**
- Calculate Average True Range
- Returns: ATR value or null

**calculateATRTrailingStop(float $currentPrice, float $atr, float $atrMultiplier, string $direction, ?float $currentSl = null): ?float**
- Calculate ATR-based trailing stop
- Returns: New SL price or null

**calculateChandelierStop(array $candles, int $lookbackPeriod, float $atrMultiplier, string $direction): ?float**
- Calculate Chandelier stop loss
- Returns: Chandelier SL price or null

**getPriceHistory(ExecutionConnection $connection, string $symbol, string $timeframe, int $limit = 50): array**
- Get price history/candles
- Returns: Array of candles

**checkCandleCloseLogic(PresetConfigurationDTO $config, \DateTime $positionOpenedAt, string $timeframe): array**
- Check if position should close on candle close
- Returns: ['should_close' => bool, 'reason' => string|null]

## Models

### TradingPreset

Eloquent model for trading presets.

```php
use Addons\TradingPresetAddon\App\Models\TradingPreset;

$preset = TradingPreset::find($id);
```

#### Relationships

- `user()`: BelongsTo User (creator)
- `executionConnections()`: HasMany ExecutionConnection
- `copyTradingSubscriptions()`: HasMany CopyTradingSubscription
- `usersWithDefault()`: HasMany User (users with this as default)

#### Scopes

- `enabled()`: Only enabled presets
- `public()`: Only public presets
- `defaultTemplates()`: Only default templates
- `byUser(User $user)`: User's presets
- `clonable()`: Only clonable presets

#### Methods

- `isPublic(): bool`
- `isClonable(): bool`
- `isDefaultTemplate(): bool`
- `canBeEditedBy(User $user): bool`
- `canBeDeletedBy(User $user): bool`

## DTOs

### PresetConfigurationDTO

Data Transfer Object for preset configuration.

```php
use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;

$dto = PresetConfigurationDTO::fromArray($data);
$array = $dto->toArray();
```

#### Properties

All preset configuration fields are available as public properties:
- `$position_size_mode`
- `$risk_per_trade_pct`
- `$sl_mode`
- `$tp_mode`
- `$be_enabled`
- `$ts_enabled`
- etc.

## Usage Examples

### Example 1: Create and Apply Preset

```php
use Addons\TradingPresetAddon\App\Services\PresetService;
use Addons\TradingPresetAddon\App\Services\PresetApplicatorService;

$presetService = app(PresetService::class);
$applicator = app(PresetApplicatorService::class);

// Create preset
$preset = $presetService->create([
    'name' => 'My Scalper',
    'position_size_mode' => 'RISK_PERCENT',
    'risk_per_trade_pct' => 1.0,
    'sl_mode' => 'PIPS',
    'sl_pips' => 50,
    'tp_mode' => 'SINGLE',
    'tp1_rr' => 2.0,
], $user);

// Apply to connection
$connection->preset_id = $preset->id;
$connection->save();

// Get configuration
$config = $applicator->applyAsDTO($preset, $connection->settings ?? []);
```

### Example 2: Calculate Position Size

```php
use Addons\TradingPresetAddon\App\Services\PresetExecutionService;

$executionService = app(PresetExecutionService::class);

// Get preset config
$config = $executionService->getPresetConfig($connection, $signal);

if ($config) {
    // Get equity
    $adapter = $connectionService->getAdapter($connection);
    $balance = $adapter->getBalance();
    $equity = $balance['balance'] ?? 0;

    // Calculate position size
    $quantity = $executionService->calculatePositionSize(
        $config,
        $equity,
        $signal->open_price,
        $signal->sl
    );

    // Calculate SL/TP
    $slPrice = $executionService->calculateSlPrice(
        $config,
        $signal->open_price,
        $signal->direction,
        $signal->structure_sl_price ?? null
    );

    $tpPrices = $executionService->calculateTpPrices(
        $config,
        $signal->open_price,
        $slPrice ?? $signal->sl,
        $signal->direction
    );
}
```

### Example 3: Check Trading Rules

```php
use Addons\TradingPresetAddon\App\Services\PresetExecutionService;

$executionService = app(PresetExecutionService::class);
$config = $executionService->getPresetConfig($connection, $signal);

if ($config) {
    // Check trading schedule
    $scheduleCheck = $executionService->checkTradingSchedule($config);
    if (!$scheduleCheck['allowed']) {
        return ['error' => $scheduleCheck['reason']];
    }

    // Check weekly target
    $weeklyCheck = $executionService->checkWeeklyTarget($connection, $config);
    if (!$weeklyCheck['allowed']) {
        return ['error' => $weeklyCheck['reason']];
    }

    // Check max positions
    $maxPosCheck = $executionService->checkMaxPositions($connection, $config, $signal->symbol);
    if (!$maxPosCheck['allowed']) {
        return ['error' => $maxPosCheck['reason']];
    }
}
```

### Example 4: Use Advanced Features

```php
use Addons\TradingPresetAddon\App\Services\AdvancedTradingService;

$advancedService = app(AdvancedTradingService::class);

// Calculate dynamic equity
$adjustedEquity = $advancedService->calculateDynamicEquity(
    $config,
    $connection,
    $baseEquity
);

// Get price history
$candles = $advancedService->getPriceHistory(
    $connection,
    $symbol,
    '1h',
    50
);

// Calculate ATR
$atr = $advancedService->calculateATR($candles, 14);

// Calculate ATR trailing stop
if ($atr) {
    $newSl = $advancedService->calculateATRTrailingStop(
        $currentPrice,
        $atr,
        2.0, // 2x ATR
        $direction,
        $currentSl
    );
}
```

## Integration Points

### With SignalExecutionService

See `INTEGRATION_GUIDE.md` for detailed integration instructions.

### With CopyTradingService

See `INTEGRATION_COPY_TRADING.md` for detailed integration instructions.

### With TradingBotService

See `INTEGRATION_TRADING_BOTS.md` for detailed integration instructions.

## Error Handling

All services throw exceptions on critical errors. Always wrap calls in try-catch:

```php
try {
    $preset = $presetService->create($data, $user);
} catch (\Exception $e) {
    Log::error("Failed to create preset", [
        'error' => $e->getMessage(),
        'user_id' => $user->id,
    ]);
    // Handle error
}
```

## Performance Considerations

- Preset resolution is cached internally
- Use `PresetConfigurationDTO` to avoid repeated database queries
- Price history fetching may be slow - consider caching
- ATR calculations require sufficient candle data

