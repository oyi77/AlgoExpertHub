# Trading Management Addon - API Documentation

## Overview

The Trading Management Addon provides RESTful APIs for programmatic access to trading features. This documentation covers all available endpoints, authentication, request/response formats, and examples.

## Base URL

```
https://yoursite.com/api/trading-management
```

## Authentication

All API requests require authentication using Laravel Sanctum tokens.

### Obtaining an API Token

**Endpoint:** `POST /api/login`

**Request:**
```json
{
    "email": "user@example.com",
    "password": "your_password"
}
```

**Response:**
```json
{
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "user": {
        "id": 1,
        "username": "john_doe",
        "email": "user@example.com"
    }
}
```

### Using the Token

Include the token in the `Authorization` header:

```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## Endpoints

### Exchange Connections

#### List Connections

**GET** `/api/trading-management/connections`

Get all exchange/broker connections for the authenticated user.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Binance Main",
            "type": "crypto",
            "exchange": "binance",
            "status": "active",
            "health_status": "healthy",
            "created_at": "2024-01-01T00:00:00Z"
        }
    ]
}
```

#### Create Connection

**POST** `/api/trading-management/connections`

Create a new exchange/broker connection.

**Request:**
```json
{
    "name": "Binance Main",
    "type": "crypto",
    "exchange": "binance",
    "api_key": "your_api_key",
    "api_secret": "your_api_secret",
    "testnet": false
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "Binance Main",
        "type": "crypto",
        "exchange": "binance",
        "status": "active",
        "health_status": "healthy",
        "created_at": "2024-01-01T00:00:00Z"
    },
    "message": "Connection created successfully"
}
```

#### Test Connection

**POST** `/api/trading-management/connections/{id}/test`

Test if a connection is working.

**Response:**
```json
{
    "status": "success",
    "message": "Connection is healthy",
    "response_time": 245
}
```

#### Delete Connection

**DELETE** `/api/trading-management/connections/{id}`

Delete a connection.

**Response:**
```json
{
    "message": "Connection deleted successfully"
}
```

---

### Risk Presets

#### List Presets

**GET** `/api/trading-management/presets`

Get all risk presets for the authenticated user.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Conservative",
            "risk_type": "percentage",
            "risk_amount": 2,
            "stop_loss_type": "signal",
            "take_profit_type": "single",
            "created_at": "2024-01-01T00:00:00Z"
        }
    ]
}
```

#### Create Preset

**POST** `/api/trading-management/presets`

Create a new risk preset.

**Request:**
```json
{
    "name": "Aggressive",
    "risk_type": "percentage",
    "risk_amount": 5,
    "stop_loss_type": "signal",
    "take_profit_type": "multiple",
    "take_profit_levels": [
        {"percentage": 50, "target": 1.5},
        {"percentage": 30, "target": 2.0},
        {"percentage": 20, "target": 3.0}
    ]
}
```

**Response:**
```json
{
    "data": {
        "id": 2,
        "name": "Aggressive",
        "risk_type": "percentage",
        "risk_amount": 5,
        "created_at": "2024-01-01T00:00:00Z"
    },
    "message": "Preset created successfully"
}
```

---

### Positions

#### List Positions

**GET** `/api/trading-management/positions`

Get all positions (open and closed).

**Query Parameters:**
- `status` - Filter by status (open, closed)
- `connection_id` - Filter by connection
- `symbol` - Filter by symbol
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "connection_id": 1,
            "symbol": "BTCUSDT",
            "side": "buy",
            "entry_price": 50000,
            "current_price": 51000,
            "quantity": 0.1,
            "stop_loss": 49000,
            "take_profit": 52000,
            "pnl": 100,
            "pnl_percentage": 2,
            "status": "open",
            "opened_at": "2024-01-01T00:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 50,
        "per_page": 20
    }
}
```

#### Get Position

**GET** `/api/trading-management/positions/{id}`

Get details of a specific position.

**Response:**
```json
{
    "data": {
        "id": 1,
        "connection_id": 1,
        "connection_name": "Binance Main",
        "symbol": "BTCUSDT",
        "side": "buy",
        "entry_price": 50000,
        "current_price": 51000,
        "quantity": 0.1,
        "stop_loss": 49000,
        "take_profit": 52000,
        "pnl": 100,
        "pnl_percentage": 2,
        "status": "open",
        "signal_id": 123,
        "opened_at": "2024-01-01T00:00:00Z",
        "closed_at": null
    }
}
```

