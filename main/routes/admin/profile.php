<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\AdminProfileController;

/*
|--------------------------------------------------------------------------
| Admin Profile Management Routes
|--------------------------------------------------------------------------
|
| Admin profile and password change routes. Accessible to all authenticated admins.
|
*/

Route::middleware(['web', 'admin', 'demo'])->prefix('admin/profile')->name('admin.profile.')->group(function () {
    Route::get('/', [AdminProfileController::class, 'profile'])->name('index');
    Route::put('/', [AdminProfileController::class, 'profileUpdate'])->name('update');
    Route::post('/change-password', [AdminProfileController::class, 'changePassword'])->name('change-password');
});
