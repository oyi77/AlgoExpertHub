<?php

use Addons\FilterStrategyAddon\App\Http\Controllers\User\FilterStrategyController;
use Illuminate\Support\Facades\Route;

Route::prefix('filter-strategies')->name('filter-strategies.')->group(function () {
    Route::get('/', [FilterStrategyController::class, 'index'])->name('index');
    Route::get('/marketplace', [FilterStrategyController::class, 'marketplace'])->name('marketplace');
    Route::get('/create', [FilterStrategyController::class, 'create'])->name('create');
    Route::post('/', [FilterStrategyController::class, 'store'])->name('store');
    Route::get('/{id}', [FilterStrategyController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [FilterStrategyController::class, 'edit'])->name('edit');
    Route::put('/{id}', [FilterStrategyController::class, 'update'])->name('update');
    Route::delete('/{id}', [FilterStrategyController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/clone', [FilterStrategyController::class, 'clone'])->name('clone');
});