#### Close Position

**POST** `/api/trading-management/positions/{id}/close`

Close an open position.

**Request:**
```json
{
    "percentage": 100
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "status": "closed",
        "close_price": 51000,
        "pnl": 100,
        "closed_at": "2024-01-01T01:00:00Z"
    },
    "message": "Position closed successfully"
}
```

#### Modify Position

**PUT** `/api/trading-management/positions/{id}`

Modify stop loss or take profit.

**Request:**
```json
{
    "stop_loss": 49500,
    "take_profit": 53000
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "stop_loss": 49500,
        "take_profit": 53000
    },
    "message": "Position updated successfully"
}
```

---

### Backtesting

#### Create Backtest

**POST** `/api/trading-management/backtests`

Create and run a new backtest.

**Request:**
```json
{
    "name": "BTCUSDT Strategy Test",
    "symbol": "BTCUSDT",
    "timeframe": "1h",
    "start_date": "2024-01-01",
    "end_date": "2024-03-01",
    "filter_strategy_id": 1,
    "risk_preset_id": 1,
    "initial_balance": 10000
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "BTCUSDT Strategy Test",
        "status": "running",
        "created_at": "2024-01-01T00:00:00Z"
    },
    "message": "Backtest started successfully"
}
```

#### Get Backtest Results

**GET** `/api/trading-management/backtests/{id}`

Get backtest results.

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "BTCUSDT Strategy Test",
        "status": "completed",
        "results": {
            "total_trades": 150,
            "winning_trades": 95,
            "losing_trades": 55,
            "win_rate": 63.33,
            "profit_factor": 1.85,
            "max_drawdown": 12.5,
            "sharpe_ratio": 1.42,
            "total_pnl": 2500,
            "average_win": 45,
            "average_loss": 25
        },
        "equity_curve": [...],
        "trades": [...]
    }
}
```

---

### Filter Strategies

#### List Strategies

**GET** `/api/trading-management/filter-strategies`

Get all filter strategies.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Trend Following",
            "description": "EMA + RSI strategy",
            "filters": [
                {
                    "indicator": "ema",
                    "period": 50,
                    "condition": "price_above"
                },
                {
                    "indicator": "rsi",
                    "period": 14,
                    "condition": "below",
                    "value": 70
                }
            ]
        }
    ]
}
```

#### Create Strategy

**POST** `/api/trading-management/filter-strategies`

Create a new filter strategy.

**Request:**
```json
{
    "name": "Momentum Strategy",
    "description": "MACD + RSI",
    "filters": [
        {
            "indicator": "macd",
            "condition": "bullish_cross"
        },
        {
            "indicator": "rsi",
            "period": 14,
            "condition": "above",
            "value": 50
        }
    ],
    "logic": "and"
}
```

---

### Trading Bots

#### List Bots

**GET** `/api/trading-management/bots`

