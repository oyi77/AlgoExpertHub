<?php

use Addons\MultiChannelSignalAddon\App\Http\Controllers\User\ChannelForwardingController;
use Addons\MultiChannelSignalAddon\App\Http\Controllers\User\SignalSourceController;

// Signal Sources - User's Own Connections
Route::prefix('signal-sources')->name('signal-sources.')->group(function () {
    Route::get('/', [SignalSourceController::class, 'index'])->name('index');
    Route::get('/create/{type?}', [SignalSourceController::class, 'create'])->name('create');
    Route::post('/', [SignalSourceController::class, 'store'])->name('store');
    Route::get('/{id}/authenticate', [SignalSourceController::class, 'authenticate'])->name('authenticate');
    Route::post('/{id}/authenticate', [SignalSourceController::class, 'authenticate'])->name('authenticate.post');
    Route::post('/{id}/test-connection', [SignalSourceController::class, 'testConnection'])->name('test-connection');
    Route::post('/{id}/status', [SignalSourceController::class, 'updateStatus'])->name('status');
    Route::delete('/{id}', [SignalSourceController::class, 'destroy'])->name('destroy');
});

// Channel Forwarding - Channels Assigned to User
Route::prefix('channel-forwarding')->name('channel-forwarding.')->group(function () {
    Route::get('/', [ChannelForwardingController::class, 'index'])->name('index');
    Route::get('/{id}', [ChannelForwardingController::class, 'show'])->name('show');
    Route::get('/{id}/select-channel', [ChannelForwardingController::class, 'selectChannel'])->name('select-channel');
    Route::post('/{id}/select-channel', [ChannelForwardingController::class, 'selectChannel'])->name('select-channel.post');
});

