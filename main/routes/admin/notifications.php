<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\AdminController;

/*
|--------------------------------------------------------------------------
| Admin Notification Routes
|--------------------------------------------------------------------------
|
| Notification management routes. Accessible to all authenticated admins.
|
*/

Route::middleware(['web', 'admin', 'demo'])->prefix('admin')->name('admin.')->group(function () {
    // Notifications
    Route::get('/all-notifications', [AdminController::class, 'notifications'])->name('notifications');
    Route::get('/mark-as-read', [AdminController::class, 'markNotification'])->name('markNotification');
    Route::post('/mark-as-read/{id}', [AdminController::class, 'SignlemarkNotification'])->name('markNotification.single');
});