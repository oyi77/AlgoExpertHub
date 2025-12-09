<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User private channel for position updates
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Bot-specific channel
Broadcast::channel('bot.{botId}', function ($user, $botId) {
    $bot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::find($botId);
    if (!$bot) {
        return false;
    }
    return (int) $user->id === (int) $bot->user_id;
});

// Connection-specific channel
Broadcast::channel('connection.{connectionId}', function ($user, $connectionId) {
    $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::find($connectionId);
    if (!$connection) {
        return false;
    }
    return (int) $user->id === (int) $connection->user_id;
});
