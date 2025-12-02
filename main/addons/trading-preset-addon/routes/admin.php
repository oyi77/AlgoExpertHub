<?php

use Addons\TradingPresetAddon\App\Http\Controllers\Backend\PresetController;

// Trading Presets Management
Route::prefix('trading-presets')->name('trading-presets.')->group(function () {
    Route::get('/', [PresetController::class, 'index'])->name('index');
    Route::get('/create', [PresetController::class, 'create'])->name('create');
    Route::post('/', [PresetController::class, 'store'])->name('store');
    Route::get('/{id}', [PresetController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [PresetController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PresetController::class, 'update'])->name('update');
    Route::post('/{id}', [PresetController::class, 'update'])->name('update.post');
    Route::delete('/{id}', [PresetController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/clone', [PresetController::class, 'clone'])->name('clone');
    Route::post('/{id}/toggle-status', [PresetController::class, 'toggleStatus'])->name('toggle-status');
});

