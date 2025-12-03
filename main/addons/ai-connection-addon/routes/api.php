<?php

use Illuminate\Support\Facades\Route;
use Addons\AiConnectionAddon\App\Http\Controllers\Api\AiConnectionApiController;

/*
|--------------------------------------------------------------------------
| AI Connection API Routes
|--------------------------------------------------------------------------
|
| API routes for consumer addons to interact with AI connections
|
*/

// Get available connections
Route::get('/providers/{provider}/connections', [AiConnectionApiController::class, 'getConnections']);

// Execute AI call
Route::post('/execute', [AiConnectionApiController::class, 'execute']);

// Test connection
Route::post('/test/{connection}', [AiConnectionApiController::class, 'test']);

// Track usage
Route::post('/track-usage', [AiConnectionApiController::class, 'trackUsage']);

