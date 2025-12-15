# API Reference

> [!NOTE]
> **📖 Comprehensive API Documentation Available**
> 
> For complete, auto-generated API documentation with detailed endpoint descriptions, request/response examples, and real-time communication guides, see:
> 
> **[Complete API Reference](../.qoder/repowiki/en/content/API%20Reference/API%20Reference.md)** - Full REST API, WebSocket, and Webhook documentation
>
> #### Quick Navigation:
> - [Authentication](../.qoder/repowiki/en/content/API%20Reference/Authentication.md) - Sanctum, OAuth, API tokens
> - [User Management](../.qoder/repowiki/en/content/API%20Reference/User%20Management.md) - User profile and account operations
> - [Trading Operations](../.qoder/repowiki/en/content/API%20Reference/Trading%20Operations.md) - Trading bots, positions, execution
> - [Signal Processing](../.qoder/repowiki/en/content/API%20Reference/Signal%20Processing.md) - Signal management and distribution
> - [System Monitoring](../.qoder/repowiki/en/content/API%20Reference/System%20Monitoring.md) - Admin monitoring and system operations
> - [Webhooks](../.qoder/repowiki/en/content/API%20Reference/Webhooks.md) - Telegram and API webhook endpoints
> - [Real-time Communication](../.qoder/repowiki/en/content/API%20Reference/Real-time%20Communication.md) - WebSocket and Server-Sent Events

---

## Quick Reference Guide

Complete REST API documentation for AlgoExpertHub Trading Signal Platform.

## Table of Contents

