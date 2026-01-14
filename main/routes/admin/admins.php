<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\AdminController;

/*
|--------------------------------------------------------------------------
| Admin Management Routes
|--------------------------------------------------------------------------
|
| Admin account management routes. Requires manage-admin permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:manage-admin,admin'])->prefix('admin/admins')->name('admin.admins.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/create', [AdminController::class, 'create'])->name('create');
    Route::post('/', [AdminController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AdminController::class, 'update'])->name('update');
    Route::post('/changeStatus/{id}', [AdminController::class, 'changeStatus'])->name('changestatus');
});