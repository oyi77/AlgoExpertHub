<?php

use Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelForwardingController;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\PatternTemplateController;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\SignalAnalyticsController;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\SignalSourceController;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AiConfigurationController;

// All multi-channel routes require signal permission
Route::middleware('permission:signal,admin')->group(function () {
    
// Signal Sources - Connection Management Only
Route::prefix('signal-sources')->name('signal-sources.')->group(function () {
    Route::get('/', [SignalSourceController::class, 'index'])->name('index');
    Route::get('/create/{type?}', [SignalSourceController::class, 'create'])->name('create');
    Route::post('/', [SignalSourceController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [SignalSourceController::class, 'edit'])->name('edit');
    Route::put('/{id}', [SignalSourceController::class, 'update'])->name('update');
    Route::post('/{id}', [SignalSourceController::class, 'update'])->name('update.post');
    Route::get('/{id}/authenticate', [SignalSourceController::class, 'authenticate'])->name('authenticate');
    Route::post('/{id}/authenticate', [SignalSourceController::class, 'authenticate'])->name('authenticate.post');
    Route::post('/{id}/test-connection', [SignalSourceController::class, 'testConnection'])->name('test-connection');
    Route::post('/{id}/status', [SignalSourceController::class, 'updateStatus'])->name('status');
    Route::delete('/{id}', [SignalSourceController::class, 'destroy'])->name('destroy');
});

// Channel Forwarding - Channel Selection & Assignment Management
Route::prefix('channel-forwarding')->name('channel-forwarding.')->group(function () {
    Route::get('/', [ChannelForwardingController::class, 'index'])->name('index');
    Route::get('/{id}/load-dialogs', [ChannelForwardingController::class, 'loadDialogs'])->name('load-dialogs');
    Route::get('/{id}/select-channel', [ChannelForwardingController::class, 'selectChannel'])->name('select-channel');
    Route::post('/{id}/select-channel', [ChannelForwardingController::class, 'selectChannel'])->name('select-channel.post');
    Route::get('/{id}/view-samples', [ChannelForwardingController::class, 'viewSampleMessages'])->name('view-samples');
    Route::post('/{id}/store-parser', [ChannelForwardingController::class, 'storeParser'])->name('store-parser');
    Route::post('/{id}/test-parser', [ChannelForwardingController::class, 'testParser'])->name('test-parser');
    Route::get('/{id}/assign', [ChannelForwardingController::class, 'assign'])->name('assign');
    Route::post('/{id}/assign', [ChannelForwardingController::class, 'storeAssignments'])->name('assign.store');
    Route::get('/{id}', [ChannelForwardingController::class, 'show'])->name('show');
    Route::delete('/{id}/users/{userId}', [ChannelForwardingController::class, 'removeUserAssignment'])->name('users.remove');
    Route::delete('/{id}/plans/{planId}', [ChannelForwardingController::class, 'removePlanAssignment'])->name('plans.remove');
});

// Channel Signals (Auto-Created) - Review & Approval
Route::prefix('channel-signals')->name('channel-signals.')->group(function () {
    Route::get('/', [ChannelSignalController::class, 'index'])->name('index');
    Route::get('/{id}', [ChannelSignalController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [ChannelSignalController::class, 'edit'])->name('edit');
    Route::post('/{id}', [ChannelSignalController::class, 'update'])->name('update');
    Route::post('/{id}/approve', [ChannelSignalController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [ChannelSignalController::class, 'reject'])->name('reject');
    Route::post('/bulk/approve', [ChannelSignalController::class, 'bulkApprove'])->name('bulk.approve');
    Route::post('/bulk/reject', [ChannelSignalController::class, 'bulkReject'])->name('bulk.reject');
});

// Pattern Templates Management
Route::prefix('pattern-templates')->name('pattern-templates.')->group(function () {
    Route::get('/', [PatternTemplateController::class, 'index'])->name('index');
    Route::get('/create', [PatternTemplateController::class, 'create'])->name('create');
    Route::post('/', [PatternTemplateController::class, 'store'])->name('store');
    Route::get('/{patternTemplate}/edit', [PatternTemplateController::class, 'edit'])->name('edit');
    Route::post('/{patternTemplate}', [PatternTemplateController::class, 'update'])->name('update');
    Route::delete('/{patternTemplate}', [PatternTemplateController::class, 'destroy'])->name('destroy');
    Route::post('/test', [PatternTemplateController::class, 'test'])->name('test');
});

// Signal Analytics & Reporting
Route::prefix('signal-analytics')->name('signal-analytics.')->group(function () {
    Route::get('/', [SignalAnalyticsController::class, 'index'])->name('index');
    Route::get('/report', [SignalAnalyticsController::class, 'report'])->name('report');
    Route::get('/channel/{channelSourceId}', [SignalAnalyticsController::class, 'channel'])->name('channel');
    Route::get('/plan/{planId}', [SignalAnalyticsController::class, 'plan'])->name('plan');
    Route::get('/export', [SignalAnalyticsController::class, 'export'])->name('export');
});

// AI Parsing Profiles (NEW - Uses AI Connection Addon)
Route::prefix('ai-parsing-profiles')->name('ai-parsing-profiles.')->group(function () {
    Route::get('/', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AiParsingProfileController::class, 'index'])->name('index');
    Route::get('/create', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AiParsingProfileController::class, 'create'])->name('create');
    Route::post('/', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AiParsingProfileController::class, 'store'])->name('store');
    Route::get('/{profile}/edit', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AiParsingProfileController::class, 'edit'])->name('edit');
    Route::put('/{profile}', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AiParsingProfileController::class, 'update'])->name('update');
    Route::delete('/{profile}', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AiParsingProfileController::class, 'destroy'])->name('destroy');
    Route::post('/{profile}/test', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AiParsingProfileController::class, 'testParsing'])->name('test');
    Route::post('/{profile}/toggle', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AiParsingProfileController::class, 'toggleStatus'])->name('toggle');
});

// AI Configuration (DEPRECATED - Use AI Parsing Profiles instead)
Route::prefix('ai-configuration')->name('ai-configuration.')->middleware('permission:manage-addon,admin')->group(function () {
    Route::get('/', [AiConfigurationController::class, 'index'])->name('index');
    Route::get('/create', [AiConfigurationController::class, 'create'])->name('create');
    Route::post('/', [AiConfigurationController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [AiConfigurationController::class, 'edit'])->name('edit');
    Route::post('/{id}', [AiConfigurationController::class, 'update'])->name('update');
    Route::delete('/{id}', [AiConfigurationController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/test-connection', [AiConfigurationController::class, 'testConnection'])->name('test-connection');
    Route::post('/{id}/test-parse', [AiConfigurationController::class, 'testParse'])->name('test-parse');
    Route::post('/{id}/fetch-models', [AiConfigurationController::class, 'fetchModelsFromConfig'])->name('fetch-models-from-config');
    Route::post('/fetch-models', [AiConfigurationController::class, 'fetchModels'])->name('fetch-models');
});

}); // End permission middleware group