Get all trading bots.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "BTCUSDT Scalper",
            "connection_id": 1,
            "status": "running",
            "performance": {
                "total_trades": 45,
                "win_rate": 68,
                "pnl": 450
            }
        }
    ]
}
```

#### Start Bot

**POST** `/api/trading-management/bots/{id}/start`

Start a trading bot.

**Response:**
```json
{
    "message": "Bot started successfully",
    "data": {
        "id": 1,
        "status": "running"
    }
}
```

#### Stop Bot

**POST** `/api/trading-management/bots/{id}/stop`

Stop a trading bot.

**Response:**
```json
{
    "message": "Bot stopped successfully",
    "data": {
        "id": 1,
        "status": "stopped"
    }
}
```

---

### Analytics

#### Get Performance Analytics

**GET** `/api/trading-management/analytics`

Get trading performance analytics.

**Query Parameters:**
- `connection_id` - Filter by connection
- `start_date` - Start date (YYYY-MM-DD)
- `end_date` - End date (YYYY-MM-DD)

**Response:**
```json
{
    "data": {
        "total_trades": 250,
        "winning_trades": 165,
        "losing_trades": 85,
        "win_rate": 66,
        "profit_factor": 2.1,
        "total_pnl": 5000,
        "max_drawdown": 8.5,
        "sharpe_ratio": 1.65,
        "by_symbol": {
            "BTCUSDT": {
                "trades": 100,
                "win_rate": 70,
                "pnl": 2500
            },
            "ETHUSDT": {
                "trades": 150,
                "win_rate": 63,
                "pnl": 2500
            }
        }
    }
}
```

---

## Webhooks

### Signal Execution Webhook

Receive notifications when signals are executed.

**Webhook URL:** Configure in Trading Management settings

**Payload:**
```json
{
    "event": "signal_executed",
    "data": {
        "signal_id": 123,
        "connection_id": 1,
        "symbol": "BTCUSDT",
        "side": "buy",
        "entry_price": 50000,
        "quantity": 0.1,
        "stop_loss": 49000,
        "take_profit": 52000
    },
    "timestamp": "2024-01-01T00:00:00Z"
}
```

### Position Closed Webhook

Receive notifications when positions are closed.

**Payload:**
```json
{
    "event": "position_closed",
    "data": {
        "position_id": 1,
        "symbol": "BTCUSDT",
        "close_price": 51000,
        "pnl": 100,
        "pnl_percentage": 2,
        "reason": "take_profit"
    },
    "timestamp": "2024-01-01T01:00:00Z"
}
```

---

## Error Handling

### Error Response Format

```json
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": {
            "api_key": ["The api key field is required."]
        }
    }
}
```

### Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Invalid request data |
| `UNAUTHORIZED` | 401 | Invalid or missing token |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `RATE_LIMIT` | 429 | Too many requests |
| `SERVER_ERROR` | 500 | Internal server error |
| `CONNECTION_ERROR` | 503 | Exchange/broker connection failed |

---

## Rate Limiting

API requests are rate-limited to prevent abuse.

**Limits:**
- **Authenticated requests**: 60 requests per minute
- **Unauthenticated requests**: 10 requests per minute

**Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1609459200
```

---

## Code Examples

### JavaScript (Axios)

```javascript
const axios = require('axios');

const api = axios.create({
    baseURL: 'https://yoursite.com/api',
    headers: {
        'Authorization': 'Bearer YOUR_TOKEN',
        'Accept': 'application/json'
    }
});

// Get positions
async function getPositions() {
    try {
        const response = await api.get('/trading-management/positions');
        console.log(response.data);
    } catch (error) {
        console.error(error.response.data);
    }
}

// Create connection
async function createConnection() {
    try {
        const response = await api.post('/trading-management/connections', {
            name: 'Binance Main',
            type: 'crypto',
            exchange: 'binance',
            api_key: 'your_key',
            api_secret: 'your_secret'
        });
        console.log(response.data);
    } catch (error) {
        console.error(error.response.data);
    }
}
```

### Python (Requests)

```python
import requests

BASE_URL = 'https://yoursite.com/api'
TOKEN = 'YOUR_TOKEN'

headers = {
    'Authorization': f'Bearer {TOKEN}',
    'Accept': 'application/json'
}

# Get positions
response = requests.get(
    f'{BASE_URL}/trading-management/positions',
    headers=headers
)
print(response.json())

# Create connection
data = {
    'name': 'Binance Main',
    'type': 'crypto',
    'exchange': 'binance',
    'api_key': 'your_key',
    'api_secret': 'your_secret'
}

response = requests.post(
    f'{BASE_URL}/trading-management/connections',
    headers=headers,
    json=data
)
print(response.json())
```

### PHP (Guzzle)

```php
<?php

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://yoursite.com/api',
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Accept' => 'application/json'
    ]
]);

// Get positions
$response = $client->get('/trading-management/positions');
$data = json_decode($response->getBody(), true);
print_r($data);

// Create connection
$response = $client->post('/trading-management/connections', [
    'json' => [
        'name' => 'Binance Main',
        'type' => 'crypto',
        'exchange' => 'binance',
        'api_key' => 'your_key',
        'api_secret' => 'your_secret'
    ]
]);
$data = json_decode($response->getBody(), true);
print_r($data);
```

---

## Support

For API support:
- **Documentation**: This guide
- **Support Tickets**: Submit in platform
- **Developer Forum**: Ask technical questions

---

**Last Updated**: 2025-12-22
