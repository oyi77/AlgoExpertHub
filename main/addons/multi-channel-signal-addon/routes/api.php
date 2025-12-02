<?php

use Addons\MultiChannelSignalAddon\App\Http\Controllers\Api\TelegramWebhookController;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Api\ApiWebhookController;

// Telegram webhook endpoint
Route::post('/webhook/telegram/{channelSourceId}', [TelegramWebhookController::class, 'handle']);

// API webhook endpoint
Route::post('/webhook/channel/{channelSourceId}', [ApiWebhookController::class, 'handle']);


