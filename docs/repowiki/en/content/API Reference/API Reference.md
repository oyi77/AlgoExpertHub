# API Reference

<cite>
**Referenced Files in This Document**   
- [api.php](file://main/routes/api.php)
- [channels.php](file://main/routes/channels.php)
- [api.php](file://main/addons/multi-channel-signal-addon/routes/api.php)
- [api.php](file://main/addons/trading-management-addon/routes/api.php)
- [api.php](file://main/addons/ai-connection-addon/routes/api.php)
- [channels.php](file://main/addons/trading-management-addon/routes/channels.php)
- [TelegramWebhookController.php](file://main/app/Http/Controllers/Api/TelegramWebhookController.php)
- [ApiWebhookController.php](file://main/app/Http/Controllers/Api/ApiWebhookController.php)
- [CryptoTradeController.php](file://main/app/Http/Controllers/Api/User/CryptoTradeController.php)
- [PositionUpdated.php](file://main/addons/trading-management-addon/Modules/PositionMonitoring/Events/PositionUpdated.php)
- [PositionClosed.php](file://main/addons/trading-management-addon/Modules/PositionMonitoring/Events/PositionClosed.php)
- [BalanceUpdated.php](file://main/addons/trading-management-addon/Modules/PositionMonitoring/Events/BalanceUpdated.php)
- [broadcasting.php](file://main/config/broadcasting.php)
- [sanctum.php](file://main/config/sanctum.php)
- [api-reference.md](file://docs/api-reference.md)
</cite>

## Table of Contents
1. [Authentication](#authentication)
2. [Base URL](#base-url)
3. [REST API Endpoints](#rest-api-endpoints)
4. [Webhook Endpoints](#webhook-endpoints)
5. [WebSocket & Real-time APIs](#websocket--real-time-apis)
6. [Error Handling](#error-handling)
7. [Rate Limiting](#rate-limiting)
8. [API Versioning and Compatibility](#api-versioning-and-compatibility)
9. [Client Implementation Guidelines](#client-implementation-guidelines)
10. [Security Considerations](#security-considerations)

## Authentication

The AITradePulse platform uses **Laravel Sanctum** for API authentication. Most endpoints require authentication via Bearer token, while webhook endpoints use channel source IDs for identification.

### Obtaining API Token

**Endpoint**: `POST /api/auth/login`

**Request**:
```json
{
  "email": "user@example.com",
  "password": "password",
  "device_name": "web"
}
```

**Response**:
```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

### Using API Token

Include token in Authorization header for authenticated endpoints:

```
Authorization: Bearer {token}
```

### Sanctum Configuration

The Sanctum configuration is defined in `config/sanctum.php` and supports token-based authentication for SPA and mobile applications. Tokens can be scoped to specific abilities, though the current implementation uses standard personal access tokens.

**Section sources**
- [api.php](file://main/routes/api.php#L18-L30)
- [sanctum.php](file://main/config/sanctum.php)

## Base URL

```
Production: https://aitradepulse.com/api
Development: http://localhost/api
```

All endpoints are prefixed with `/api`. The base URL should be configured according to the deployment environment.

## REST API Endpoints

### User Management Endpoints

#### Get Authenticated User
**Endpoint**: `GET /api/user`  
**Authentication**: Required  
**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "balance": "100.00",
    "created_at": "2023-01-01T00:00:00.000000Z"
  }
}
```

#### User Profile Operations
- `GET /api/user/profile` - Get user profile
- `PUT /api/user/profile` - Update user profile
- `POST /api/user/change-password` - Change password
- `GET /api/user/kyc` - Get KYC status
- `POST /api/user/kyc` - Submit KYC documents

#### User Dashboard and Statistics
- `GET /api/user/dashboard` - Get dashboard data
- `GET /api/user/stats` - Get statistical data
- `GET /api/user/transactions` - Get transaction history
- `GET /api/user/transactions/summary` - Get transaction summary

### Trading Operations Endpoints

#### Trading Bot Management
- `GET /api/user/trading-bots` - List all trading bots
- `POST /api/user/trading-bots` - Create new trading bot
- `GET /api/user/trading-bots/{id}` - Get specific bot details
- `PUT /api/user/trading-bots/{id}` - Update trading bot
- `DELETE /api/user/trading-bots/{id}` - Delete trading bot
- `POST /api/user/trading-bots/{id}/start` - Start bot
- `POST /api/user/trading-bots/{id}/stop` - Stop bot
- `POST /api/user/trading-bots/{id}/pause` - Pause bot
- `POST /api/user/trading-bots/{id}/resume` - Resume bot
- `POST /api/user/trading-bots/{id}/restart` - Restart bot
- `GET /api/user/trading-bots/{id}/worker-status` - Get worker status
- `GET /api/user/trading-bots/{id}/positions` - Get bot positions
- `GET /api/user/trading-bots/{id}/logs` - Get bot logs
- `GET /api/user/trading-bots/{id}/metrics` - Get performance metrics

#### Trading Configuration
- `GET /api/user/trading-config/connections` - List exchange connections
- `POST /api/user/trading-config/connections` - Create connection
- `PUT /api/user/trading-config/connections/{id}` - Update connection
- `DELETE /api/user/trading-config/connections/{id}` - Delete connection
- `POST /api/user/trading-config/connections/{id}/test` - Test connection
- `GET /api/user/trading-config/presets` - Get risk presets
- `POST /api/user/trading-config/presets` - Create risk preset
- `GET /api/user/trading-config/filter-strategies` - Get filter strategies
- `GET /api/user/trading-config/ai-profiles` - Get AI profiles

#### Manual Trading Operations
- `POST /api/user/trading-operations/manual-trade` - Execute manual trade
- `GET /api/user/trading-operations/statistics` - Get trading statistics
- `GET /api/user/trading-operations/execution-logs` - Get execution logs

#### Crypto Trading
- `GET /api/user/crypto-trading/current-price` - Get current price
- `GET /api/user/crypto-trading/ticker` - Get latest ticker
- `POST /api/user/crypto-trading/open-trade` - Open new trade
- `GET /api/user/crypto-trading/trades` - Get trade history
- `POST /api/user/crypto-trading/trades/{id}/close` - Close trade
- `GET /api/user/crypto-trading/stream-prices` - Stream price updates

### Signal Processing Endpoints

#### Signal Management
- `GET /api/user/signals` - List signals
- `GET /api/user/signals/{id}` - Get signal details
- `GET /api/user/signals/dashboard` - Get dashboard signals
- `GET /api/user/trading/signals` - Get trading signals
- `GET /api/user/trading/executions` - Get execution history
- `POST /api/user/trading/execute` - Execute trade from signal

#### Channel Sources Management
- `GET /api/user/channel-sources` - List channel sources
- `POST /api/user/channel-sources` - Create channel source
- `PUT /api/user/channel-sources/{id}` - Update channel source
- `DELETE /api/user/channel-sources/{id}` - Delete channel source

### System Monitoring Endpoints

#### Admin Monitoring and Management
- `GET /api/admin/dashboard` - Admin dashboard
- `GET /api/admin/users` - List all users
- `GET /api/admin/users/{id}` - Get user details
- `PUT /api/admin/users/{id}` - Update user
- `POST /api/admin/users/{id}/status` - Toggle user status
- `POST /api/admin/users/{id}/balance` - Update user balance
- `POST /api/admin/users/{id}/kyc/{status}` - Update KYC status
- `POST /api/admin/users/{id}/mail` - Send email to user

#### System Operations
- `GET /api/admin/logs/transactions` - Transaction logs
- `GET /api/admin/logs/payments` - Payment logs
- `GET /api/admin/logs/withdrawals` - Withdrawal logs
- `GET /api/admin/logs/commissions` - Commission logs
- `GET /api/admin/logs/trades` - Trade logs
- `POST /api/admin/system/optimize` - Optimize system
- `POST /api/admin/system/cache/clear` - Clear cache
- `POST /api/admin/system/cache/prewarm` - Prewarm cache
- `POST /api/admin/system/backup/create` - Create backup
- `POST /api/admin/system/backup/load` - Load backup
- `POST /api/admin/system/backup/delete` - Delete backup

**Section sources**
- [api.php](file://main/routes/api.php#L65-L477)

## Webhook Endpoints

### Telegram Webhook

Receive Telegram channel updates and automatically process them as signals.

**Endpoint**: `POST /api/webhook/telegram/{channelSourceId}`  
**Authentication**: Not required (uses channel source ID)  
**Headers**:
```
Content-Type: application/json
```

**Request Body** (Telegram Update Format):
```json
{
  "update_id": 123456789,
  "channel_post": {
    "message_id": 1,
    "chat": {
      "id": -1001234567890,
      "title": "Trading Signals",
      "type": "channel"
    },
    "date": 1609459200,
    "text": "EUR/USD BUY 1.1000 SL 1.0950 TP 1.1100"
  }
}
```

**Response**:
```json
{
  "ok": true
}
```

**Status Codes**:
- `200` - Success (message processed or ignored)
- `400` - Bad Request (invalid channel type, channel not active, no message text)
- `500` - Internal Server Error

**Notes**:
- Webhook accepts Telegram's standard update format
- Extracts message from `channel_post` or `message` fields
- Supports text messages and captions
- Automatically filters duplicates (same message hash within 24 hours)
- Only processes messages from configured chat ID (if specified)
- Dispatches `ProcessChannelMessage` job for async processing

**Setting Up Telegram Webhook**:
1. Get your channel source ID from admin panel
2. Set webhook URL in Telegram:
```bash
curl -X POST https://api.telegram.org/bot{TOKEN}/setWebhook \
  -d url=https://aitradepulse.com/api/webhook/telegram/{channelSourceId}
```

3. Verify webhook:
```bash
curl https://api.telegram.org/bot{TOKEN}/getWebhookInfo
```

### API Webhook

Receive custom API webhook requests and process them as signals.

**Endpoint**: `POST /api/webhook/channel/{channelSourceId}`  
**Authentication**: Not required (uses channel source ID)  
**Headers**:
```
Content-Type: application/json
X-Signature: {signature} (optional, if signature verification enabled)
```

**Request Body** (JSON):
```json
{
  "message": "EUR/USD BUY 1.1000 SL 1.0950 TP 1.1100"
}
```

**Supported Message Fields**:
- `message`
- `text`
- `content`
- `body`
- `signal`
- `data`

**Response**:
```json
{
  "ok": true
}
```

**Status Codes**:
- `200` - Success (message processed or ignored)
- `400` - Bad Request (invalid channel type, channel not active, no message found)
- `401` - Unauthorized (invalid signature)
- `500` - Internal Server Error

**Signature Verification**:
If signature verification is enabled for the channel source:
1. Calculate HMAC-SHA256 signature:
```php
$signature = hash_hmac('sha256', $payload, $secret);
```
2. Send signature in header:
```
X-Signature: {signature}
```

**Example cURL Request**:
```bash
curl -X POST https://aitradepulse.com/api/webhook/channel/1 \
  -H "Content-Type: application/json" \
  -H "X-Signature: abc123..." \
  -d '{"message": "EUR/USD BUY 1.1000 SL 1.0950 TP 1.1100"}'
```

**Notes**:
- Accepts JSON, form data, or raw text payloads
- Automatically extracts message from common field names
- Supports signature verification (HMAC-SHA256)
- Filters duplicates (same message hash within 24 hours)
- Dispatches `ProcessChannelMessage` job for async processing

**Section sources**
- [api.php](file://main/routes/api.php#L478-L479)
- [api.php](file://main/addons/multi-channel-signal-addon/routes/api.php#L7-L10)
- [TelegramWebhookController.php](file://main/app/Http/Controllers/Api/TelegramWebhookController.php)
- [ApiWebhookController.php](file://main/app/Http/Controllers/Api/ApiWebhookController.php)

## WebSocket & Real-time APIs

The platform supports real-time updates through Laravel's broadcasting system with support for Pusher, Ably, Redis, and other drivers.

### WebSocket Configuration

The broadcasting configuration is defined in `config/broadcasting.php` with support for multiple drivers:

```php
'default' => env('BROADCAST_DRIVER', 'null'),

'connections' => [
    'pusher' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true,
        ],
    ],
    'ably' => [
        'driver' => 'ably',
        'key' => env('ABLY_KEY'),
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
    ],
]
```

### Broadcast Channels

The application defines several broadcast channels for real-time communication:

```php
// User private channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Trading Management Addon channels
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('bot.{botId}', function ($user, $botId) {
    $bot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::find($botId);
    if (!$bot) {
        return false;
    }
    return (int) $user->id === (int) $bot->user_id;
});

Broadcast::channel('connection.{connectionId}', function ($user, $connectionId) {
    $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::find($connectionId);
    if (!$connection) {
        return false;
    }
    return (int) $user->id === (int) $connection->user_id;
});
```

### Real-time Event Types

#### Position Updates
Broadcasts position updates in real-time:

```php
class PositionUpdated implements ShouldBroadcast
{
    public ExecutionPosition $position;
    public ?int $userId;

    public function broadcastOn(): Channel
    {
        if ($this->userId) {
            return new Channel('user.' . $this->userId);
        }
        return new Channel('positions');
    }

    public function broadcastAs(): string
    {
        return 'position.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'position_id' => $this->position->id,
            'symbol' => $this->position->symbol,
            'direction' => $this->position->direction,
            'entry_price' => $this->position->entry_price,
            'current_price' => $this->position->current_price,
            'quantity' => $this->position->quantity,
            'pnl' => $this->position->pnl,
            'pnl_percentage' => $this->position->pnl_percentage,
            'updated_at' => $this->position->updated_at?->toIso8601String(),
        ];
    }
}
```

#### Position Closure
Broadcasts when positions are closed:

```php
class PositionClosed implements ShouldBroadcast
{
    public ExecutionPosition $position;
    public ?int $userId;

    public function broadcastOn(): Channel
    {
        if ($this->userId) {
            return new Channel('user.' . $this->userId);
        }
        return new Channel('positions');
    }

    public function broadcastAs(): string
    {
        return 'position.closed';
    }

    public function broadcastWith(): array
    {
        return [
            'position_id' => $this->position->id,
            'symbol' => $this->position->symbol,
            'direction' => $this->position->direction,
            'entry_price' => $this->position->entry_price,
            'exit_price' => $this->position->current_price,
            'quantity' => $this->position->quantity,
            'pnl' => $this->position->pnl,
            'pnl_percentage' => $this->position->pnl_percentage,
            'closed_reason' => $this->position->closed_reason,
            'closed_at' => $this->position->closed_at?->toIso8601String(),
        ];
    }
}
```

#### Balance Updates
Broadcasts account balance updates:

```php
class BalanceUpdated implements ShouldBroadcast
{
    public ExchangeConnection $connection;
    public array $balance;

    public function __construct(ExchangeConnection $connection, array $balance)
    {
        $this->connection = $connection;
        $this->balance = $balance;
    }

    public function broadcastOn(): Channel
    {
        $userId = $this->connection->user_id ?? null;
        if ($userId) {
            return new Channel('user.' . $userId);
        }
        return new Channel('balance');
    }

    public function broadcastAs(): string
    {
        return 'balance.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'connection_id' => $this->connection->id,
            'balance' => $this->balance,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

### Server-Sent Events (SSE)

The platform also supports Server-Sent Events for streaming data:

**Endpoint**: `GET /api/user/crypto-trading/stream-prices`  
**Authentication**: Required  
**Parameters**:
- `symbols[]` - Array of symbols to stream prices for

**Response**:
Streamed JSON data with price updates:
```json
{
  "success": true,
  "data": {
    "BTCUSDT": 50000.00,
    "ETHUSDT": 3000.00
  },
  "timestamp": "2023-01-01T00:00:00Z"
}
```

**Section sources**
- [channels.php](file://main/routes/channels.php)
- [channels.php](file://main/addons/trading-management-addon/routes/channels.php)
- [broadcasting.php](file://main/config/broadcasting.php)
- [PositionUpdated.php](file://main/addons/trading-management-addon/Modules/PositionMonitoring/Events/PositionUpdated.php)
- [PositionClosed.php](file://main/addons/trading-management-addon/Modules/PositionMonitoring/Events/PositionClosed.php)
- [BalanceUpdated.php](file://main/addons/trading-management-addon/Modules/PositionMonitoring/Events/BalanceUpdated.php)
- [CryptoTradeController.php](file://main/app/Http/Controllers/Api/User/CryptoTradeController.php#L162-L184)

## Error Handling

### Error Response Format

Standard error response format:
```json
{
  "error": "Error message",
  "message": "Detailed error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors, invalid input)
- `401` - Unauthorized (missing or invalid token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Unprocessable Entity (validation errors)
- `429` - Too Many Requests (rate limit exceeded)
- `500` - Internal Server Error

### Example Error Responses

**400 Bad Request**:
```json
{
  "error": "Invalid channel type",
  "message": "Channel source must be of type 'telegram'"
}
```

**401 Unauthorized**:
```json
{
  "error": "Invalid signature",
  "message": "Signature verification failed"
}
```

**500 Internal Server Error**:
```json
{
  "error": "Internal server error",
  "message": "An unexpected error occurred"
}
```

**Section sources**
- [api-reference.md](file://docs/api-reference.md#L264-L314)

## Rate Limiting

API endpoints are rate-limited to prevent abuse:

- **Default**: 60 requests per minute per IP
- **Webhooks**: No rate limit (uses channel source ID for identification)

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1609459200
```

When rate limit is exceeded:
```json
{
  "error": "Too Many Requests",
  "message": "Rate limit exceeded. Please try again later."
}
```

**Section sources**
- [api-reference.md](file://docs/api-reference.md#L318-L338)

## API Versioning and Compatibility

The current API implementation does not use explicit versioning in the URL structure. All endpoints are served under the `/api` prefix without version identifiers.

### Backward Compatibility Policies

- Breaking changes require major version increment
- New endpoints and fields are added without incrementing version
- Deprecated endpoints are maintained for 6 months before removal
- Field removals are communicated 3 months in advance
- API consumers are encouraged to use specific field selections rather than relying on full response objects

**Section sources**
- [api.php](file://main/routes/api.php)

## Client Implementation Guidelines

### JavaScript Client Example

```javascript
// Initialize API client
const apiClient = {
    baseUrl: 'https://aitradepulse.com/api',
    token: null,
    
    setToken(token) {
        this.token = token;
    },
    
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(this.token && {'Authorization': `Bearer ${this.token}`})
        };
        
        const config = {
            ...options,
            headers: {...headers, ...options.headers}
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    },
    
    // Authentication
    async login(email, password) {
        const data = await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({email, password, device_name: 'web'})
        });
        this.setToken(data.token);
        return data;
    },
    
    // Get user data
    async getUser() {
        return this.request('/user');
    },
    
    // Send webhook
    async sendSignal(channelSourceId, message) {
        return this.request(`/webhook/channel/${channelSourceId}`, {
            method: 'POST',
            body: JSON.stringify({message}),
            headers: {'X-Signature': this.calculateSignature(message)}
        });
    },
    
    calculateSignature(payload) {
        // Implement HMAC-SHA256 signature calculation
        // This is a placeholder - use appropriate crypto library
        return 'signature_hash';
    }
};

// Usage
async function exampleUsage() {
    try {
        // Login
        await apiClient.login('user@example.com', 'password');
        
        // Get user data
        const user = await apiClient.getUser();
        console.log('User:', user);
        
        // Send signal via webhook
        const result = await apiClient.sendSignal(1, 'BTC/USDT BUY 50000 SL 49000 TP 52000');
        console.log('Signal sent:', result);
    } catch (error) {
        console.error('Error:', error);
    }
}
```

### Python Client Example

```python
import requests
import json
import hmac
import hashlib
from datetime import datetime

class AITradePulseClient:
    def __init__(self, base_url="https://aitradepulse.com/api"):
        self.base_url = base_url
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        })
        self.token = None
    
    def set_token(self, token):
        self.token = token
        if token:
            self.session.headers['Authorization'] = f'Bearer {token}'
        else:
            self.session.headers.pop('Authorization', None)
    
    def request(self, endpoint, method='GET', **kwargs):
        url = f"{self.base_url}{endpoint}"
        
        # Add authentication if token is set
        if self.token and 'headers' not in kwargs:
            kwargs['headers'] = {}
        if self.token:
            kwargs['headers']['Authorization'] = f'Bearer {self.token}'
        
        response = self.session.request(method, url, **kwargs)
        
        if response.status_code >= 400:
            try:
                error_data = response.json()
                error_msg = error_data.get('message', response.text)
            except:
                error_msg = response.text
            raise Exception(f"Request failed: {error_msg}")
        
        return response.json()
    
    def login(self, email, password):
        data = {
            'email': email,
            'password': password,
            'device_name': 'python-client'
        }
        result = self.request('/auth/login', method='POST', json=data)
        self.set_token(result['token'])
        return result
    
    def get_user(self):
        return self.request('/user')
    
    def send_signal(self, channel_source_id, message, secret=None):
        data = {'message': message}
        headers = {}
        
        # Add signature if secret is provided
        if secret:
            signature = self.calculate_signature(json.dumps(data, separators=(',', ':')), secret)
            headers['X-Signature'] = signature
        
        return self.request(f'/webhook/channel/{channel_source_id}', 
                          method='POST', json=data, headers=headers)
    
    def calculate_signature(self, payload, secret):
        return hmac.new(
            secret.encode('utf-8'),
            payload.encode('utf-8'),
            hashlib.sha256
        ).hexdigest()

# Usage example
def example_usage():
    client = AITradePulseClient()
    
    try:
        # Login
        client.login('user@example.com', 'password')
        
        # Get user data
        user = client.get_user()
        print(f"User: {user}")
        
        # Send signal
        result = client.send_signal(1, 'BTC/USDT BUY 50000 SL 49000 TP 52000')
        print(f"Signal sent: {result}")
        
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    example_usage()
```

### PHP Client Example

```php
<?php

class AITradePulseClient
{
    private $baseUrl;
    private $token;
    private $httpClient;
    
    public function __construct($baseUrl = 'https://aitradepulse.com/api')
    {
        $this->baseUrl = $baseUrl;
        $this->httpClient = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }
    
    public function setToken($token)
    {
        $this->token = $token;
    }
    
    private function request($endpoint, $method = 'GET', $options = [])
    {
        $url = $this->baseUrl . $endpoint;
        $headers = [];
        
        // Add authentication header if token is set
        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }
        
        // Merge headers
        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
            unset($options['headers']);
        }
        
        $options['headers'] = $headers;
        
        try {
            $response = $this->httpClient->request($method, $url, $options);
            $body = $response->getBody()->getContents();
            
            return json_decode($body, true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $body = $response->getBody()->getContents();
                $error = json_decode($body, true);
                throw new Exception($error['message'] ?? 'Request failed');
            }
            throw new Exception('Request failed: ' . $e->getMessage());
        }
    }
    
    public function login($email, $password)
    {
        $data = [
            'email' => $email,
            'password' => $password,
            'device_name' => 'php-client'
        ];
        
        $result = $this->request('/auth/login', 'POST', [
            'json' => $data
        ]);
        
        $this->setToken($result['token']);
        return $result;
    }
    
    public function getUser()
    {
        return $this->request('/user');
    }
    
    public function sendSignal($channelSourceId, $message, $secret = null)
    {
        $data = ['message' => $message];
        $headers = [];
        
        // Add signature if secret is provided
        if ($secret) {
            $payload = json_encode($data, JSON_UNESCAPED_SLASHES);
            $signature = hash_hmac('sha256', $payload, $secret);
            $headers['X-Signature'] = $signature;
        }
        
        return $this->request("/webhook/channel/{$channelSourceId}", 'POST', [
            'json' => $data,
            'headers' => $headers
        ]);
    }
    
    public function calculateSignature($payload, $secret)
    {
        return hash_hmac('sha256', $payload, $secret);
    }
}

// Usage example
function exampleUsage()
{
    $client = new AITradePulseClient();
    
    try {
        // Login
        $client->login('user@example.com', 'password');
        
        // Get user data
        $user = $client->getUser();
        echo "User: " . json_encode($user) . "\n";
        
        // Send signal
        $result = $client->sendSignal(1, 'BTC/USDT BUY 50000 SL 49000 TP 52000');
        echo "Signal sent: " . json_encode($result) . "\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

exampleUsage();
```

**Section sources**
- [api-reference.md](file://docs/api-reference.md#L432-L522)

## Security Considerations

### Input Validation

All API endpoints implement strict input validation:

- User input is validated against defined rules
- Sanitization is applied to prevent XSS attacks
- Length restrictions are enforced on all string fields
- Type checking ensures data integrity
- Rate limiting prevents brute force attacks

### Authentication Scopes

While the current implementation uses standard Sanctum tokens, the system is designed to support token scopes:

- Read-only access for data retrieval
- Write access for creating/updating resources
- Administrative access for management operations
- Webhook access with limited permissions

### Data Protection

- All API communication should use HTTPS
- Sensitive data is encrypted at rest
- Passwords are hashed using bcrypt
- API tokens are stored securely
- Session management follows security best practices

### Webhook Security

- Signature verification for API webhooks (HMAC-SHA256)
- Channel source ID validation
- Input sanitization for message content
- Rate limiting on non-webhook endpoints
- Duplicate message filtering (24-hour window)

### Best Practices

1. **Use HTTPS**: Always use HTTPS for all API communications
2. **Secure Token Storage**: Store API tokens securely (not in client-side code)
3. **Validate Input**: Always validate and sanitize input data
4. **Handle Errors Gracefully**: Implement proper error handling and logging
5. **Monitor Usage**: Track API usage and detect anomalous patterns
6. **Keep Credentials Safe**: Never expose API keys or secrets in client code
7. **Implement Retries**: Use exponential backoff for failed requests
8. **Respect Rate Limits**: Monitor rate limit headers and adjust request frequency

**Section sources**
- [api.php](file://main/routes/api.php)
- [api-reference.md](file://docs/api-reference.md)