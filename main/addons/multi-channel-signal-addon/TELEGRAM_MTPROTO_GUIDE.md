# Telegram MTProto Integration Guide

## What is MTProto?

MTProto is Telegram's protocol that allows you to login as a **user account** (not just a bot). This gives you access to:
- Private channels you're a member of
- Groups you're in
- Full message history
- User-level permissions

This is similar to Telethon in Python.

## Getting API Credentials

1. Go to https://my.telegram.org/apps
2. Login with your phone number
3. Create a new application
4. You'll get:
   - **api_id**: A number (e.g., 123456)
   - **api_hash**: A string (e.g., "abcdef1234567890")

## Installation

### Step 1: Install MadelineProto

```bash
cd /home/u875299794/domains/algoexperthub.com/public_html/main
composer require danog/madelineproto
```

### Step 2: Create Channel Source

Go to: `/user/channels/create/telegram_mtproto`

Or programmatically:

```php
use Addons\MultiChannelSignalAddon\App\Services\TelegramMtprotoService;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;

$service = new TelegramMtprotoService();

$result = $service->createChannel([
    'user_id' => auth()->id(),
    'name' => 'My Private Channel',
    'api_id' => 'YOUR_API_ID',
    'api_hash' => 'YOUR_API_HASH',
    'phone_number' => '+1234567890', // Your Telegram phone number
    'channel_username' => '@my_channel', // Optional: channel to monitor
]);
```

## Authentication Flow

### Step 1: Start Authentication

The system will request your phone number if not provided.

### Step 2: Receive Code

Telegram will send a verification code to your phone.

### Step 3: Enter Code

Complete authentication by entering the code:

```php
$service->completeAuth($channelSource, $code, $phoneCodeHash);
```

## Using the Channel

Once authenticated:
- The system will automatically fetch messages from the specified channel
- Messages are processed every 5 minutes via scheduled command
- You can access private channels you're a member of
- No need to add bot as admin

## Session Storage

Sessions are stored in: `storage/app/madelineproto/{channel_source_id}.session`

These files contain your authentication and should be kept secure.

## Security Notes

⚠️ **Important:**
- MTProto uses your **personal Telegram account**
- Session files are encrypted but contain sensitive data
- Keep session files secure
- Don't share API credentials
- Use 2FA on your Telegram account for extra security

## Differences from Bot API

| Feature | Bot API | MTProto |
|---------|---------|---------|
| Account Type | Bot | User |
| Private Channels | No (bot must be admin) | Yes (if you're member) |
| Setup Complexity | Simple | More complex (auth required) |
| Session Files | Not needed | Required |
| API Credentials | Bot token | API ID + Hash |

## Troubleshooting

### "MadelineProto not installed"
```bash
composer require danog/madelineproto
```

### "Authentication failed"
- Check API ID and Hash are correct
- Verify phone number format (+country code)
- Check verification code is correct
- Session files may be corrupted (delete and retry)

### "Could not resolve channel"
- Verify you're a member of the channel
- Check channel username/ID is correct
- Try using channel ID instead of username

### "Session expired"
- Re-authenticate by deleting session file
- Or use the authentication flow again


