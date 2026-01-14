<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\AdminController;

/*
|--------------------------------------------------------------------------
| Admin Subscriber Management Routes
|--------------------------------------------------------------------------
|
| Newsletter subscriber management routes. Accessible to all authenticated admins.
|
*/

Route::middleware(['web', 'admin', 'demo'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/subscribers', [AdminController::class, 'subscribers'])->name('subscribers');
    Route::post('/subscribers/{email}', [AdminController::class, 'singleMail'])->name('subscribers.single');
    Route::post('/bulk/mail', [AdminController::class, 'bulkMail'])->name('subscribers.bulk');
});