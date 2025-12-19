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

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Trading Bot Channels
|--------------------------------------------------------------------------
*/

// Admin channel for trading bot updates (admin must be authenticated)
Broadcast::channel('admin.trading-bot.{botId}', function ($user, $botId) {
    // Check if user is admin (admin guard)
    if ($user instanceof \App\Models\Admin) {
        return true; // Admins can access any bot
    }
    return false;
});

// User channel for their own trading bots
Broadcast::channel('user.{userId}.trading-bot.{botId}', function ($user, $userId, $botId) {
    // User must match the channel userId
    if ((int) $user->id !== (int) $userId) {
        return false;
    }
    
    // User must own the bot
    $bot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::find($botId);
    if (!$bot || $bot->user_id !== (int) $userId) {
        return false;
    }
    
    return true;
});

// User market data channel
Broadcast::channel('user.{userId}.market', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// User positions channel for trading terminal
Broadcast::channel('user.{userId}.positions', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
