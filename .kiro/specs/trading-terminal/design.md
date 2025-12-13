# Trading Terminal Design

## Architecture Overview

The Trading Terminal is a single-page application (SPA) built with Laravel Blade and JavaScript, providing real-time trading functionality. It integrates with the existing Trading Management Addon backend to provide a unified trading interface.

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│              Trading Terminal (Frontend)                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │  Market  │  │Position  │  │  Order  │  │Analytics│ │
│  │   Data   │  │ Monitor  │  │ Placement│  │ Dashboard│ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │Execution │  │Connection │  │  Signal  │             │
│  │   Log    │  │  Manager  │  │  Panel   │             │
│  └──────────┘  └──────────┘  └──────────┘             │
└─────────────────────────────────────────────────────────┘
                        │
                        │ API Calls (REST + WebSocket)
                        ▼
┌─────────────────────────────────────────────────────────┐
│         Trading Management Addon (Backend)               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │Execution │  │Position  │  │  Market   │             │
│  │ Service  │  │Monitoring│  │   Data    │             │
│  └──────────┘  └──────────┘  └──────────┘             │
│  ┌──────────┐  ┌──────────┐                           │
│  │  Risk    │  │Analytics │                           │
│  │Management│  │ Service  │                           │
│  └──────────┘  └──────────┘                           │
└─────────────────────────────────────────────────────────┘
                        │
                        │
                        ▼
