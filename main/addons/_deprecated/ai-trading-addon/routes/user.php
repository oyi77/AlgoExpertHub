<?php

use Illuminate\Support\Facades\Route;
use Addons\AiTradingAddon\App\Http\Controllers\User\AiModelProfileController;

Route::prefix('ai-model-profiles')->name('ai-model-profiles.')->group(function () {
    Route::get('/', [AiModelProfileController::class, 'index'])->name('index');
    Route::get('/marketplace', [AiModelProfileController::class, 'marketplace'])->name('marketplace');
    Route::get('/create', [AiModelProfileController::class, 'create'])->name('create');
    Route::post('/', [AiModelProfileController::class, 'store'])->name('store');
    Route::get('/{id}', [AiModelProfileController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [AiModelProfileController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AiModelProfileController::class, 'update'])->name('update');
    Route::delete('/{id}', [AiModelProfileController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/clone', [AiModelProfileController::class, 'clone'])->name('clone');
});

