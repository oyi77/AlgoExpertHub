<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Telegram webhook endpoint (no auth required, uses channel source ID)
Route::post('/webhook/telegram/{channelSourceId}', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);

// API webhook endpoint (no auth required, uses channel source ID)
Route::post('/webhook/channel/{channelSourceId}', [\App\Http\Controllers\Api\ApiWebhookController::class, 'handle']);