┌─────────────────────────────────────────────────────────┐
│              External Services                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │  CCXT    │  │  MTAPI   │  │TradingView│            │
│  │Exchanges │  │  Brokers │  │  Charts  │             │
│  └──────────┘  └──────────┘  └──────────┘             │
└─────────────────────────────────────────────────────────┘
```

### Technology Stack

**Frontend**:
- Laravel Blade templates (server-rendered base)
- JavaScript (Vanilla or Alpine.js for reactivity)
- TradingView Widget (for charts) OR Chart.js (lightweight alternative)
- WebSocket client (Laravel Echo + Pusher/Broadcasting) OR Polling (setInterval)

**Backend**:
- Laravel 9.x (existing)
- Trading Management Addon APIs
- Laravel Broadcasting (for real-time updates)
- Queue system (for async operations)

**Real-Time Updates**:
- Option 1: Laravel Broadcasting + Laravel Echo + Pusher
- Option 2: Polling via setInterval (simpler, less real-time)
- Option 3: Server-Sent Events (SSE) - middle ground

## Component Design

### Component 1: Market Data Panel

**Purpose**: Display real-time market data and charts

**Location**: `main/addons/trading-management-addon/resources/views/user/terminal/market-data.blade.php`

**Responsibilities**:
- Fetch and display current price data
- Render trading charts
- Handle symbol selection
- Update data in real-time

**API Endpoints**:
- `GET /api/trading/terminal/market-data/{symbol}` - Get current market data
- `GET /api/trading/terminal/market-data/{symbol}/history` - Get historical data for charts

**Data Structure**:
```json
{
  "symbol": "BTC/USDT",
  "price": 45000.50,
  "change_24h": 2.5,
  "high_24h": 46000.00,
  "low_24h": 44000.00,
  "volume_24h": 1234567.89,
  "timestamp": "2025-01-13T10:00:00Z"
}
```

**Real-Time Updates**: Poll every 2 seconds OR WebSocket subscription

---

### Component 2: Position Monitor Panel

**Purpose**: Display and monitor open positions in real-time

**Location**: `main/addons/trading-management-addon/resources/views/user/terminal/positions.blade.php`

**Responsibilities**:
- Fetch and display open positions
- Calculate and update P&L in real-time
- Highlight positions hitting SL/TP
- Show position details on click

**API Endpoints**:
- `GET /api/trading/terminal/positions` - Get all open positions
- `GET /api/trading/terminal/positions/{id}` - Get position details
- `PUT /api/trading/terminal/positions/{id}/close` - Manually close position

**Data Structure**:
```json
{
  "id": 123,
  "symbol": "BTC/USDT",
  "direction": "long",
  "entry_price": 45000.00,
  "current_price": 45500.00,
  "quantity": 0.1,
  "pnl": 50.00,
  "pnl_percentage": 0.11,
  "sl_price": 44000.00,
  "tp_price": 46000.00,
  "status": "open",
  "connection_id": 1,
  "signal_id": 456
}
```

**Real-Time Updates**: Poll every 2 seconds OR WebSocket subscription

---

### Component 3: Order Placement Panel

**Purpose**: Allow users to place manual orders

**Location**: `main/addons/trading-management-addon/resources/views/user/terminal/order-form.blade.php`

**Responsibilities**:
- Display order form
- Validate order parameters
- Submit order to backend
- Show order confirmation/errors

**API Endpoints**:
- `POST /api/trading/terminal/orders` - Place new order
- `GET /api/trading/terminal/connections` - Get available connections
- `GET /api/trading/terminal/presets` - Get available presets

**Request Structure**:
```json
{
  "connection_id": 1,
  "symbol": "BTC/USDT",
  "direction": "buy",
  "order_type": "market",
  "quantity": 0.1,
  "price": null,
  "sl_price": 44000.00,
  "tp_price": 46000.00,
  "preset_id": 1
}
```

**Validation**:
- Connection must be active
- Symbol must be tradable on selected connection
- Quantity must be positive
- For limit orders, price must be provided
- Balance must be sufficient

---

### Component 4: Execution Log Viewer

**Purpose**: Display trade execution history

**Location**: `main/addons/trading-management-addon/resources/views/user/terminal/execution-log.blade.php`

**Responsibilities**:
- Fetch and display execution history
- Provide filtering and pagination
- Export to CSV
- Show execution details

**API Endpoints**:
- `GET /api/trading/terminal/executions` - Get execution log (with filters)
- `GET /api/trading/terminal/executions/export` - Export to CSV

**Query Parameters**:
- `page`: Page number
- `per_page`: Items per page (default 50)
- `connection_id`: Filter by connection
- `symbol`: Filter by symbol
- `status`: Filter by status (success, failed)
- `date_from`: Start date
- `date_to`: End date

---

### Component 5: Connection Manager

**Purpose**: Display and manage execution connections

**Location**: `main/addons/trading-management-addon/resources/views/user/terminal/connections.blade.php`

**Responsibilities**:
- Display all user connections
- Show connection status and health
- Allow connection selection
- Display account balance

**API Endpoints**:
- `GET /api/trading/terminal/connections` - Get all connections
- `GET /api/trading/terminal/connections/{id}/balance` - Get account balance
- `GET /api/trading/terminal/connections/{id}/health` - Check connection health

---

### Component 6: Analytics Dashboard

**Purpose**: Display trading performance metrics

**Location**: `main/addons/trading-management-addon/resources/views/user/terminal/analytics.blade.php`

**Responsibilities**:
- Fetch analytics data
- Display key metrics
- Render performance charts
- Provide time period filtering

**API Endpoints**:
- `GET /api/trading/terminal/analytics` - Get analytics data
- Query params: `period` (today, 7d, 30d, all)

**Data Structure**:
```json
{
  "total_pnl": 1234.56,
  "win_rate": 65.5,
  "profit_factor": 1.85,
  "total_trades": 100,
  "winning_trades": 65,
  "losing_trades": 35,
  "avg_win": 50.00,
  "avg_loss": -25.00,
  "max_drawdown": -200.00,
  "pnl_chart": [
    {"date": "2025-01-01", "pnl": 100.00},
    {"date": "2025-01-02", "pnl": 150.00}
  ]
}
```

---

### Component 7: Signal Panel

**Purpose**: Display and execute trading signals

**Location**: `main/addons/trading-management-addon/resources/views/user/terminal/signals.blade.php`

**Responsibilities**:
- Fetch user's signals (from subscribed plans)
- Display signal details
- Allow signal execution
- Show execution status

**API Endpoints**:
- `GET /api/trading/terminal/signals` - Get available signals
- `POST /api/trading/terminal/signals/{id}/execute` - Execute signal

**Integration**: Uses core Signal model and plan subscriptions

---

## Database Schema

### No New Tables Required

The terminal uses existing tables from Trading Management Addon:
- `execution_connections` - User's exchange connections
- `execution_positions` - Open/closed positions
- `execution_logs` - Trade execution history
- `execution_analytics` - Performance analytics
- `trading_presets` - Risk management presets
- `signals` - Trading signals (core platform)
- `plan_subscriptions` - User's plan subscriptions

### Optional: Terminal Settings Table

If we want to persist user's terminal layout preferences:

```sql
CREATE TABLE terminal_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    layout_config JSON,
    default_connection_id BIGINT UNSIGNED NULL,
    default_preset_id BIGINT UNSIGNED NULL,
    chart_timeframe VARCHAR(10) DEFAULT '1h',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (default_connection_id) REFERENCES execution_connections(id) ON DELETE SET NULL,
    FOREIGN KEY (default_preset_id) REFERENCES trading_presets(id) ON DELETE SET NULL
);
```

---

## API Contracts

### API Route Structure

All terminal APIs under: `/api/trading/terminal/*`

**Base Route**: `main/addons/trading-management-addon/routes/api.php`

```php
Route::prefix('trading/terminal')
    ->middleware(['auth', 'is_email_verified', '2fa'])
    ->name('trading.terminal.')
    ->group(function () {
        // Market Data
        Route::get('market-data/{symbol}', [TerminalController::class, 'getMarketData']);
        Route::get('market-data/{symbol}/history', [TerminalController::class, 'getMarketHistory']);
        
        // Positions
        Route::get('positions', [TerminalController::class, 'getPositions']);
        Route::get('positions/{id}', [TerminalController::class, 'getPosition']);
        Route::put('positions/{id}/close', [TerminalController::class, 'closePosition']);
        
        // Orders
        Route::post('orders', [TerminalController::class, 'placeOrder']);
        Route::get('orders', [TerminalController::class, 'getOrders']);
        
        // Execution Log
        Route::get('executions', [TerminalController::class, 'getExecutions']);
        Route::get('executions/export', [TerminalController::class, 'exportExecutions']);
        
        // Connections
        Route::get('connections', [TerminalController::class, 'getConnections']);
        Route::get('connections/{id}/balance', [TerminalController::class, 'getBalance']);
        Route::get('connections/{id}/health', [TerminalController::class, 'checkHealth']);
        
        // Analytics
        Route::get('analytics', [TerminalController::class, 'getAnalytics']);
        
        // Signals
        Route::get('signals', [TerminalController::class, 'getSignals']);
        Route::post('signals/{id}/execute', [TerminalController::class, 'executeSignal']);
    });
```

### Response Format

Standard Laravel API response:
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

Error response:
```json
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

---

## Real-Time Updates Strategy

### Option 1: Polling (Simpler, Recommended for MVP)

**Implementation**:
- JavaScript `setInterval` polling every 2 seconds
- Poll endpoints: market data, positions, execution log
- Show connection status indicator

**Pros**:
- Simple to implement
- No additional infrastructure
- Works with existing Laravel setup

**Cons**:
- Not truly real-time (2s delay)
- Higher server load
- Battery drain on mobile

**Code Example**:
```javascript
class TerminalUpdater {
    constructor() {
        this.interval = 2000; // 2 seconds
        this.timer = null;
    }
    
    start() {
        this.timer = setInterval(() => {
            this.updateMarketData();
            this.updatePositions();
            this.updateExecutions();
        }, this.interval);
    }
    
    stop() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    }
    
    async updateMarketData() {
        // Fetch market data
    }
    
    async updatePositions() {
        // Fetch positions
    }
    
    async updateExecutions() {
        // Fetch new executions
    }
}
```

### Option 2: WebSocket (More Real-Time)

**Implementation**:
- Laravel Broadcasting + Laravel Echo + Pusher
- Broadcast events: PositionUpdated, ExecutionCreated, MarketDataUpdated
- Client subscribes to user-specific channels

**Pros**:
- True real-time updates
- Lower server load
- Better user experience

**Cons**:
- Requires Pusher account or self-hosted WebSocket server
- More complex setup
- Additional infrastructure cost

**Event Broadcasting**:
```php
// In PositionMonitoring Service
event(new PositionUpdated($position));

// In TerminalController
broadcast(new MarketDataUpdated($symbol, $data))->toOthers();
```

---

## Security Considerations

1. **Authentication**: All endpoints require `auth` middleware
2. **Authorization**: Users can only access their own data (filter by `user_id`)
3. **Rate Limiting**: Apply throttle middleware to prevent abuse
4. **Input Validation**: Validate all order parameters
5. **Balance Checks**: Verify sufficient balance before order placement
6. **Connection Validation**: Ensure connection belongs to user
7. **CSRF Protection**: All POST requests require CSRF token

**Middleware Stack**:
```php
Route::middleware([
    'auth',
    'is_email_verified',
    '2fa',
    'kyc', // Optional, if KYC required for trading
    'throttle:60,1' // 60 requests per minute
])
```

---

## Performance Optimization

1. **Caching**: Cache market data for 1-2 seconds
2. **Eager Loading**: Load relationships in API responses
3. **Pagination**: Limit execution log to 50 per page
4. **Lazy Loading**: Load chart data on demand
5. **Debouncing**: Debounce order form submissions
6. **Connection Pooling**: Reuse exchange API connections

---

## UI/UX Design

### Layout Structure

```
┌─────────────────────────────────────────────────────────┐
│  Header: Connection Selector | Balance | Settings       │
├──────────┬──────────────────────────────────────────────┤
│          │  Market Data Panel (Chart + Price Info)      │
│  Signal  │                                               │
│  Panel   │                                               │
│          ├──────────────────────────────────────────────┤
│          │  Order Placement Form                        │
├──────────┼──────────────────────────────────────────────┤
│          │  Positions Panel (Open Positions)            │
│          ├──────────────────────────────────────────────┤
│          │  Execution Log (Recent Executions)            │
└──────────┴──────────────────────────────────────────────┘
│  Analytics Dashboard (Collapsible)                     │
└─────────────────────────────────────────────────────────┘
```

### Responsive Design

- **Desktop**: Full layout with all panels visible
- **Tablet**: Stack panels vertically, collapsible sections
- **Mobile**: Single column, tabbed interface

### Color Scheme

- **Profit**: Green (#10b981)
- **Loss**: Red (#ef4444)
- **Neutral**: Gray (#6b7280)
- **Warning**: Yellow (#f59e0b)
- **Info**: Blue (#3b82f6)

---

## Integration Points

### Trading Management Addon Modules

1. **Execution Module**: Order placement, execution logging
2. **Position Monitoring Module**: Position tracking, P&L calculation
3. **Risk Management Module**: Preset selection, position sizing
4. **Data Provider Module**: Market data fetching
5. **Analytics Module**: Performance metrics

### Core Platform

1. **Signal System**: Display and execute signals
2. **Plan Subscriptions**: Filter signals by user's plans
3. **User Authentication**: User session and permissions

---

## Testing Strategy

1. **Unit Tests**: Test API controllers and services
2. **Feature Tests**: Test complete user flows (place order, monitor position)
3. **Integration Tests**: Test exchange API integration (mock CCXT)
4. **Frontend Tests**: Test JavaScript components (if using framework)
5. **Performance Tests**: Test API response times under load

---

## Deployment Considerations

1. **Environment Variables**: TradingView API key (if using), Pusher credentials (if using WebSocket)
2. **Queue Workers**: Ensure queue workers running for async operations
3. **Caching**: Configure Redis/Memcached for market data caching
4. **CDN**: Serve static assets via CDN for better performance
5. **Monitoring**: Set up error tracking (Sentry) and performance monitoring

---

## Change History

- 2025-01-13: Initial design document created

