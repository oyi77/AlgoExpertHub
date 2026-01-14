<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\AddonController;

/*
|--------------------------------------------------------------------------
| Admin Addon Management Routes
|--------------------------------------------------------------------------
|
| Addon management routes. Requires manage-addon permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:manage-addon,admin'])->prefix('admin/addons')->name('admin.addons.')->group(function () {
    Route::get('/', [AddonController::class, 'index'])->name('index');
    Route::post('/upload', [AddonController::class, 'upload'])->name('upload');
    Route::post('/{addon}/status', [AddonController::class, 'updateStatus'])->name('status');
    Route::get('/{addon}/modules', [AddonController::class, 'modules'])->name('modules');
    Route::post('/{addon}/modules/{module}', [AddonController::class, 'updateModule'])->name('modules.update');
});