- [Authentication](#authentication)
- [Base URL](#base-url)
- [Endpoints](#endpoints)
- [Webhooks](#webhooks)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Examples](#examples)


---

## Authentication

The platform uses **Laravel Sanctum** for API authentication. Most endpoints require authentication via Bearer token.

### Obtaining API Token

**Endpoint**: `POST /api/sanctum/token` (if implemented)

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

Include token in Authorization header:

```
Authorization: Bearer {token}
```

---

## Base URL

```
Production: https://yourdomain.com/api
Development: http://localhost/api
```

All endpoints are prefixed with `/api`.

---

## Endpoints

### User Endpoints

#### Get Authenticated User

**Endpoint**: `GET /api/user`

**Authentication**: Required

**Response**:
```json
{
  "id": 1,
  "username": "john_doe",
  "email": "john@example.com",
  "balance": "100.00",
  "created_at": "2023-01-01T00:00:00.000000Z"
}
```

---

## Webhooks

Webhooks allow external services to send data to the platform. Webhooks do **not** require authentication but use channel source IDs for identification.

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

**Or**:
```json
{
  "update_id": 123456790,
  "message": {
    "message_id": 2,
    "from": {
      "id": 123456789,
      "is_bot": false,
      "first_name": "John"
    },
    "chat": {
      "id": 123456789,
      "type": "private"
    },
    "date": 1609459200,
    "text": "BTC/USDT SELL 50000 SL 51000 TP 48000"
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
  -d url=https://yourdomain.com/api/webhook/telegram/{channelSourceId}
```

3. Verify webhook:
```bash
curl https://api.telegram.org/bot{TOKEN}/getWebhookInfo
```

---

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

**Or**:
```json
{
  "text": "BTC/USDT SELL 50000 SL 51000 TP 48000"
}
```

**Or**:
```json
{
  "content": "GBP/USD LONG 1.2500 SL 1.2450 TP 1.2600",
  "timestamp": "2023-01-01T00:00:00Z"
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
curl -X POST https://yourdomain.com/api/webhook/channel/1 \
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

---

## Error Handling

### Error Response Format

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

---

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

---

## Examples

### Example 1: Telegram Webhook Integration

**Setup**:
1. Create Telegram channel source in admin panel
2. Get channel source ID (e.g., `1`)
3. Configure Telegram bot webhook

**Webhook URL**:
```
https://yourdomain.com/api/webhook/telegram/1
```

**Telegram sends update**:
```json
{
  "update_id": 123456789,
  "channel_post": {
    "message_id": 1,
    "chat": {
      "id": -1001234567890,
      "title": "Trading Signals"
    },
    "date": 1609459200,
    "text": "EUR/USD BUY 1.1000 SL 1.0950 TP 1.1100"
  }
}
```

**Platform response**:
```json
{
  "ok": true
}
```

**What happens**:
1. Webhook receives update
2. Extracts message text
3. Creates `ChannelMessage` record
4. Dispatches `ProcessChannelMessage` job
5. Job parses message and creates draft signal
6. Admin reviews and publishes signal

---

### Example 2: Custom API Webhook Integration

**Setup**:
1. Create API channel source in admin panel
2. Get channel source ID (e.g., `2`)
3. Configure signature secret (optional)

**Webhook URL**:
```
https://yourdomain.com/api/webhook/channel/2
```

**Send signal via cURL**:
```bash
curl -X POST https://yourdomain.com/api/webhook/channel/2 \
  -H "Content-Type: application/json" \
  -d '{
    "message": "BTC/USDT SELL 50000 SL 51000 TP 48000",
    "timestamp": "2023-01-01T00:00:00Z"
  }'
```

**With signature verification**:
```bash
SECRET="your_secret_key"
PAYLOAD='{"message": "EUR/USD BUY 1.1000 SL 1.0950 TP 1.1100"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

curl -X POST https://yourdomain.com/api/webhook/channel/2 \
  -H "Content-Type: application/json" \
  -H "X-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

**Platform response**:
```json
{
  "ok": true
}
```

---

### Example 3: PHP Webhook Client

```php
<?php

$channelSourceId = 1;
$webhookUrl = "https://yourdomain.com/api/webhook/channel/{$channelSourceId}";
$message = "EUR/USD BUY 1.1000 SL 1.0950 TP 1.1100";

$data = [
    'message' => $message,
    'timestamp' => date('c'),
];

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "Signal sent successfully\n";
} else {
    echo "Error: HTTP {$httpCode}\n";
    echo $response . "\n";
}
```

---

### Example 4: Python Webhook Client

```python
import requests
import json
from datetime import datetime

channel_source_id = 1
webhook_url = f"https://yourdomain.com/api/webhook/channel/{channel_source_id}"

payload = {
    "message": "EUR/USD BUY 1.1000 SL 1.0950 TP 1.1100",
    "timestamp": datetime.now().isoformat()
}

headers = {
    "Content-Type": "application/json"
}

response = requests.post(webhook_url, json=payload, headers=headers)

if response.status_code == 200:
    print("Signal sent successfully")
else:
    print(f"Error: {response.status_code}")
    print(response.json())
```

---

### Example 5: Node.js Webhook Client

```javascript
const axios = require('axios');

const channelSourceId = 1;
const webhookUrl = `https://yourdomain.com/api/webhook/channel/${channelSourceId}`;

const payload = {
  message: 'EUR/USD BUY 1.1000 SL 1.0950 TP 1.1100',
  timestamp: new Date().toISOString()
};

axios.post(webhookUrl, payload, {
  headers: {
    'Content-Type': 'application/json'
  }
})
.then(response => {
  console.log('Signal sent successfully');
})
.catch(error => {
  console.error('Error:', error.response?.status, error.response?.data);
});
```

---

## Testing Webhooks

### Test Telegram Webhook Locally

Use ngrok to expose local server:

```bash
ngrok http 8000
```

Set Telegram webhook to ngrok URL:
```bash
curl -X POST https://api.telegram.org/bot{TOKEN}/setWebhook \
  -d url=https://your-ngrok-url.ngrok.io/api/webhook/telegram/1
```

### Test API Webhook

```bash
# Simple test
curl -X POST http://localhost/api/webhook/channel/1 \
  -H "Content-Type: application/json" \
  -d '{"message": "Test signal"}'

# With signature
SECRET="test_secret"
PAYLOAD='{"message": "Test signal"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

curl -X POST http://localhost/api/webhook/channel/1 \
  -H "Content-Type: application/json" \
  -H "X-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

---

## Best Practices

1. **Use HTTPS**: Always use HTTPS for webhooks in production
2. **Verify Signatures**: Enable signature verification for API webhooks
3. **Handle Errors**: Implement retry logic for failed webhook calls
4. **Monitor Logs**: Check webhook logs regularly for errors
5. **Rate Limiting**: Respect rate limits and implement exponential backoff
6. **Idempotency**: Webhooks automatically handle duplicates, but ensure your client is idempotent too

---

## Support

For API support:
- Check logs: `storage/logs/laravel.log`
- Review webhook controller code
- Check channel source configuration in admin panel

---

**Last Updated**: 2025-12-02
