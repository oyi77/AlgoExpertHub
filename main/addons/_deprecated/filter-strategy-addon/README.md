# Filter Strategy Addon

**Version:** 1.0.0  
**Status:** Active  
**Sprint:** 1 (Filter Strategy fully working in Telegram flow)

## Overview

Filter Strategy Addon provides technical indicator-based filtering for trading signals. Signals must pass filter evaluation before execution, ensuring only signals that meet technical criteria are traded.

## Features (Sprint 1)

- ✅ Filter Strategy CRUD (Create, Read, Update, Delete)
- ✅ Indicator calculation (EMA, Stochastic, PSAR)
- ✅ Rule evaluation engine (AND/OR logic)
- ✅ Integration with Trading Preset
- ✅ Hook to Telegram signal flow
- ✅ Fail-safe error handling

## Architecture

### Components

1. **FilterStrategy Model** - Stores filter configurations
2. **MarketDataService** - Fetches OHLCV data from exchanges/brokers
3. **IndicatorService** - Calculates technical indicators
4. **FilterStrategyEvaluator** - Evaluates filter rules against market data
5. **FilterStrategyResolverService** - Resolves which filter to use for a signal

### Flow

```
Telegram Message
  → ParsingPipeline
  → AutoSignalService::createFromParsedData()
  → FilterStrategyResolverService::resolveForSignal()
  → FilterStrategyEvaluator::evaluate()
  → If PASS: Continue to auto-publish
  → If FAIL: Stop, signal not published
```

## Database Schema

### filter_strategies

- `id` - Primary key
- `name` - Strategy name
- `description` - Description
- `created_by_user_id` - Owner (nullable)
- `visibility` - PRIVATE / PUBLIC_MARKETPLACE
- `clonable` - Allow cloning
- `enabled` - Enable/disable
- `config` - JSON configuration (indicators + rules)
- `created_at`, `updated_at`, `deleted_at`

### trading_presets (Extended)

- `filter_strategy_id` - Foreign key to filter_strategies (nullable)

## Configuration Format

```json
{
  "indicators": {
    "ema_fast": {"period": 10},
    "ema_slow": {"period": 100},
    "stoch": {"k": 14, "d": 3, "smooth": 3},
    "psar": {"step": 0.02, "max": 0.2}
  },
  "rules": {
    "logic": "AND",
    "conditions": [
      {"left": "ema_fast", "operator": ">", "right": "ema_slow"},
      {"left": "stoch", "operator": "<", "right": 80},
      {"left": "psar", "operator": "below_price", "right": null}
    ]
  }
}
```

## Usage

### Creating a Filter Strategy

1. Go to `/user/filter-strategies/create`
2. Enter name, description
3. Configure indicators and rules in JSON
4. Set visibility and enable status
5. Save

### Assigning to Trading Preset

1. Edit a Trading Preset
2. In Basic tab, select Filter Strategy from dropdown
3. Save preset

### How It Works

When a Telegram signal is received:
1. Signal is parsed and created (draft)
2. System resolves Filter Strategy from preset
3. If filter exists, market data is fetched
4. Indicators are calculated
5. Rules are evaluated
6. If filter passes → signal can be published
7. If filter fails → signal is not published (stays draft)

## Fail-Safe Behavior

- If MarketDataService fails → Filter evaluation fails → Signal not published
- If IndicatorService fails → Filter evaluation fails → Signal not published
- If FilterStrategyEvaluator throws exception → Filter evaluation fails → Signal not published
- **Default: Reject on error** (safer than allowing through)

## Results Storage

Filter evaluation results are stored in:
- `channel_messages.parsed_data['filter_evaluation']` - JSON field

Structure:
```json
{
  "pass": true/false,
  "reason": "string",
  "strategy_id": 123,
  "strategy_name": "EMA Trend Filter",
  "indicators": {...},
  "evaluated_at": "2025-12-02T10:30:00Z"
}
```

## Routes

### Admin
- `GET /admin/filter-strategies` - List all strategies
- `GET /admin/filter-strategies/create` - Create form
- `POST /admin/filter-strategies` - Store
- `GET /admin/filter-strategies/{id}` - Show
- `GET /admin/filter-strategies/{id}/edit` - Edit form
- `PUT /admin/filter-strategies/{id}` - Update
- `DELETE /admin/filter-strategies/{id}` - Delete

### User
- `GET /user/filter-strategies` - List own + public strategies
- `GET /user/filter-strategies/marketplace` - Browse public strategies
- `GET /user/filter-strategies/create` - Create form
- `POST /user/filter-strategies` - Store
- `GET /user/filter-strategies/{id}` - Show
- `GET /user/filter-strategies/{id}/edit` - Edit form
- `PUT /user/filter-strategies/{id}` - Update
- `DELETE /user/filter-strategies/{id}` - Delete
- `POST /user/filter-strategies/{id}/clone` - Clone strategy

## Next Steps (Sprint 2)

- AI Model Profile integration
- AI Market Confirmation
- AI Market Scan
- AI Position Management
- Usage tracking & monetization

