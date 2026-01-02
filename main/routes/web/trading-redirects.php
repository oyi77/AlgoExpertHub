<?php

/*
|--------------------------------------------------------------------------
| Backward Compatibility Redirects
|--------------------------------------------------------------------------
|
| Old routes that redirect to new unified pages for backward compatibility.
|
*/

// Multi-Channel Signal Addon - Old routes
Route::get('external-signals', function() {
    return redirect()->route('user.trading.multi-channel-signal.index', ['tab' => 'signal-sources']);
})->name('external-signals.index');

Route::get('signal-sources', function() {
    return redirect()->route('user.trading.multi-channel-signal.index', ['tab' => 'signal-sources']);
})->name('signal-sources.index');

Route::get('channel-forwarding', function() {
    return redirect()->route('user.trading.multi-channel-signal.index', ['tab' => 'channel-forwarding']);
})->name('channel-forwarding.index');

// Trading Management - Old routes
Route::get('execution-connections', function() {
    return redirect()->route('user.trading.operations.index', ['tab' => 'connections']);
})->name('execution-connections.index');

Route::get('trading-presets', function() {
    return redirect()->route('user.trading.configuration.index', ['tab' => 'risk-presets']);
})->name('trading-presets.index');

Route::get('filter-strategies', function() {
    return redirect()->route('user.trading.configuration.index', ['tab' => 'filter-strategies']);
})->name('filter-strategies.index');

Route::get('ai-model-profiles', function() {
    return redirect()->route('user.trading.configuration.index', ['tab' => 'ai-profiles']);
})->name('ai-model-profiles.index');
